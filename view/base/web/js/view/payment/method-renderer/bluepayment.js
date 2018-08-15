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
        var redirectUrl;
        return Component.extend({
            renderSubOptions: window.checkoutConfig.payment.bluePaymentOptions,
            renderCardOptions: window.checkoutConfig.payment.bluePaymentCard,
            renderAutomaticPayment: window.checkoutConfig.payment.bluePaymentAutomatic,
            renderBlikPayment: window.checkoutConfig.payment.bluePaymentBlik,
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

                PayBmCheckout.transactionSuccess = function(status) {
                    window.location.href = redirectUrl;
                };

                PayBmCheckout.transactionDeclined = function(status) {
                    window.location.href = redirectUrl;
                };

                PayBmCheckout.transactionError = function(status) {
                    window.location.href = redirectUrl;
                };
            },
            defaults: {
                template: 'BlueMedia_BluePayment/payment/bluepayment',
                logoUrl: window.checkoutConfig.payment.bluePaymentLogo || 'https://bm.pl/img/www/logos/bmLogo.png',
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

                var $automaticRadio = $("input.payment_method_bluepayment_automatic"),
                    $blikRadio = $("input.payment_method_bluepayment_blik"),
                    isCheckedAutomatic = $("input.payment_method_bluepayment_automatic").is(':checked'),
                    isCheckedBlik = $("input.payment_method_bluepayment_blik").is(':checked');

                if (isCheckedAutomatic) {
                    $automaticRadio.attr("checked", false);
                }

                if (isCheckedBlik) {
                    $blikRadio.attr("checked", false);
                }

                $(this).attr("checked", true);
                $('.blue-payment').find('.payment-method-content').show();
                $('.blue-payment__automatic').find('.payment-method-content').hide();
                $('.blue-payment__blik').find('.payment-method-content').hide();

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
            isAutomaticChecked: function (context) {
                return ko.pureComputed(function () {
                    var paymentMethod = quote.paymentMethod();
                    if (paymentMethod) {
                        return true;
                    }
                    return null;
                });
            },
            selectAutomaticMethod: function (value) {
                $('#checkout-payment-method-load input[type=radio]').each( function() {
                    var $this = $(this),
                        $paymentMethod = $this.parent().parent(),
                        billingContent = $paymentMethod.find('.payment-method-content');

                    if ($paymentMethod.hasClass('_active')) {
                        $paymentMethod.removeClass('_active');
                    }

                    if ($paymentMethod.hasClass('blue-payment') || $paymentMethod.hasClass('blue-payment__blik')) {
                        $this.attr("checked", false);
                        billingContent.hide();
                    }
                });

                $(this).attr("checked", true);
                $('.blue-payment__automatic').find('.payment-method-content').show();

                // widget.selectCardPaymentMethod();
                widget.setBlueMediaGatewayMethod(value);

                return true;
            },
            selectBlikMethod: function (value) {
                $('#checkout-payment-method-load input[type=radio]').each( function() {
                    var $this = $(this),
                        $paymentMethod = $this.parent().parent(),
                        billingContent = $paymentMethod.find('.payment-method-content');

                    if ($paymentMethod.hasClass('_active')) {
                        $paymentMethod.removeClass('_active');
                    }

                    if ($paymentMethod.hasClass('blue-payment') || $paymentMethod.hasClass('blue-payment__automatic')) {
                        $this.attr("checked", false);
                        billingContent.hide();
                    }
                });

                $(this).attr("checked", true);
                $('.blue-payment__blik').find('.payment-method-content').show();

                // widget.selectCardPaymentMethod();
                widget.setBlueMediaGatewayMethod(value);

                return true;
            },
            redirectAfterPlaceOrder: false,
            /**
             * @return {Boolean}
             */
            validate: function () {
                $('.blik-error').hide();

                if (_.isEmpty(this.selectedPaymentObject)) {
                    this.validationFailed(true);
                    return false;
                }

                if (this.renderBlikPayment[0].gateway_id == this.selectedPaymentObject.gateway_id) {
                    var code = $(".blue-payment__blik input[name='payment_method_bluepayment_code']").val();
                    if (code.length !== 6) {
                        $('.blik-error').show();
                        return false;
                    }
                }

                return true;
            },
            afterPlaceOrder: function () {

                if (this.renderAutomaticPayment[0].gateway_id == this.selectedPaymentObject.gateway_id) {
                    this.callIframePayment();

                    return false;
                }

                if (this.renderBlikPayment[0].gateway_id == this.selectedPaymentObject.gateway_id) {
                    this.callBlikPayment();

                    return false;
                }

                window.location.href = url.build('bluepayment/processing/create') + '?gateway_id=' + this.selectedPaymentObject.gateway_id;
            },
            inputIdPrefix: function () {
                return 'blue-payment';
            },
            callIframePayment: function() {
                var urlResponse = url.build('bluepayment/processing/create')
                    + '?gateway_id='
                    + this.selectedPaymentObject.gateway_id
                    + '&automatic=true';

                $.ajax({
                    showLoader: true,
                    url: urlResponse,
                    type: "GET",
                    dataType: "json",
                }).done(function (response) {
                    redirectUrl = url.build('bluepayment/processing/back') + '?ServiceID=' + response.params.ServiceID + '&OrderID=' + response.params.OrderID + '&Hash=' + response.redirectHash;

                    PayBmCheckout.actionURL = response.gateway_url;
                    PayBmCheckout.transactionStartByParams(response.params);
                });

                return false;
            },
            callBlikPayment: function() {
                var urlResponse = url.build('bluepayment/processing/create')
                    + '?gateway_id='
                    + this.selectedPaymentObject.gateway_id
                    + '&automatic=true';
                var code = $(".blue-payment__blik input[name='payment_method_bluepayment_code']").val();

                if (code.length === 6) {
                    $.ajax({
                        showLoader: true,
                        url: urlResponse,
                        data: {'code': code},
                        type: "POST",
                        dataType: "json",
                    }).done(function (response) {
                        if (response.status && response.status == false) {
                            alert('Status = false');
                        } else {
                            redirectUrl = url.build('bluepayment/processing/backblick')
                                + '?ServiceID=' + response.params.ServiceID
                                + '&OrderID=' + response.params.OrderID
                                + '&Hash=' + response.params.hash
                                + '&paymentStatus=' + response.params.paymentStatus;
                            window.location.href = redirectUrl;
                        }
                    });
                }

                return false;
            }
        });
    }
);
