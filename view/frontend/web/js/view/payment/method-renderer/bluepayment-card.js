define([
    'jquery',
    'mage/url',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
], function (
    $,
    url,
    $t,
    quote,
    Component,
    config,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-card',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_short_description: null,
            gateway_description: null,
        },
        redirectAfterPlaceOrder: false,

        initialize: function () {
            this._super();

            this.widget = null;
            this.widgetReady = false;
            this.widgetValid = false;

            return this;
        },

        selectPaymentMethod: function () {
            const result = this._super();

            setTimeout(function () {
                this.ensureWidgetInitialized();
            }.bind(this), 0);

            return result;
        },

        afterPlaceOrder: function () {
            this.callWidgetPayment();
            return false;
        },

        getWidgetFrameId: function () {
            return 'bluepayment-widget-' + this.gateway_id;
        },

        getWidgetHost: function () {
            return config.testMode ? 'https://testcards.autopay.eu' : 'https://cards.autopay.eu';
        },

        getWidgetIframeUrl: function () {
            return this.getWidgetHost() + '/widget-new/partner';
        },

        getWidgetAmount: function () {
            const totals = quote.getTotals()();
            const fallbackTotals = window.checkoutConfig?.totalsData || {};
            const amount = Number(totals?.grand_total ?? fallbackTotals?.grand_total ?? 0);

            return Number(amount.toFixed(2));
        },

        getWidgetCurrency: function () {
            const totals = quote.getTotals()();
            const fallbackTotals = window.checkoutConfig?.totalsData || {};

            return totals?.quote_currency_code
                || fallbackTotals?.quote_currency_code
                || fallbackTotals?.base_currency_code
                || 'PLN';
        },

        getWidgetLanguage: function () {
            const htmlLang = document.documentElement?.lang || 'pl';
            return htmlLang.toLowerCase().split('-')[0].split('_')[0];
        },

        getWidgetConfig: function () {
            const widgetConfig = {
                language: this.getWidgetLanguage(),
                amount: this.getWidgetAmount(),
                currency: this.getWidgetCurrency(),
                serviceId: config.serviceId,
            };

            const recurringAction = this.getWidgetRecurringAction();
            if (recurringAction) {
                widgetConfig.recurringAction = recurringAction;
            }

            return widgetConfig;
        },

        getWidgetRecurringAction: function () {
            return null;
        },

        ensureWidgetInitialized: function () {
            if (this.widget) {
                return;
            }

            const frame = document.getElementById(this.getWidgetFrameId());
            if (!frame) {
                return;
            }

            if (typeof WidgetConnection === 'undefined' || typeof widgetEvents === 'undefined') {
                return;
            }

            frame.src = this.getWidgetIframeUrl();

            this.widget = new WidgetConnection(this.getWidgetConfig());
            this.widget.startConnection(frame).then(function () {
                this.widgetReady = true;

                this.widget.on(widgetEvents.formSuccess, this.onWidgetFormSuccess.bind(this));
                this.widget.on(widgetEvents.formError, this.onWidgetFormError.bind(this));
                this.widget.on(widgetEvents.validityStatus, this.onWidgetValidity.bind(this));
                this.widget.on(widgetEvents.validationResult, this.onWidgetValidity.bind(this));
            }.bind(this), function () {
                this.widget = null;
                this.widgetReady = false;
            }.bind(this));
        },

        callWidgetPayment: function () {
            this.ensureWidgetInitialized();

            if (!this.widget || !this.widgetReady) {
                this.messageContainer.addErrorMessage({
                    message: $t('Card form is not ready yet. Please wait a moment and try again.')
                });
                return false;
            }

            if (this.widget.invalid === true || this.widgetValid === false) {
                this.widget.validateForm();
                this.messageContainer.addErrorMessage({
                    message: $t('Please complete card data correctly.')
                });
                return false;
            }

            this.widget.sendForm();

            return false;
        },

        onWidgetValidity: function (message, eventData) {
            this.widgetValid = Boolean(eventData && eventData.valid);
        },

        onWidgetFormSuccess: function (message, eventData) {
            const paymentToken = this.extractPaymentToken(message, eventData);

            if (!paymentToken) {
                this.messageContainer.addErrorMessage({
                    message: $t('Card token is missing. Please try again.')
                });
                return;
            }

            $.ajax({
                showLoader: true,
                url: url.build('bluepayment/processing/create'),
                type: 'POST',
                dataType: 'json',
                data: this.getWidgetCreatePayload(paymentToken),
            }).done(function (response) {
                if (response?.redirect_url) {
                    window.location.href = response.redirect_url;
                    return;
                }

                this.messageContainer.addErrorMessage({
                    message: $t('Payment could not be started. Please try again.')
                });
            }.bind(this)).fail(function () {
                this.messageContainer.addErrorMessage({
                    message: $t('Payment request failed. Please try again.')
                });
            }.bind(this));
        },

        onWidgetFormError: function () {
            this.messageContainer.addErrorMessage({
                message: $t('Check card form fields and try again.')
            });
        },

        extractPaymentToken: function (message, eventData) {
            if (typeof message === 'string') {
                return message;
            }

            if (message && typeof message === 'object' && typeof message.message === 'string') {
                return message.message;
            }

            if (eventData && typeof eventData.message === 'string') {
                return eventData.message;
            }

            return '';
        },

        resetWidget: function () {
            if (this.widget && typeof this.widget.stopConnection === 'function') {
                this.widget.stopConnection();
            }

            this.widget = null;
            this.widgetReady = false;
            this.widgetValid = false;

            const frame = document.getElementById(this.getWidgetFrameId());
            if (frame) {
                frame.removeAttribute('src');
            }
        },

        getWidgetCreatePayload: function (paymentToken) {
            return {
                gateway_id: this.gateway_id,
                automatic: true,
                token: paymentToken,
                form_key: window.FORM_KEY,
            };
        },
    });
});
