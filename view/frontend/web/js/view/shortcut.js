define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data'
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
            let self = this,
                autopay = new window.autopay.checkout({
                    merchantId: this.merchantId,
                    language: 'pl'
                }),
                button = autopay.createButton(),
                container = $('.' + this.selector + ' .autopay-button');

            console.log('Autopay Init params', {
                merchantId: this.merchantId,
                language: 'pl'
            });

            autopay.onBeforeCheckout = () => {
                if (self.isCatalogProduct()) {
                    self.addToCart();
                }

                customerData.reload(['cart'], false);
                let cartData = customerData.get('cart')();

                console.log({
                    id: cartData.cart_id,
                    amount: cartData.subtotalAmount,
                    currency: cartData.currency,
                    label: cartData.cart_id,
                    productList: cartData.items,
                });

                autopay.setTransactionData({
                    id: cartData.cart_id,
                    amount: cartData.subtotalAmount,
                    currency: cartData.currency,
                    label: cartData.cart_id,
                    productList: cartData.items,
                });
            }

            container.append(button);
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
