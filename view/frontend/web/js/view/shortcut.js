define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/modal/alert'
], function (
    $,
    Component,
    url,
    storage,
    customerData,
    customer,
    alert
) {
    'use strict';

    return Component.extend({
        defaults: {
            formInvalid: false,
            productAddedToCart: false,
        },

        autopay: false,
        allowedThemes: ['dark', 'light', 'orange', 'gradient'],
        allowedArrangements: ['horizontal', 'horizontal-reversed', 'vertical', 'vertical-reversed'],

        /**
         * @return {exports}
         */
        initialize: function () {
            this._super();

            this.initAutopay();
            return this;
        },

        /**
         * Returns whether shortcut is run from catalog product page.
         * @returns {boolean}
         */
        isCatalogProduct: function () {
            return this.scope === 'product';
        },

        /**
         * Wait until SDK will be available.
         * @param name
         * @param callback
         */
        whenAvailable: function (name, callback) {
            const interval = 10; // ms
            window.setTimeout(function () {
                if (window[name]) {
                    callback(window[name]);
                } else {
                    this.whenAvailable(name, callback);
                }
            }.bind(this), interval);
        },

        /**
         * Init Autopay button.
         * @param withButton
         * @returns {Promise<unknown>}
         */
        initAutopay: function (withButton = true) {
            let button,
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
                        if (this.allowedThemes.indexOf(theme) === -1) {
                            theme = 'dark';
                        }

                        let arrangement = this.style.arrangement;
                        if (this.allowedArrangements.indexOf(arrangement) === -1) {
                            arrangement = 'vertical';
                        }

                        const buttonParams = {
                            theme: theme,
                            fullWidth: this.style.width === 'full',
                            rounded: this.style.rounded === 'rounded',
                            arrangement: arrangement,
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

        /**
         * Before checkout event (from Autopay SDK).
         * @returns {Promise<unknown>}
         */
        onBeforeCheckout: function () {
            this.log('onBeforeCheckout executed');

            return new Promise((resolve, reject) => {
                if (this.isCatalogProduct()) {
                    if (!this.productAddedToCart) {
                        // Clear whole cart
                        this.clearCart(reject);

                        // After clear cart
                        $(document).one('autopay:cart-cleared', () => {
                            this.log('Cart cleared event');
                            this.addToCart(reject);
                        });

                        // After add to cart
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
                                    this.setAutopay(resolve, reject);
                                });
                            }
                        });
                    } else {
                        this.log('Product already added to cart');

                        // If already added - just set data and resolve promise.
                        this.setAutopay(resolve, reject);
                    }
                } else {
                    this.log('Not catalog product');

                    this.setAutopay(resolve, reject);
                }
            });
        },

        /**
         * Add product to cart.
         * @param reject
         */
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

        /**
         * Set payment method to Autopay and set cart data in Autopay SDK.
         * @param resolve
         * @param reject
         */
        setAutopay: function (resolve, reject) {
            this.setPaymentMethod()
                .then((result) => {
                    if (result) {
                        this.setAutopayData(resolve, reject);
                    } else {
                        reject('Error during setPaymentMethod', result);
                    }
                })
                .catch((err) => {
                    reject(err);
                });
        },

        /**
         * Send transaction data to Autopay SDK.
         * @param resolve
         * @param reject
         */
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

        /**
         * Calculate total amount without shipping.
         * @param cartData
         * @returns {string}
         */
        calculateTotalWithoutShipping: function (cartData) {
            console.log(cartData);
            let totalWithoutShipping = parseFloat(cartData.grand_total);
            let shippingInclTax = parseFloat(cartData.shipping_incl_tax);

            if (shippingInclTax && ! isNaN(shippingInclTax)) {
                totalWithoutShipping -= shippingInclTax;
            }

            return totalWithoutShipping.toFixed(2);
        },

        /**
         * Remove all products from cart.
         * @param reject
         */
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

        /**
         * Listen to event when cart is cleared.
         */
        onRemoveFromCartListener: function () {
            $(document).on('ajax:removeFromCart', function (event, data) {
                this.log('Remove from cart event');
                this.productAddedToCart = false;
            });
        },

        /**
         * Set AutoPay as payment method.
         * @returns {Promise<boolean>}
         */
        setPaymentMethod: function () {
            return new Promise((resolve, reject) => {
                storage.post(
                    '/bluepayment/autopay/setpaymentmethod',
                    {},
                    true,
                    'application/json',
                    {}
                ).done((response) => {
                    if (response.success === false) {
                        this.log('Set payment method failed', response);
                        reject('Set payment method failed');
                    } else {
                        this.log('Set payment method success', response);
                        resolve(true);
                    }
                }).fail((response) => {
                    this.log('Set payment method failed', response);
                    reject('Set payment method failed');
                })
            })
        },

        /**
         * Log message to console.
         * @param message
         * @param object
         */
        log: function (message, object = null) {
            message = '[Autopay]' + this.formatConsoleDate(new Date()) + message;

            console.log(message, object);
        },

        /**
         * Pretty print date.
         * @param date
         * @returns {string}
         */
        formatConsoleDate: (date) => {
            const hour = date.getHours();
            const minutes = date.getMinutes();
            const seconds = date.getSeconds();
            const milliseconds = date.getMilliseconds();

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
