define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/select-payment-method',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-selected-gateway',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-agreements',
    'BlueMedia_BluePayment/js/checkout-data',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    $,
    ko,
    Component,
    selectPaymentMethodAction,
    url,
    quote,
    selectedGateway,
    agreements,
    checkoutData,
    additionalValidators,
) {
    'use strict';

    let widget;

    return Component.extend({
        // Config from backend
        testMode: window.checkoutConfig.payment.bluepayment.test_mode,
        iframeEnabled: window.checkoutConfig.payment.bluepayment.iframe_enabled,

        ordered: false,
        redirectAfterPlaceOrder: false,
        validationFailed: ko.observable(false),

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'agreements_ids': agreements.getCheckedAgreementsIds()
                }
            };
        },

        initialize: function () {
            widget = this;
            this._super();

            const blueMediaPayment = checkoutData.getBlueMediaPaymentMethod();
            if (blueMediaPayment && quote.paymentMethod()) {
                if (quote.paymentMethod().method === 'bluepayment') {
                    selectedGateway(blueMediaPayment);
                }
            }

            // Refresh selected gateway
            checkoutData.setIndividualGatewayFlag('');
            this.setBlueMediaGatewayMethod({});
        },
        selectPaymentOption: function (value) {
            widget.setBlueMediaGatewayMethod(value);
            return true;
        },
        selectPaymentMethod: function () {
            this.item.individual_gateway = null;
            checkoutData.setIndividualGatewayFlag(this.item.individual_gateway);
            selectPaymentMethodAction(this.getData());
            checkoutData.setSelectedPaymentMethod(this.item.method);
            checkoutData.setIndividualGatewayFlag('');
            this.setBlueMediaGatewayMethod({});
            return true;
        },
        selectCardPaymentMethod: function () {
            selectPaymentMethodAction(this.getData());
            checkoutData.setSelectedPaymentMethod(this.item.method);
            return true;
        },
        selectPaymentMethodCard: function (cardContext) {
            this.item.individual_gateway = cardContext.gateway_id;
            checkoutData.setIndividualGatewayFlag(this.item.individual_gateway);

            this.setBlueMediaGatewayMethod(cardContext);
            this.selectCardPaymentMethod();

            return true;
        },
        setBlueMediaGatewayMethod: function (value) {
            this.validationFailed(false);
            selectedGateway(value);
            checkoutData.setBlueMediaPaymentMethod(value);
        },
        isSeparatedChecked: function (gateway_id) {
            return ko.pureComputed(function () {
                const paymentMethod = quote.paymentMethod();
                const individualFlag = checkoutData.getIndividualGatewayFlag();
                if (paymentMethod) {
                    if (individualFlag && paymentMethod.method === 'bluepayment') {
                        if (individualFlag.toString() === gateway_id.toString()) {
                            return individualFlag;
                        }
                        return false;
                    } else {
                        return false;
                    }
                }

                return null;
            });
        },

        /**
         * @return {Boolean}
         */
        validate: function () {
            return additionalValidators.validate(false);
        },

        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (this.validate()) {
                this.placeOrderAfterValidation();
            }

            return false;
        },
        placeOrderAfterValidation: function (callback) {
            const self = this;

            if (!this.ordered) {
                // Disable other payment types
                $('.payment-method:not(.blue-payment) input[type=radio]').prop('disabled', true);

                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).done(function () {
                        self.ordered = true;
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
                // Order has been placed already.
                // Create only payment.
                self.afterPlaceOrder();

                if (self.redirectAfterPlaceOrder) {
                    redirectOnSuccessAction.execute();
                }

                callback.call(this);
            }
        },
        afterPlaceOrder: function () {
            window.location.href =
                url.build('bluepayment/processing/create')
                + '?gateway_id=' + selectedGateway().gateway_id;
        },
    });
});
