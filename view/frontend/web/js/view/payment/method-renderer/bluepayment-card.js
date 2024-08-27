define([
    'jquery',
    'ko',
    'mage/url',
    'mage/translate',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
], function (
    $,
    ko,
    url,
    $t,
    Component,
    config,
) {
    'use strict';

    let cardRedirectUrl = null;

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-card',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,

            iframeEnabled: config.iframeEnabled,
        },
        redirectAfterPlaceOrder: !config.iframeEnabled,

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();

            if (typeof PayBmCheckout !== 'undefined') {
                PayBmCheckout.transactionSuccess = function (status) {
                    window.location.href = cardRedirectUrl;
                };
            }
        },

        /**
         * Get gateway description.
         *
         * @returns {string}
         */
        getGatewayDescription: function () {
            if (this.iframeEnabled) {
                return $t("Pay with your credit or debit card.");
            }

            return $t("You will be redirected to our partner Autopay's website, where you will enter your card details.");
        },

        /**
         * After place order callback.
         *
         * @returns {boolean}
         */
        afterPlaceOrder: function () {
            if (this.iframeEnabled) {
                this.callIframePayment();
                return false;
            } else {
                return this._super();
            }
        },

        /**
         * Prepare URL for iframe
         *
         * @returns {string}
         */
        prepareIframeUrl: function () {
            return url.build('bluepayment/processing/create')
                + '?gateway_id=' + this.gateway_id
                + '&automatic=true';
        },

        /**
         * Call iframe payment
         *
         * @returns {boolean}
         */
        callIframePayment: function () {
            const urlCreate = this.prepareIframeUrl();

            $.ajax({
                showLoader: true,
                url: urlCreate,
                type: "GET",
                dataType: "json",
            }).done(function (response) {
                cardRedirectUrl = url.build('bluepayment/processing/back')
                    + '?ServiceID=' + response.params.ServiceID
                    + '&OrderID=' + response.params.OrderID
                    + '&Hash=' + response.redirectHash;

                PayBmCheckout.actionURL = response.gateway_url;
                PayBmCheckout.transactionStartByParams(response.params);
            });

            return false;
        },
    });
});
