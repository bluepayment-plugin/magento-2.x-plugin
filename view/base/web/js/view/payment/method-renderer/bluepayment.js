define([
        'jquery',
        'underscore',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'mage/url',
        'BlueMedia_BluePayment/js/model/quote',
        'BlueMedia_BluePayment/js/checkout-data',
        'Magento_Ui/js/modal/modal',
        'text!BlueMedia_BluePayment/template/blik-popup.html',
        'Magento_Checkout/js/model/payment/additional-validators'
    ], function ($,
                 _,
                 ko,
                 Component,
                 selectPaymentMethodAction,
                 url,
                 quote,
                 checkoutData,
                 modal,
                 blikTpl,
                 additionalValidators
    ) {
        'use strict';
        var widget;
        var redirectUrl;

        return Component.extend({
            ordered: false,
            redirectAfterPlaceOrder: false,
            renderSubOptions: window.checkoutConfig.payment.bluePaymentOptions,
            renderSeparatedOptions: window.checkoutConfig.payment.bluePaymentSeparated,
            bluePaymentTestMode: window.checkoutConfig.payment.bluePaymentTestMode,
            bluePaymentCards: window.checkoutConfig.payment.bluePaymentCards,
            bluePaymentAutopayAgreement: window.checkoutConfig.payment.bluePaymentAutopayAgreement,
            bluePaymentCollapsible:
                window.checkoutConfig.payment.bluePaymentCollapsible === '1'
                && window.checkoutConfig.payment.bluePaymentOptions.length > 8,
            selectedPaymentObject: {},
            selectedAutopayGatewayIndex: null,
            validationFailed: ko.observable(false),
            activeMethod: ko.computed(function () {
                if (checkoutData.getBlueMediaPaymentMethod() && quote.paymentMethod()) {
                    if (quote.paymentMethod().method === 'bluepayment') {
                        return checkoutData.getBlueMediaPaymentMethod().gateway_id;
                    }
                }
                return -1;
            }),
            blikModal: modal({
                title: 'Potwierdź transakcję BLIK',
                autoOpen: false,
                clickableOverlay: false,
                buttons: [],
                type: 'popup',
                popupTpl: blikTpl,
                keyEventHandlers: {},
                modalClass: 'blik-modal',
            }, $('<div />').html('Potwierdź płatność w aplikacji swojego banku.')),
            blikTimeout: null,
            collapsed: ko.observable(true),

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

                PayBmCheckout.transactionSuccess = function (status) {
                    window.location.href = redirectUrl;
                };

                PayBmCheckout.transactionDeclined = function (status) {
                    // window.location.href = redirectUrl;
                };

                PayBmCheckout.transactionError = function (status) {
                    // window.location.href = redirectUrl;
                };

                if (typeof google.payments !== 'undefined') {
                    this.initGPay();
                }
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
            selectAutopayCardIndex: function (card) {
                this.selectedAutopayGatewayIndex = card.index;

                if (card.index === -1) {
                    $('.autopay-agreement').show();
                } else {
                    $('.autopay-agreement').hide();
                }

                $('.autopay-card-error').hide();
                return true;
            },
            setBlueMediaGatewayMethod: function (value) {
                this.validationFailed(false);
                this.selectedPaymentObject = value;

                quote.setBlueMediaPaymentMethod(value);
                checkoutData.setBlueMediaPaymentMethod(value);
            },
            isChecked: ko.computed(function () {
                var paymentMethod = quote.paymentMethod();

                if (paymentMethod) {
                    return checkoutData.getIndividualGatewayFlag() ? false : paymentMethod.method;
                }
                return null;
            }),
            isSeparatedChecked: function (context) {
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
            isIframeSelected: function () {
                if (this.isAutopaySelected()) {
                    var cardIndex = checkoutData.getCardIndex();

                    if (cardIndex != -1) {
                        return false;
                    }
                }

                return this.selectedPaymentObject.is_iframe === true && this.selectedPaymentObject.is_separated_method == "1";
            },
            isBlikSelected: function () {
                return this.selectedPaymentObject.is_blik === true && this.selectedPaymentObject.is_separated_method == "1";
            },
            isGPaySelected: function () {
                return this.selectedPaymentObject.is_gpay === true;
            },
            isAutopaySelected: function () {
                return this.selectedPaymentObject.is_autopay === true;
            },
            /**
             * @return {Boolean}
             */
            validate: function () {
                if (! additionalValidators.validate()) {
                    return false;
                }

                if (this.isAutopaySelected()) {
                    var card_index = jQuery("input[name='payment_method_bluepayment_card_index']:checked").val();

                    if (card_index !== undefined) {
                        checkoutData.setCardIndex(card_index);

                        if (card_index == -1 && !jQuery('#autopay-agreement').is(':checked')) {
                            $('.autopay-agreement-error').show();
                            return false;
                        }
                    } else {
                        $('.autopay-card-error').show();
                        return false;
                    }
                }

                if (this.isGPaySelected()) {
                    this.callGPayPayment();
                    return false;
                }

                $('.blik-error').hide();

                if (_.isEmpty(this.selectedPaymentObject)) {
                    this.validationFailed(true);
                    return false;
                }

                // Add code validation if BLIK is selected./
                if (this.isBlikSelected()) {
                    var code = $(".blue-payment__blik input[name='payment_method_bluepayment_code']").val();
                    if (code.length !== 6) {
                        $('.blik-error').text('Niepoprawny kod BLIK.').show();
                        $(".blue-payment__blik input[name='payment_method_bluepayment_code']").focus();
                        return false;
                    }
                }

                return true;
            },
            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate()) {
                    self.placeOrderAfterValidation();
                }

                return false;
            },
            placeOrderAfterValidation: function (callback) {
                var self = this;

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
                if (this.isIframeSelected()) {
                    this.callIframePayment();
                    return false;
                }

                if (this.isBlikSelected()) {
                    this.callBlikPayment();
                    return false;
                }

                if (this.isGPaySelected()) {
                    return false;
                }

                var href = url.build('bluepayment/processing/create') + '?gateway_id=' + this.selectedPaymentObject.gateway_id;

                if (this.isAutopaySelected()) {
                    href += '&card_index=' + checkoutData.getCardIndex();
                }

                window.location.href = href;
            },
            callIframePayment: function () {
                var urlResponse = url.build('bluepayment/processing/create')
                    + '?gateway_id='
                    + this.selectedPaymentObject.gateway_id
                    + '&automatic=true';

                if (this.isAutopaySelected()) {
                    urlResponse += '&card_index=' + checkoutData.getCardIndex();
                }

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
            callBlikPayment: function () {
                var self = this;

                var urlResponse = url.build('bluepayment/processing/create')
                    + '?gateway_id='
                    + this.selectedPaymentObject.gateway_id
                    + '&automatic=true';
                var code = $(".blue-payment__blik input[name='payment_method_bluepayment_code']").val();
                $('.blik-error').hide();

                if (code.length === 6) {
                    $.ajax({
                        showLoader: true,
                        url: urlResponse,
                        data: {'code': code},
                        type: "POST",
                        dataType: "json",
                    }).done(function (response) {
                        if (response.params) {
                            if (response.params.confirmation && response.params.confirmation == 'NOTCONFIRMED') {
                                $('.blik-error').text('Niepoprawny kod BLIK.').show();
                            } else {
                                self.handleBlikStatus(response.params.paymentStatus, response.params);
                            }
                        }
                    });
                }

                return false;
            },
            handleBlikStatus: function (status, params) {
                var self = this;

                if (status === 'SUCCESS') {
                    clearTimeout(self.blikTimeout);

                    redirectUrl = url.build('bluepayment/processing/back')
                        + '?ServiceID=' + params.ServiceID
                        + '&OrderID=' + params.OrderID
                        + '&Hash=' + params.hash
                        + '&Status=' + 'SUCCESS';

                    window.location.href = redirectUrl;
                } else if (status === 'FAILURE') {
                    clearTimeout(self.blikTimeout);

                    this.blikModal.closeModal();
                    $('.blik-error').text('Upłynął czas żądania. Spróbuj ponownie lub użyj innej metody płatności.').show();
                } else {
                    if (this.blikModal.options.isOpen !== true) {
                        this.blikModal.openModal();
                        this.blikModal._removeKeyListener();

                        this.blikTimeout = setTimeout(function () {
                            self.blikModal.closeModal();
                            $('.blik-error').text('Kod BLIK wygasł. Spróbuj ponownie.').show();
                        }, 120000); /* 2 minutes */
                    }

                    setTimeout(function () {
                        if (self.blikModal.options.isOpen) {
                            self.updateBlikStatus(status);
                        }
                    }, 2000);
                }
            },

            updateBlikStatus: function () {
                var urlResponse = url.build('bluepayment/processing/blik');
                var self = this;

                $.ajax({
                    showLoader: false,
                    url: urlResponse,
                    type: 'GET',
                    dataType: "json"
                }).done(function (response) {
                    if (typeof response.Status !== 'undefined') {
                        self.handleBlikStatus(response.Status, response);
                    }
                });
            },

            /* Google Pay */
            GPayMerchantInfo: null,
            bluePaymentAcceptorId: null,
            GPayModal: modal({
                title: 'Oczekiwanie na potwierdzenie transakcji.',
                autoOpen: false,
                clickableOverlay: false,
                buttons: [],
                type: 'popup',
                popupTpl: blikTpl,
                keyEventHandlers: {},
                modalClass: 'blik-modal',
            }, $('<div />').html()),
            callGPayPayment: function () {
                var self = this;

                self.GPayClient.loadPaymentData(self.getGPayTransactionData()).then(function (data) {
                    self.placeOrderAfterValidation(function () {
                        var token = data.paymentMethodData.tokenizationData.token;
                        var urlResponse = url.build('bluepayment/processing/create')
                            + '?gateway_id='
                            + self.selectedPaymentObject.gateway_id
                            + '&automatic=true';

                        $.ajax({
                            showLoader: true,
                            url: urlResponse,
                            data: {'token': token},
                            type: "POST",
                            dataType: "json",
                        }).done(function (response) {
                            if (response.params) {
                                if (response.params.redirectUrl) {
                                    window.location.href = response.params.redirectUrl;
                                } else {
                                    if (response.params.paymentStatus) {
                                        self.handleGPayStatus(response.params.paymentStatus, response.params);
                                    } else {
                                        console.error('Payment has no paymentStatus.');
                                    }
                                }
                            }
                        });
                    });
                })
                    .catch(function (errorMessage) {
                        console.error(errorMessage);
                    });
            },
            getGPayTransactionData: function () {
                return {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    merchantInfo: this.GPayMerchantInfo,

                    allowedPaymentMethods: [
                        {
                            type: 'CARD',
                            parameters: {
                                allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                                allowedCardNetworks: [/*"AMEX", "DISCOVER", "JCB", */"MASTERCARD", "VISA"]
                            },
                            tokenizationSpecification: {
                                type: 'PAYMENT_GATEWAY',
                                parameters: {
                                    'gateway': 'bluemedia',
                                    'gatewayMerchantId': this.bluePaymentAcceptorId
                                }
                            }
                        }
                    ],
                    shippingAddressRequired: false,
                    transactionInfo: {
                        totalPriceStatus: 'FINAL',
                        totalPrice: quote.getCalculatedTotal().toFixed(2).toString(),
                        currencyCode: window.checkoutConfig.quoteData.quote_currency_code
                    },
                };
            },
            initGPay: function () {
                var urlResponse = url.build('bluepayment/processing/googlepay');
                var self = this;

                $.ajax({
                    showLoader: false,
                    url: urlResponse,
                    type: 'GET',
                    dataType: "json"
                }).done(function (response) {
                    if (!response.hasOwnProperty('error')) {
                        self.GPayMerchantInfo = response.merchantInfo;
                        self.bluePaymentAcceptorId = response.acceptorId.toString();

                        self.GPayClient = new google.payments.api.PaymentsClient({
                            environment: self.bluePaymentTestMode === "1" ? 'TEST' : 'PRODUCTION'
                        });
                        self.GPayClient.isReadyToPay({
                            apiVersion: 2,
                            apiVersionMinor: 0,
                            merchantInfo: this.GPayMerchantInfo,
                            allowedPaymentMethods: [
                                {
                                    type: "CARD",
                                    parameters: {
                                        allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                                        allowedCardNetworks: [/*"AMEX", "DISCOVER", "JCB", */"MASTERCARD", "VISA"]
                                    }
                                }
                            ]
                        })
                            .then(function (response) {
                                var transactionData = self.getGPayTransactionData();
                                transactionData.transactionInfo.totalPriceStatus = 'NOT_CURRENTLY_KNOWN';

                                if (response.result) {
                                    self.GPayClient.prefetchPaymentData(transactionData);
                                    self.GPayClient.createButton({
                                        onClick: function () {
                                        }
                                    });
                                } else {
                                    console.error(response);
                                }
                            })
                            .catch(function (errorMessage) {
                                console.error(response);
                            });
                    }
                });
            },
            handleGPayStatus: function (status, params) {
                var self = this;

                if (status === 'SUCCESS') {
                    redirectUrl = url.build('bluepayment/processing/back')
                        + '?ServiceID=' + params.ServiceID
                        + '&OrderID=' + params.OrderID
                        + '&Hash=' + params.hash
                        + '&Status=' + 'SUCCESS';

                    window.location.href = redirectUrl;
                } else if (status === 'FAILURE') {
                    this.GPayModal.closeModal();
                    console.error('GPay - status failure');
                } else {
                    if (this.GPayModal.options.isOpen !== true) {
                        this.GPayModal.openModal();
                        this.GPayModal._removeKeyListener();
                    }

                    setTimeout(function () {
                        self.updateGPayStatus(status);
                    }, 2000);
                }
            },
            updateGPayStatus: function () {
                var urlResponse = url.build('bluepayment/processing/blik');
                var self = this;

                $.ajax({
                    showLoader: false,
                    url: urlResponse,
                    type: 'GET',
                    dataType: "json"
                }).done(function (response) {
                    self.handleGPayStatus(response.Status, response);
                });
            }
        });
    }
);
