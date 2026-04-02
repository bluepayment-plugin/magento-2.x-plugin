define([
    'jquery',
    'ko',
    'mage/url',
    'mage/translate',
    'Magento_Checkout/js/action/redirect-on-success',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-card',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
    'BlueMedia_BluePayment/js/checkout-data',
], function (
    $,
    ko,
    url,
    $t,
    redirectOnSuccessAction,
    Component,
    config,
    checkoutData,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-one-click',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,

            cards: config.cards,
            oneClickAgreement: config.oneClickAgreement,
        },
        selectedCard: ko.observable(null),
        redirectAfterPlaceOrder: false,

        initialize: function () {
            this._super();

            this.selectedCard.subscribe(function (selectedCard) {
                if (Number(selectedCard) === -1) {
                    if (this.isWidgetEnabled()) {
                        this.ensureWidgetInitialized();
                    }
                } else {
                    this.resetWidget();
                }
            }.bind(this));

            return this;
        },

        afterPlaceOrder: function () {
            if (!this.hasSelectedCard()) {
                this.messageContainer.addErrorMessage({message: $t('You have to select card.')});
                return false;
            }

            if (Number(this.selectedCard()) === -1) {
                if (!this.isWidgetEnabled()) {
                    redirectOnSuccessAction.redirectUrl = url.build('bluepayment/processing/create')
                        + '?gateway_id=' + this.gateway_id
                        + '&card_index=-1';

                    this.redirectAfterPlaceOrder = true;
                    return true;
                }

                this.redirectAfterPlaceOrder = false;
                this.callWidgetPayment();
                return false;
            }

            redirectOnSuccessAction.redirectUrl = url.build('bluepayment/processing/create')
                + '?gateway_id=' + this.gateway_id
                + '&card_index=' + this.selectedCard();

            this.redirectAfterPlaceOrder = true;
            return true;
        },

        isWidgetEnabled: function () {
            return Boolean(config.iframeEnabled);
        },

        selectCard: function (card) {
            this.selectedCard(card.index);
            return true;
        },

        getWidgetRecurringAction: function () {
            return 'INIT_WITH_PAYMENT';
        },

        getWidgetCreatePayload: function (paymentToken) {
            return {
                gateway_id: this.gateway_id,
                automatic: true,
                token: paymentToken,
                card_index: this.selectedCard(),
                form_key: window.FORM_KEY,
            };
        },

        hasSelectedCard: function () {
            const selectedCard = this.selectedCard();
            return selectedCard !== undefined && selectedCard !== null && selectedCard !== '';
        },

        validate: function () {
            const cardIndex = this.selectedCard();

            if (!this.hasSelectedCard()) {
                this.messageContainer.addErrorMessage({message: $t('You have to select card.')});
                return false;
            }

            checkoutData.setCardIndex(cardIndex);

            if (Number(cardIndex) === -1 && !$('#bluepayment-one-click-agreement').is(':checked')) {
                this.messageContainer.addErrorMessage({message: $t('You have to agree with terms.')});

                return false;
            }

            return true;
        },
    });
});
