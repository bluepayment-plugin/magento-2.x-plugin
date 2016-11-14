define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function ($, Component, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'BlueMedia_BluePayment/payment/bluepayment'
            },
            redirectAfterPlaceOrder: false,
            afterPlaceOrder: function () {
                console.log('BM AFTER PLACE ORDER');
                this.redirectToBM();
            },
            redirectToBM: function () {
                window.location.href = url.build('bluepayment/processing/create');
            }
        });
    }
);
