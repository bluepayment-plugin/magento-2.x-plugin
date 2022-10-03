define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'Magento_Ui/js/modal/alert'
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

        isCatalogProduct: function() {
            return Boolean(this.isInCatalogProduct);
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
            var self = this,
                autopay,
                button,
                container = $('.' + this.selector + ' .autopay-button');

            this.whenAvailable('autopay', function() {
                self.log('Init params', {
                    merchantId: self.merchantId,
                    theme: 'dark',
                    language: self.language
                });

                autopay = new window.autopay.checkout({
                    merchantId: self.merchantId,
                    theme: 'dark',
                    language: self.language
                });
                button = autopay.createButton();

                self.autopay = autopay;

                self.onRemoveFromCartListener();

                autopay.onBeforeCheckout = () => {
                    self.log('onBeforeCheckout executed');

                    return new Promise((resolve, reject) => {
                        if (self.isCatalogProduct()) {
                            if (!self.productAddedToCart) {
                                $(document).one('autopay:cart-cleared', () => {
                                    self.log('Cart cleared event');

                                    // After clear Cart
                                    self.addToCart();
                                });

                                $(document).one('ajax:addToCart', () => {
                                    // After add to cart
                                    self.log('addToCart event');

                                    customerData.reload(['cart'], true);

                                    if (! self.productAddedToCart) {
                                        reject('Product not added to cart');
                                    } else {
                                        $(document).one('customer-data-reloaded', () => {
                                            self.log('customer-data-reloaded event');

                                            // After customerData reloaded
                                            self.setAutopayData(resolve, reject);
                                        });
                                    }
                                });

                                // Clear whole cart
                                self.clearCart(reject);
                            } else {
                                self.log('Product already added to cart');

                                // If already added - just set data and resolve promise.
                                self.setAutopayData(resolve, reject);
                            }
                        } else {
                            self.log('Not catalog product');

                            self.setAutopayData(resolve, reject);
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
                    this.log('Form is valid');

                    this.productAddedToCart = true;
                } else {
                    this.log('Form is invalid');
                }
            } else {
                this.log('Form validation is not available');

                this.productAddedToCart = true;
            }
        },

        setAutopayData: function (resolve, reject) {
            var cartData = customerData.get('cart')();
            var minimumOrderConfig = this.minimumOrderConfiguration;

            // Validate minimum order amount
            if (minimumOrderConfig.active) {
                var tax = minimumOrderConfig.includingTax ? cartData.tax_amount : 0;
                var amountToCompare = cartData.includingDiscount
                    ? cartData.base_subtotal_with_discount
                    : cartData.base_subtotal;

                if (amountToCompare + tax < minimumOrderConfig.amount) {
                    var text = (minimumOrderConfig.text)
                        ? minimumOrderConfig.text
                        : 'Minimalna wartość zamówienia to ' + minimumOrderConfig.amount;

                    alert({
                        content: text
                    });

                    reject();
                    return;
                }
            }

            var data = {
                id: cartData.cart_id,
                amount: parseFloat(cartData['grand_total']),
                currency: cartData.currency,
                label: cartData.cart_id,
                productList: cartData.items,
            };
:wq
            this.log('SetTransactionData', data);
            this.autopay.setTransactionData(data);

            resolve();
        },

        clearCart: function (reject) {
            const self = this;

            self.log('Clear cart started');

            $.ajax({
                url: url.build('checkout/cart/updatePost'),
                data: {
                    form_key: $('[name=form_key]').val(),
                    update_cart_action: 'empty_cart',
                },
                type: 'post',
                context: this,
                beforeSend: function () {
                    $(document.body).trigger('processStart');
                },
                complete: () => {
                    $(document.body).trigger('processStop');
                }
            })
                .done((response) => {
                    self.log('Cart cleared');

                    $(document).trigger('autopay:cart-cleared');
                })
                .fail((response) => {
                    self.log('Cart clear failed', response);

                    reject('Unable to clear cart...');
                });
        },

        onRemoveFromCartListener: function () {
            const self = this;

            $(document).on('ajax:removeFromCart', function (event, data) {
                self.log('Remove from cart event');
                self.productAddedToCart = false;
            });
        },

        log: function (message, object = null) {
            message = '[AutoPay]' + this.formatConsoleDate(new Date()) + message;

            console.log(message, object);
        },

        formatConsoleDate: (date) => {
            var hour = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();
            var milliseconds = date.getMilliseconds();

            return '[' +
                ((hour < 10) ? '0' + hour: hour) +
                ':' +
                ((minutes < 10) ? '0' + minutes: minutes) +
                ':' +
                ((seconds < 10) ? '0' + seconds: seconds) +
                '.' +
                ('00' + milliseconds).slice(-3) +
                '] ';
        }
    });
});
