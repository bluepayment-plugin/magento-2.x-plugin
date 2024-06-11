define([
    'jquery',
    'ko',
    'mage/translate',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/modal/modal',
    'BlueMedia_BluePayment/js/checkout-data',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
    'text!BlueMedia_BluePayment/template/wait-popup.html',
], function (
    $,
    ko,
    $t,
    url,
    quote,
    additionalValidators,
    modal,
    checkoutData,
    Component,
    config,
    popupTpl,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-google-pay',
            gateway_id: 1512,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,
        },
        client: ko.observable(false),
        testMode: config.testMode,
        merchantInfo: null,
        acceptorId: null,
        modal: modal({
                title: $t('Waiting for the confirmation of the transaction.'),
                autoOpen: false,
                clickableOverlay: false,
                buttons: [],
                type: 'popup',
                popupTpl: popupTpl,
                keyEventHandlers: {},
                modalClass: 'blik-modal',
            },
            $('<div />').html()),


        /**
         * Subscribe to grand totals
         */
        initObservable: function () {
            this._super();
            this.currencyCode = quote.totals()['base_currency_code'];

            return this;
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();

            if (typeof google !== 'undefined' && typeof google.payments !== 'undefined') {
                this.initGooglePay();
            }
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
                this.callGooglePayPayment();
            }

            return false;
        },

        afterPlaceOrder: function () {
            return true;
        },

        /**
         * Is Google Pay available
         *
         * @returns {boolean}
         */
        isAvailable: function () {
            return this.client() !== false;
        },

        /**
         * Initialize Google Pay
         */
        initGooglePay: function () {
            const urlResponse = url.build('bluepayment/processing/googlepay');
            const self = this;

            $.ajax({
                showLoader: false,
                url: urlResponse,
                type: 'GET',
                dataType: "json"
            }).done(function (response) {
                if (!response.hasOwnProperty('error')) {
                    self.merchantInfo = response.merchantInfo;
                    self.acceptorId = response.acceptorId.toString();

                    let client = new google.payments.api.PaymentsClient({
                        environment: self.testMode ? 'TEST' : 'PRODUCTION'
                    });
                    self.client(client);
                    client.isReadyToPay({
                        apiVersion: 2,
                        apiVersionMinor: 0,
                        merchantInfo: this.merchantInfo,
                        allowedPaymentMethods: [{
                            type: "CARD",
                            parameters: {
                                allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                                allowedCardNetworks: ["MASTERCARD", "VISA"]
                            }
                        }]
                    })
                        .then(function (response) {
                            const transactionData = self.getTransactionData();
                            transactionData.transactionInfo.totalPriceStatus = 'NOT_CURRENTLY_KNOWN';

                            if (response.result) {
                                client.prefetchPaymentData(transactionData);
                                client.createButton({
                                    onClick: function () {}
                                });
                            } else {
                                console.error(response);
                            }
                        })
                        .catch(function () {
                            console.error(response);
                        });
                } else {
                    // Google Pay not available
                    console.warn(response.error);
                }
            });
        },

        /**
         * Get Google Pay transaction data
         *
         * @returns {object}
         */
        getTransactionData: function () {
            return {
                apiVersion: 2,
                apiVersionMinor: 0,
                merchantInfo: this.merchantInfo,

                allowedPaymentMethods: [{
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                        allowedCardNetworks: ["MASTERCARD", "VISA"]
                    },
                    tokenizationSpecification: {
                        type: 'PAYMENT_GATEWAY',
                        parameters: {
                            'gateway': 'bluemedia',
                            'gatewayMerchantId': this.acceptorId
                        }
                    }
                }],
                shippingAddressRequired: false,
                transactionInfo: {
                    totalPriceStatus: 'FINAL',
                    totalPrice: this.grandTotalAmount(),
                    currencyCode: this.currencyCode
                },
            };
        },

        /**
         * Call Google Pay payment
         *
         * @returns {exports}
         */
        callGooglePayPayment: function () {
            const self = this;

            self.client().loadPaymentData(self.getTransactionData()).then(function (data) {
                self.placeOrderAfterValidation(function () {
                    const token = data.paymentMethodData.tokenizationData.token;
                    const urlResponse = url.build('bluepayment/processing/create')
                        + '?gateway_id=1512'
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
                                    self.handleStatus(response.params.paymentStatus, response.params);
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

        /**
         * Handle Google Pay status
         * @param status
         * @param params
         */
        handleStatus: function (status, params) {
            const self = this;

            if (status === 'SUCCESS') {
                window.location.href = url.build('bluepayment/processing/back')
                    + '?ServiceID=' + params.ServiceID
                    + '&OrderID=' + params.OrderID
                    + '&Hash=' + params.hash
                    + '&Status=' + 'SUCCESS';
            } else if (status === 'FAILURE') {
                this.modal.closeModal();
                console.error('GPay - status failure');
            } else {
                if (this.modal.options.isOpen !== true) {
                    this.modal.openModal();
                    this.modal._removeKeyListener();
                }

                setTimeout(function () {
                    self.updateStatus(status);
                }, 2000);
            }
        },

        /**
         * Update Google Pay status
         */
        updateStatus: function () {
            const urlResponse = url.build('bluepayment/processing/paymentstatus');
            const self = this;

            $.ajax({
                showLoader: false,
                url: urlResponse,
                type: 'GET',
                dataType: "json"
            }).done(function (response) {
                if (typeof response.Status !== 'undefined') {
                    self.handleStatus(response.Status, response);
                }
            });
        },
    });
});

