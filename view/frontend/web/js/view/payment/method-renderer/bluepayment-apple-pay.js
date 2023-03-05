define([
    'ko',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated',
], function (
    ko,
    Component,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-apple-pay',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,
        },

        /**
         * Check if Apple Pay is available.
         *
         * @returns {boolean}
         */
        isAvailable: function () {
            try {
                return window.ApplePaySession && ApplePaySession.canMakePayments();
            } catch (e) {
            }

            return false;
        },
    });
});
