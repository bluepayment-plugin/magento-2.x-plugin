define([
    'jquery',
    'underscore',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/payment-service',
    'mage/url',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-selected-gateway',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-agreements',
    'BlueMedia_BluePayment/js/checkout-data',
    'Magento_Ui/js/modal/modal',
    'text!BlueMedia_BluePayment/template/blik-popup.html',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    $,
    _,
    ko,
    Component,
    selectPaymentMethodAction,
    paymentService,
    url,
    $t,
    quote,
    selectedGateway,
    agreements,
    checkoutData,
    modal,
    blikTpl,
    additionalValidators,

) {
    'use strict';

    var redirectUrl;
    var widget;

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment',
            logoUrl: window.checkoutConfig.payment.bluePaymentLogo || 'https://bm.pl/img/www/logos/bmLogo.png',
            grandTotalAmount: 0
        },

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

        blikInputSelector: ".blue-payment__blik input[name='payment_method_bluepayment_code']",
        blikModal: modal({
            title: $t('Confirm BLIK transaction'),
            autoOpen: false,
            clickableOverlay: false,
            buttons: [],
            type: 'popup',
            popupTpl: blikTpl,
            keyEventHandlers: {},
            modalClass: 'blik-modal',
            }, $('<div />').html($t('Confirm the payment in your bank\'s app.'))),
        blikTimeout: null,
        collapsed: ko.observable(true),

        gatewayIds: {
            blik: 509,
            smartney: 700,
            hub: 702,
            paypo: 705,
            card: 1500,
            one_click: 1503,
            gpay: 1512,
            apple_pay: 1513,
            alior_installments: 1506,
        },

        /**
         * Subscribe to grand totals
         */
        initObservable: function () {
            this._super();
            this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
            this.currencyCode = quote.totals()['base_currency_code'];

            quote.totals.subscribe(function () {
                if (this.grandTotalAmount !== quote.totals()['base_grand_total']) {
                    this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                }
            }.bind(this));

            return this;
        },

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

            var blueMediaPayment = checkoutData.getBlueMediaPaymentMethod();
            if (blueMediaPayment && quote.paymentMethod()) {
                if (quote.paymentMethod().method === 'bluepayment') {
                    selectedGateway(blueMediaPayment);
                }
            }

            PayBmCheckout.transactionSuccess = function (status) {
                window.location.href = redirectUrl;
            };

            PayBmCheckout.transactionDeclined = function (status) {
                // window.location.href = redirectUrl;
            };

            PayBmCheckout.transactionError = function (status) {
                // window.location.href = redirectUrl;
            };

            if (typeof google !== 'undefined' && typeof google.payments !== 'undefined') {
                this.initGPay();
            }

            // Refresh selected gateway
            checkoutData.setIndividualGatewayFlag('');
            this.setBlueMediaGatewayMethod({});
        },
        blikCodeAfterRender: function () {
            // Only number for blik code
            var blikInput = $(this.blikInputSelector);
            if (blikInput) {
                blikInput.keypress(function (e) {
                    blikInput.val(this.value.match(/\d{0,6}/));
                });
            }
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

            return true;
        },
        setBlueMediaGatewayMethod: function (value) {
            this.validationFailed(false);
            selectedGateway(value);
            checkoutData.setBlueMediaPaymentMethod(value);
        },
        isChecked: ko.computed(function () {
            var paymentMethod = quote.paymentMethod();

            if (paymentMethod) {
                return checkoutData.getIndividualGatewayFlag() ? false : paymentMethod.method;
            }
            return null;
        }),
        isRadioButtonVisible: ko.computed(function () {
            // If it has separated methods - always show radio
            if (window.checkoutConfig.payment.bluePaymentSeparated.length > 0) {
                return true;
            }

            return paymentService.getAvailablePaymentMethods().length !== 1;
        }),
        canUseApplePay: function() {
            try {
                return window.ApplePaySession && ApplePaySession.canMakePayments();
            } catch (e) {
                console.log('Cannot use ApplePay', e);
            }

            return false;
        },
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

            return selectedGateway().is_iframe === true && selectedGateway().is_separated_method == "1";
        },
        isBlikSelected: function () {
            return selectedGateway().is_blik === true && selectedGateway().is_separated_method == "1";
        },
        isGPaySelected: function () {
            return selectedGateway().is_gpay === true;
        },
        isAutopaySelected: function () {
            return selectedGateway().is_autopay === true;
        },

        getTitle: function () {
            if (this.renderSubOptions === false) {
                return $t('Quick payment');
            }

            return $t('Internet transfer');
        },

        getDescription: function () {
            if (this.renderSubOptions === false) {
                return $t('Internet transfer, BLIK, payment card, Google Pay, Apple Pay');
            }

            return false;
        },

        getGatewayTitle: function (gateway) {
            let gatewayId = Number(gateway.gateway_id);

            if (gatewayId === this.gatewayIds.card) {
                return $t('Card Payment');
            }

            if (gatewayId === this.gatewayIds.smartney) {
                return $t('Pay later');
            }

            if (gatewayId === this.gatewayIds.alior_installments) {
                return $t('Spread the cost over installments');
            }

            return gateway.name;
        },

        getGatewayDescription: function (gateway) {
            let gatewayId = Number(gateway.gateway_id);

            if (gatewayId === this.gatewayIds.card) {
                if (gateway.is_iframe) {
                    return $t("Pay with your credit or debit card.");
                } else {
                    return $t("You will be redirected to our partner Blue Media's website, where you will enter your card details.");
                }
            }

            if (gatewayId === this.gatewayIds.smartney) {
                return $t('Buy now and pay within 30 days. %1')
                    .replace('%1', '<a href="https://pomoc.bluemedia.pl/platnosci-online-w-e-commerce/pay-smartney" target="_blank">' + $t('Learn more') + '</a>');
            }

            if (gatewayId === this.gatewayIds.alior_installments) {
                return $t('0% installments and even 48 installments. %1')
                    .replace('%1', '<a href="https://kalkulator.raty.aliorbank.pl/init?supervisor=B776&promotionList=B" target="_blank">' + $t('Check out other installment options') + '</a>');
            }

            if (gatewayId === this.gatewayIds.paypo) {
                return $t('Shop using deferred payment option or a convenient installment plan. %1')
                    .replace('%1', '<a href="https://start.paypo.pl/" target="_blank">' + $t('Find out the details') + '</a>');
            }

            return gateway.description;
        },

        getGatewayHelp: function (gateway) {
            let gatewayId = Number(gateway.gateway_id);

            if (gatewayId === this.gatewayIds.smartney) {
                return $t("You will be redirected to Smartney's partner website. After your application and positive verification, Smartney will pay for your purchases for you.");
            }

            if (gatewayId === this.gatewayIds.alior_installments) {
                return $t("You will be redirected to the bank's website. After your application and positive verification, the bank will send you a loan agreement by email. You can accept it online. Average time of the whole transaction - 15 minutes.");
            }

            if (gatewayId === this.gatewayIds.paypo) {
                return $t("You will be redirected to PayPo's partner website.");
            }

            return null;
        },

        /**
         * @return {Boolean}
         */
        validate: function () {
            if (! additionalValidators.validate()) {
                return false;
            }

            // Autopay agreement
            if (this.isAutopaySelected()) {
                var card_index = jQuery("input[name='payment_method_bluepayment_card_index']:checked").val();

                if (card_index !== undefined) {
                    checkoutData.setCardIndex(card_index);

                    if (card_index == -1 && !jQuery('#autopay-agreement').is(':checked')) {
                        this.messageContainer.addErrorMessage({message: $t('You have to agree with terms.')});

                        return false;
                    }
                } else {
                    this.messageContainer.addErrorMessage({message: $t('You have to select card.')});

                    return false;
                }
            }

            // BLIK Validation
            if (this.isBlikSelected()) {
                var code = $(this.blikInputSelector).val();
                if (code.length !== 6) {
                    this.messageContainer.addErrorMessage({
                        message: $t('Invalid BLIK code.')
                    });
                    $(this.blikInputSelector).focus();
                    return false;
                }
            }

            // Call GooglePay
            if (this.isGPaySelected()) {
                this.callGPayPayment();
                return false;
            }

            // Selected payment method validation
            if (this.renderSubOptions !== false && !this.activeMethod()) {
                this.validationFailed(true);
                $('.payment-method-empty-gateway')[0].scrollIntoView({block: "center"});
                return false;
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

            var href = url.build('bluepayment/processing/create') + '?gateway_id=' + selectedGateway().gateway_id;

            if (this.isAutopaySelected()) {
                href += '&card_index=' + checkoutData.getCardIndex();
            }

            window.location.href = href;
        },
        callIframePayment: function () {
            var urlResponse = url.build('bluepayment/processing/create')
                + '?gateway_id='
                + selectedGateway().gateway_id
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
                + selectedGateway().gateway_id
                + '&automatic=true';
            var code = $(this.blikInputSelector).val();

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
                            self.messageContainer.addErrorMessage({message: $t('Invalid BLIK code.')});
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
                this.messageContainer.addErrorMessage({
                    message: $t('Request timed out. Please try again or use a different payment method.')
                });
            } else {
                if (this.blikModal.options.isOpen !== true) {
                    this.blikModal.openModal();
                    this.blikModal._removeKeyListener();

                    this.blikTimeout = setTimeout(function () {
                        self.blikModal.closeModal();
                        self.messageContainer.addErrorMessage({
                            message: $t('The BLIK code has expired. Try again.')
                        });
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
            title: $t('Waiting for the confirmation of the transaction.'),
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
                        + selectedGateway().gateway_id
                        + '&automatic=true';

                    $.ajax({
                        showLoader: true,
                        url: urlResponse,
                        data: {'token': token},
                        type: "POST",
                        dataType: "json",
                        }).done(function (response) {
                            console.log(response);

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
                    totalPrice: this.grandTotalAmount,
                    currencyCode: this.currencyCode
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
        },
    });
});
