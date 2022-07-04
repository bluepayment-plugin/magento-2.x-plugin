define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'domReady!'
], function ($, Component, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            formInvalid: false,
            productAddedToCart: false
        },

        autopay: false,

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
                    theme: 'dark',
                    language: 'en'
                }),
                button = autopay.createButton(),
                container = $('.' + this.selector + ' .autopay-button');

            this.autopay = autopay;

            console.log('Autopay Init params', {
                merchantId: this.merchantId,
                theme: 'dark',
                language: 'en'
            });

            autopay.onBeforeCheckout = () => {
                return new Promise((resolve, reject) => {
                    if (self.isCatalogProduct()) {
                        $(document).one('ajax:addToCart', () => {
                            console.log('addToCart event');

                            customerData.reload(['cart'], true);
                        });

                        $(document).one('customer-data-reloaded', () => {
                            console.log('customer-data-reloaded event');

                            this.setAutopayData();
                            resolve();
                        });

                        self.addToCart();
                    } else {
                        this.setAutopayData();
                        resolve();
                    }
                });
            }

            container.append(button);

            // Prevent default action on APC button click - because we're manually executing addToCart function
            button.querySelector('.apc-btn').addEventListener('click', (event) => {
                event.preventDefault();
            });
        },

        addToCart: function () {
            var $form = $('.' + this.selector + ' .autopay-button').parents('form').first();

            if (!this.productAddedToCart) {
                $form.trigger('submit');

                if ($form.validation) {
                    this.formInvalid = !$form.validation('isValid');
                }

                this.productAddedToCart = true;
            }
        },

        isCatalogProduct: function() {
            return Boolean(this.isInCatalogProduct);
        },

        setAutopayData: function () {
            let cartData = customerData.get('cart')();

            console.log({
                id: cartData.cart_id,
                amount: cartData.subtotalAmount,
                currency: cartData.currency,
                label: cartData.cart_id,
                productList: cartData.items,
            });

            this.autopay.setTransactionData({
                id: cartData.cart_id,
                amount: cartData.subtotalAmount,
                currency: cartData.currency,
                label: cartData.cart_id,
                productList: cartData.items,
            });
        }
    });
});
