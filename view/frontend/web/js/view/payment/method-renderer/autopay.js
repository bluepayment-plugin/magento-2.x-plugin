define([
     'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            code: 'autopay',
            template: 'BlueMedia_BluePayment/payment/autopay',
        },

        getLogoSrc: function () {
            return window.checkoutConfig.payment[this.getCode()].logoSrc;
        },

        isButtonEnabled: function () {
            return this.getCode() === this.isChecked() && this.isPlaceOrderActionAllowed();
        },
    });
});
