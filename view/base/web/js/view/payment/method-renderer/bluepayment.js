define([
        'jquery',
        'underscore',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'mage/url',
        'BlueMedia_BluePayment/js/model/quote',
        'BlueMedia_BluePayment/js/checkout-data'
    ], function ($,
                 _,
                 ko,
                 Component,
                 selectPaymentMethodAction,
                 url,
                 quote,
                 checkoutData) {
        'use strict';
        var widget;
        return Component.extend({
            renderSubOptions: window.checkoutConfig.payment.bluePaymentOptions,
            renderCardOptions: window.checkoutConfig.payment.bluePaymentCard,
            selectedPaymentObject: {},
            validationFailed: ko.observable(false),
            activeMethod: ko.computed(function () {
                if (checkoutData.getBlueMediaPaymentMethod() && quote.paymentMethod()) {
                    if (quote.paymentMethod().method === 'bluepayment') {
                        return checkoutData.getBlueMediaPaymentMethod().gateway_id;
                    }
                }
                return -1;
            }),
            initialize: function (config) {
                var self = this;
                widget = this;
                this._super();

                var blueMediaPayment = checkoutData.getBlueMediaPaymentMethod();
                if (blueMediaPayment && quote.paymentMethod()) {
                    if (quote.paymentMethod().method === 'bluepayment') {
                        this.selectedPaymentObject = blueMediaPayment;
                    }
                }

                ko.bindingHandlers.doSomething = {
                    update: function (element) {
                        var el = $(element).find('input.radio');
                        el.change(function () {
                            el.parent().removeClass('_active');
                            $(this).parent().addClass('_active');
                        });
                    }
                };
                ko.bindingHandlers.addActiveClass = {
                    init: function (element) {
                        var el = $(element);
                        var checkboxes = el.find('input');
                        checkboxes.each(function () {
                            if ($(this).is(':checked')) {
                                $(this).parent().addClass('_active');
                            }
                        });
                    }
                };
            },
            defaults: {
                template: 'BlueMedia_BluePayment/payment/bluepayment',
                logoUrl: window.checkoutConfig.payment.bluePaymentLogo || 'https://bm.pl/img/www/logos/bmLogo.png'
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
                this.selectedPaymentObject = value;

                quote.setBlueMediaPaymentMethod(value);
                checkoutData.setBlueMediaPaymentMethod(value);
            },
            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    "method": this.item.method,
                    "po_number": null,
                    "additional_data": null
                };
            },
            isChecked: ko.computed(function () {
                var paymentMethod = quote.paymentMethod();
                if (paymentMethod) {
                    return checkoutData.getIndividualGatewayFlag() ? false : paymentMethod.method;
                }
                return null;
            }),
            isCardChecked: function (context) {
                return ko.pureComputed(function () {
                    var paymentMethod = quote.paymentMethod();
                    var individualFlag = checkoutData.getIndividualGatewayFlag();
                    if (paymentMethod) {
                        if (individualFlag && paymentMethod.method == 'bluepayment') {
                            if (individualFlag == context.gateway_id) {
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
            redirectAfterPlaceOrder: false,
            /**
             * @return {Boolean}
             */
            validate: function () {
                if (_.isEmpty(this.selectedPaymentObject)) {
                    this.validationFailed(true);
                    return false;
                }
                return true;
            },
            afterPlaceOrder: function () {
                window.location.href = url.build('bluepayment/processing/create') + '?gateway_id=' + this.selectedPaymentObject.gateway_id;
            },
            inputIdPrefix: function () {
                return 'blue-payment';
            }
        });
    }
);
