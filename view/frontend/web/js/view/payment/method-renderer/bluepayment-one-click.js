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

            iframeEnabled: config.iframeEnabled,
            cards: config.cards,
            oneClickAgreement: config.oneClickAgreement,
        },
        selectedCard: ko.observable(-1),
        redirectAfterPlaceOrder: !config.iframeEnabled,

        /**
         * After place order callback.
         *
         * @returns {boolean}
         */
        afterPlaceOrder: function () {
            if (this.iframeEnabled && this.selectedCard() == -1) {
                this.callIframePayment();
                return false;
            } else {
                redirectOnSuccessAction.redirectUrl = url.build('bluepayment/processing/create')
                    + '?gateway_id=' + this.gateway_id
                    + '&card_index=' + this.selectedCard();

                this.redirectAfterPlaceOrder = true;
            }
        },

        /**
         * Select card
         *
         * @param card
         * @returns {boolean}
         */
        selectCard: function (card) {
            this.selectedCard(card.index);
            return true;
        },

        /**
         * Prepare URL for iframe
         *
         * @returns {string}
         */
        prepareIframeUrl: function () {
            return url.build('bluepayment/processing/create')
                + '?gateway_id=' + this.gateway_id
                + '&automatic=true'
                + '&card_index=' + this.selectedCard();
        },

        /**
         * @return {Boolean}
         */
        validate: function () {
            // One click agreement
            const cardIndex = this.selectedCard();

            if (cardIndex !== undefined) {
                checkoutData.setCardIndex(cardIndex);

                if (cardIndex == -1 && !$('#bluepayment-one-click-agreement').is(':checked')) {
                    this.messageContainer.addErrorMessage({message: $t('You have to agree with terms.')});

                    return false;
                }
            } else {
                this.messageContainer.addErrorMessage({message: $t('You have to select card.')});

                return false;
            }

            return true;
        },
    });
});
