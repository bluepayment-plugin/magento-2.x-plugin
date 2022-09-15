define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'autopayShortcut'
], function ($, Component, autopay) {
    'use strict';

    return Component.extend({
        defaults: {
            code: 'autopay',
            template: 'BlueMedia_BluePayment/payment/autopay',
        },

        afterRender: function () {
            console.log('Init APC component', {
                isInCatalogProduct: false,
                selector: "autopay-shortcut",
                merchantId: this.merchantId,
                language: this.language
            });

            let autopayComponent = autopay.bind(null, {
                isInCatalogProduct: false,
                selector: "autopay-shortcut",
                merchantId: this.getMerchantId(),
                language: this.getLanguage()
            }, $('.autopay-shortcut'));

            setTimeout(autopayComponent);
        },

        getConfig: function () {
            return window.checkoutConfig.payment[this.getCode()];
        },

        getLogoSrc: function () {
            return this.getConfig().logoSrc;
        },

        getMerchantId: function () {
            return this.getConfig().merchantId;
        },

        getLanguage: function () {
            return this.getConfig().language;
        },

        isButtonEnabled: function () {
            return this.getCode() === this.isChecked() && this.isPlaceOrderActionAllowed();
        },
    });
});
