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

        initAutopay: function (withButton = true) {
            var button,
                container = $('.' + this.selector + ' .autopay-button');

            return new Promise((resolve, reject) => {
                this.whenAvailable('autopay', () => {
                    const initParams = {
                        merchantId: this.merchantId,
                        language: this.language
                    };

                    this.log('Init params', initParams);

                    this.autopay = new window.autopay.checkout(initParams);
                    this.autopay.onBeforeCheckout = this.onBeforeCheckout.bind(this);
                    if (withButton) {
                        let theme = this.style.theme;
                        if (['dark', 'light', 'orange', 'gradient'].indexOf(theme) === -1) {
                            theme = 'dark';
                        }

                        const buttonParams = {
                            theme: theme,
                            fullWidth: this.style.width === 'full' ? true : false,
                            rounded: this.style.rounded === 'rounded' ? true : false
                        }

                        this.log('Button params', buttonParams);

                        button = this.autopay.createButton(buttonParams);
                        container.append(button);
                    }

                    this.onRemoveFromCartListener();
                    resolve();
                });
            });
        },

        onBeforeCheckout: function () {
            this.log('onBeforeCheckout executed');

            return new Promise((resolve, reject) => {
                if (this.isCatalogProduct()) {
                    if (!this.productAddedToCart) {
                        $(document).one('autopay:cart-cleared', () => {
                            this.log('Cart cleared event');

                            // After clear Cart
                            this.addToCart(reject);
                        });

                        $(document).one('ajax:addToCart', () => {
                            // After add to cart
                            this.log('addToCart event');

                            customerData.reload(['cart'], true);

                            if (!this.productAddedToCart) {
                                reject('Product not added to cart');
                            } else {
                                $(document).one('customer-data-reloaded', () => {
                                    this.log('customer-data-reloaded event');

                                    // After customerData reloaded
                                    this.setAutopayData(resolve, reject);
                                });
                            }
                        });

                        // Clear whole cart
                        this.clearCart(reject);
                    } else {
                        this.log('Product already added to cart');

                        // If already added - just set data and resolve promise.
                        this.setAutopayData(resolve, reject);
                    }
                } else {
                    this.log('Not catalog product');

                    this.setAutopayData(resolve, reject);
                }
            });
        },

        addToCart: function (reject) {
            const $form = $('.' + this.selector + ' .autopay-button').parents('form').first();
            $form.trigger('submit');

            if ($form.validation) {
                this.formInvalid = !$form.validation('isValid');

                if (! this.formInvalid) {
                    this.productAddedToCart = true;

                    this.log('Form is valid');
                } else {
                    reject('Form is invalid');

                    this.log('Form is invalid');
                }
            } else {
                this.log('Form validation is not available');
                this.productAddedToCart = true;
            }
        },

        setAutopayData: function (resolve, reject) {
            const cartData = customerData.get('cart')();
            const minimumOrderConfig = this.minimumOrderConfiguration;

            this.log('Minimum order config', minimumOrderConfig);

            // Validate minimum order amount
            if (minimumOrderConfig && minimumOrderConfig.active) {
                const tax = minimumOrderConfig.includingTax ? cartData.tax_amount : 0;
                const amountToCompare = cartData.includingDiscount
                    ? cartData.base_subtotal_with_discount
                    : cartData.base_subtotal;

                if (amountToCompare + tax < minimumOrderConfig.amount) {
                    const text = (minimumOrderConfig.text)
                        ? minimumOrderConfig.text
                        : 'Minimalna wartość zamówienia to ' + minimumOrderConfig.amount;

                    alert({
                        content: text
                    });

                    this.productAddedToCart = false;
                    reject();
                    return;
                }
            }

            const data = {
                id: cartData.cart_id,
                amount: this.calculateTotalWithoutShipping(cartData),
                currency: cartData.currency,
                label: cartData.cart_id,
                productList: cartData.items,
            };

            this.log('SetTransactionData', data);
            this.autopay.setTransactionData(data);

            resolve();
        },

        calculateTotalWithoutShipping: function (cartData) {
            let totalWithoutShipping = parseFloat(cartData.grand_total);
            let shippingInclTax = parseFloat(cartData.shipping_incl_tax);

            if (shippingInclTax && ! isNaN(shippingInclTax)) {
                totalWithoutShipping -= shippingInclTax;
            }

            return totalWithoutShipping.toFixed(2);
        },

        clearCart: function (reject) {
            this.log('Clear cart started');

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
                    this.log('Cart cleared');

                    $(document).trigger('autopay:cart-cleared');
                })
                .fail((response) => {
                    this.log('Cart clear failed', response);

                    reject('Unable to clear cart...');
                });
        },

        onRemoveFromCartListener: function () {
            $(document).on('ajax:removeFromCart', function (event, data) {
                this.log('Remove from cart event');
                this.productAddedToCart = false;
            });
        },

        log: function (message, object = null) {
            message = '[Autopay]' + this.formatConsoleDate(new Date()) + message;

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
