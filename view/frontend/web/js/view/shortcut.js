define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'domReady!'
], function ($, Component, customerData, url, alert) {
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

        whenAvailable: function(name, callback) {
            var interval = 10;
            window.setTimeout(function() {
                if (window[name]) {
                    callback(window[name]);
                } else {
                    this.whenAvailable(name, callback);
                }
            }.bind(this), interval);
        },


        initAutopay: function () {
            let self = this,
                autopay,
                button,
                container = $('.' + this.selector + ' .autopay-button');

            this.whenAvailable('autopay', function() {
                autopay = new window.autopay.checkout({
                    merchantId: self.merchantId,
                    theme: 'dark',
                    language: 'en'
                });
                button = autopay.createButton();

                self.autopay = autopay;

                console.log('Autopay Init params', {
                    merchantId: self.merchantId,
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

                                self.setAutopayData();
                                resolve();
                            });

                            if (!self.productAddedToCart) {
                                // Clear whole cart
                                self.clearCart();

                                // Add to cart
                                self.addToCart();

                                if (! self.productAddedToCart) {
                                    reject('Product not added to cart');
                                }
                            } else {
                                // If already added - just set data and resolve promise.
                                self.setAutopayData();
                                resolve();
                            }
                        } else {
                            self.setAutopayData();
                            resolve();
                        }
                    });
                }

                container.append(button);

                // Prevent default action on APC button click - because we're manually executing addToCart function
                button.querySelector('.apc-btn').addEventListener('click', (event) => {
                    event.preventDefault();
                });
            });
        },

        addToCart: function () {
            const $form = $('.' + this.selector + ' .autopay-button').parents('form').first();
            $form.trigger('submit');

            if ($form.validation) {
                this.formInvalid = !$form.validation('isValid');

                if (! this.formInvalid) {
                    this.productAddedToCart = true;
                }
            } else {
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
                amount: cartData.grand_total,
                currency: cartData.currency,
                label: cartData.cart_id,
                productList: cartData.items,
            });

            this.autopay.setTransactionData({
                id: cartData.cart_id,
                amount: cartData.grand_total,
                currency: cartData.currency,
                label: cartData.cart_id,
                productList: cartData.items,
            });
        },

        clearCart: function () {
            $.ajax({
                url: url.build('checkout/cart/updatePost'),
                data: {
                    form_key: $('[name=form_key]').val(),
                    update_cart_action: 'empty_cart',
                },
                type: 'post',
                dataType: 'json',
                context: this,
                beforeSend: function () {
                    $(document.body).trigger('processStart');
                },
                complete: () => {
                    $(document.body).trigger('processStop');
                }
            })
                .done((response) => {
                    if (response.success) {
                        $(document).trigger('ajax:updateCartItemQty');

                        this.onSuccess();
                    } else {
                        console.log(response);
                        this.onError(response);
                    }
                }).fail((err) => {
                    console.warn(err.error);
                    console.log('Fail');
                });
        },

        onError: function (response) {
            console.error(response);

            // if (response['error_message']) {
            //
            //     alert({
            //         content: response['error_message'],
            //         actions: {
            //             /** @inheritdoc */
            //             always: function () {
            //                 that.submitForm();
            //             }
            //         }
            //     });
            // } else {
            //     this.submitForm();
            // }
        }
    });
});
