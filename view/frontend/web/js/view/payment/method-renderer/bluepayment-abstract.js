define([
    'jquery',
    'ko',
    'mage/url',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/redirect-on-success',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
    'BlueMedia_BluePayment/js/checkout-data',
], function (
    $,
    ko,
    url,
    Component,
    quote,
    additionalValidators,
    redirectOnSuccessAction,
    model,
    checkoutData,
) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: true,

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'separated': true,
                    'agreements_ids': model.getCheckedAgreementsIds()
                }
            };
        },

        /**
         * Custom validation for payment method.
         *
         * @return {Boolean}
         */
        validate: function () {
            if (! additionalValidators.validate()) {
                return false;
            }

            return true;
        },

        /**
         * Initialize view.
         *
         * @return {exports}
         */
        initialize: function () {
            this._super();

            return this;
        },

        /**
         * Select payment method.
         *
         * @returns {boolean}
         */
        selectPaymentMethod: function () {
            const data = this.getData();

            if (data.additional_data && data.additional_data.gateway_id) {
                model.selectedGatewayId(data.additional_data.gateway_id);
            } else {
                model.selectedGatewayId(null);
            }

            if (model.ordered()) {
                // It's needed to set payment method in quote, but without request to server.
                checkoutData.setSelectedPaymentMethod(this.item.method);

                if (data) {
                    data.__disableTmpl = {
                        title: true
                    };
                }
                quote.paymentMethod(data);

                return true;
            } else {
                return this._super();
            }
        },

        /**
         * Return state of place order button.
         *
         * @return {Boolean}
         */
        isButtonActive: function () {
            return this.isActive() && this.isPlaceOrderActionAllowed();
        },

        /**
         * Check if payment is active.
         *
         * @return {Boolean}
         */
        isActive: function () {
            return this.isChecked() === this.getId();
        },

        /**
         * Place order - with validation.
         */
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (this.validate() &&
                additionalValidators.validate()
            ) {
                this.placeOrderAfterValidation();
            }

            return false;
        },

        /**
         * Place order after validation.
         *
         * @param {Function} callback
         * @returns {boolean}
         */
        placeOrderAfterValidation: function (callback) {
            const self = this;

            if (!model.ordered()) {
                if (this.isPlaceOrderActionAllowed() === true) {
                    // Disable other payment types
                    $('.payment-method:not(.blue-payment) input[type=radio]').prop('disabled', true);

                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        })
                        .done(function () {
                            model.ordered(true);
                            self.afterPlaceOrder();

                            if (typeof callback == 'function') {
                                callback.call(this);
                            }

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        });

                    return true;
                } else {
                    console.warn('Place order action is not allowed.');
                }
            } else {
                // Order has been placed already.
                // Create only payment.
                self.afterPlaceOrder();

                if (self.redirectAfterPlaceOrder) {
                    redirectOnSuccessAction.execute();
                }

                if (typeof callback == 'function') {
                    callback.call(this);
                }
            }
        },

        /**
         * After place order callback.
         *
         * Set redirect url.
         *
         * @returns {void}
         */
        afterPlaceOrder: function () {
            const gatewayId = model.selectedGatewayId();
            let redirectUrl = url.build('bluepayment/processing/create');

            if (gatewayId) {
                redirectUrl += '?gateway_id=' + gatewayId;
            }

            redirectOnSuccessAction.redirectUrl = redirectUrl;
        },
    });
});
