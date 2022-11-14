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
        apcShortcut: null,

        afterRender: function () {
            let autopayComponent = autopay.bind(null, this.getApcComponentProps(), $('.autopay-shortcut'));

            setTimeout(() => {
                this.apcShortcut = autopayComponent();
            });
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

        validate: function () {
            if (!this.apcShortcut) {
                this.apcShortcut = autopay(this.getApcComponentProps());

                this.apcShortcut.initAutopay(false)
                    .then(this.manuallyInit.bind(this));
            } else {
                this.manuallyInit();
            }
        },

        manuallyInit: function () {
            if (this.apcShortcut && this.apcShortcut.autopay) {
                /** @var {Promise} response */
                const response = this.apcShortcut.autopay.onBeforeCheckout();

                response.then((result) => {
                    this.apcShortcut.autopay.runCheckout();
                });
            }
        },

        getApcComponentProps: function () {
            return {
                isInCatalogProduct: false,
                selector: "autopay-shortcut",
                merchantId: this.getMerchantId(),
                language: this.getLanguage()
            };
        }
    });
});
