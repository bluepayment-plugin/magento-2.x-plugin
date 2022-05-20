define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'autopaySDK'
], function ($, Component, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            productFormSelector: '#product_addtocart_form',
            formInvalid: false,
            productAddedToCart: false
        },

        /**
         * @return {exports}
         */
        initialize: function () {
            this._super();

            this.initAutopay();
            return this;
        },

        initAutopay: function () {
            let self = this;
            let autopay = new window.autopay.checkout({
                button: {
                    element: '.autopay-button'
                },
                language: 'pl'
            });

            autopay.onBeforeCheckout = () => {
                if (self.isCatalogProduct()) {
                    self.addToCart();
                }

                let cartData = customerData.get('cart')();

                console.log({
                    order: cartData.cartId,
                    amount: cartData.subtotalAmount,
                    currency: cartData.currency,
                    productList: cartData.items,
                });

                autopay.setTransactionData({
                    order: cartData.cartId,
                    amount: cartData.subtotalAmount,
                    currency: cartData.currency,
                    productList: cartData.items,
                });
            }
        },

        addToCart: function () {
            var $form = $(this.productFormSelector);

            if (!this.productAddedToCart) {
                $form.trigger('submit');
                this.formInvalid = !$form.validation('isValid');
                this.productAddedToCart = true;
            }
        },

        isCatalogProduct: function() {
            return Boolean(this.isInCatalogProduct);
        },
    });
});
