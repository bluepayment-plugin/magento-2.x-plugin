define([
    'jquery',
    'ko',
    'mage/translate',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/modal/modal',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
    'BlueMedia_BluePayment/js/checkout-data',
    'text!BlueMedia_BluePayment/template/wait-popup.html',
], function (
    $,
    ko,
    $t,
    url,
    quote,
    modal,
    Component,
    config,
    model,
    checkoutData,
    popupTpl,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-blik',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,

            blikZeroEnabled: config.blikZeroEnabled,
        },

        blikInputSelector: "input[name='payment_method_bluepayment_code']",
        blikModal: modal(
            {
                title: $t('Confirm BLIK transaction'),
                autoOpen: false,
                clickableOverlay: false,
                buttons: [],
                type: 'popup',
                popupTpl: popupTpl,
                keyEventHandlers: {},
                modalClass: 'blik-modal',
            },
            $('<div />').html(
                $t("Confirm the payment in your bank's app.")
            )
        ),
        blikTimeout: null,
        redirectAfterPlaceOrder: !config.blikZeroEnabled,

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'separated': true,
                    'gateway_id': this.gateway_id,
                    'agreements_ids': model.getCheckedAgreementsIds(),
                }
            }
        },

        /**
         * Custom validation for payment method.
         *
         * @return {Boolean}
         */
        validate: function () {
            if (this.blikZeroEnabled) {
                const code = $(this.blikInputSelector).val();
                if (code.length !== 6) {
                    this.messageContainer.addErrorMessage({
                        message: $t('Invalid BLIK code.')
                    });
                    $(this.blikInputSelector).focus();
                    return false;
                }
            }

            return true;
        },

        /**
         * After place order callback.
         *
         * @returns {boolean}
         */
        afterPlaceOrder: function () {
            if (this.blikZeroEnabled) {
                this.callBlikPayment();
                return false;
            } else {
                return this._super();
            }
        },

        /**
         * Initialize BLIK code input.
         *
         * @returns {void}
         */
        blikCodeAfterRender: function () {
            // Only number for blik code
            const blikInput = $(this.blikInputSelector);
            if (blikInput) {
                blikInput.keypress(function () {
                    blikInput.val(this.value.match(/\d{0,6}/));
                });
            }
        },

        /**
         * Start BLIK payment and wait for status.
         *
         * @returns {boolean}
         */
        callBlikPayment: function () {
            const self = this;

            const urlResponse = url.build('bluepayment/processing/create')
                + '?gateway_id=' + this.gateway_id
                + '&automatic=true';
            const code = $(this.blikInputSelector).val();

            if (code.length === 6) {
                $.ajax({
                    showLoader: true,
                    url: urlResponse,
                    data: {'code': code},
                    type: "POST",
                    dataType: "json",
                }).done(function (response) {
                    if (response.params) {
                        if (response.params.confirmation && response.params.confirmation === 'NOTCONFIRMED') {
                            self.messageContainer.addErrorMessage({
                                message: $t('Invalid BLIK code.')
                            });
                        } else {
                            self.handleStatus(response.params.paymentStatus, response.params);
                        }
                    }
                });
            }

            return false;
        },

        /**
         * Handle BLIK status.
         *
         * @param {string} status
         * @param {object} params
         */
        handleStatus: function (status, params) {
            const self = this;

            if (status === 'SUCCESS') {
                clearTimeout(self.blikTimeout);

                window.location.href = url.build('bluepayment/processing/back')
                    + '?ServiceID=' + params.ServiceID
                    + '&OrderID=' + params.OrderID
                    + '&Hash=' + params.hash
                    + '&Status=' + 'SUCCESS';
            } else if (status === 'FAILURE') {
                clearTimeout(self.blikTimeout);
                this.blikModal.closeModal();
                this.messageContainer.addErrorMessage({
                    message: $t('Request timed out. Please try again or use a different payment method.')
                });
            } else {
                if (this.blikModal.options.isOpen !== true) {
                    this.blikModal.openModal();
                    this.blikModal._removeKeyListener();

                    this.blikTimeout = setTimeout(function () {
                        self.blikModal.closeModal();
                        self.messageContainer.addErrorMessage({
                            message: $t('The BLIK code has expired. Try again.')
                        });
                    }, 120000); /* 2 minutes */
                }

                setTimeout(function () {
                    if (self.blikModal.options.isOpen) {
                        self.updateStatus(status);
                    }
                }, 2000);
            }
        },

        /**
         * Update BLIK status.
         */
        updateStatus: function () {
            const urlResponse = url.build('bluepayment/processing/paymentstatus');
            const self = this;

            $.ajax({
                showLoader: false,
                url: urlResponse,
                type: 'GET',
                dataType: "json"
            }).done(function (response) {
                if (typeof response.Status !== 'undefined') {
                    self.handleStatus(response.Status, response);
                }
            });
        },
    });
});
