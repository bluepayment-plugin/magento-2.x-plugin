/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/

define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/select-payment-method',
    'mage/url'
], function (
    $,
    ko,
    Component,
    fullScreenLoader,
    setPaymentInformationAction,
    additionalValidators,
    selectPaymentMethodAction,
    url
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/multishipping/bluepayment_autopay'
        },

        bluePaymentCards: window.checkoutConfig.payment.bluePaymentCards,
        bluePaymentAutopayAgreement: window.checkoutConfig.payment.bluePaymentAutopayAgreement,
        selectedAutopayGatewayIndex: false,
        validationFailed: ko.observable(false),
        validationAgreementFailed: ko.observable(false),
        agreements: ko.observable([]),

        initObservable: function () {
            this.reviewButtonHtml = $(this.submitButtonSelector).html();

            return this._super();
        },

        /**
         * Trigger order placing
         */
        placeOrderClick: function () {
            // Selected payment method validation
            if (this.selectedAutopayGatewayIndex === false) {
                this.validationFailed(true);
                return false;
            }
            this.validationFailed(false);

            if (this.selectedAutopayGatewayIndex === -1 && !$('#autopay-agreement').is(':checked')) {
                this.validationAgreementFailed(true);
                return false;
            }
            this.validationAgreementFailed(true);

            return true;
        },

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'gateway_id': this.item.gateway_id,
                    'gateway_index': this.selectedAutopayGatewayIndex,
                    'agreements_ids': this.getCheckedAgreementsIds(),
                }
            };
        },

        /**
         * @override
         */
        setPaymentInformation: function () {
            if (additionalValidators.validate()) {
                fullScreenLoader.startLoader();

                $.when(
                    setPaymentInformationAction(
                        this.messageContainer,
                        this.getData()
                    )
                ).done(this.done.bind(this))
                    .fail(this.fail.bind(this));
            }
        },

        /**
         * @override
         */
        selectPaymentMethod: function () {
            this.item.individual_gateway = null;
            selectPaymentMethodAction(this.getData());
            setPaymentInformationAction(this.messageContainer, this.getData());

            this.getAgreements();

            return true;
        },

        selectAutopayCardIndex: function (card) {
            this.selectedAutopayGatewayIndex = card.index;

            if (card.index === -1) {
                $('.autopay-agreement').show();
            } else {
                $('.autopay-agreement').hide();
            }

            this.validationFailed(false);
            setPaymentInformationAction(this.messageContainer, this.getData());

            return true;
        },

        getAgreements: function () {
            var urlResponse = url.build('bluepayment/processing/agreements');
            var self = this;

            $.ajax({
                showLoader: true,
                url: urlResponse,
                type: 'GET',
                data: {
                    'gateway_id': this.item.gateway_id
                },
                dataType: "json"
            }).done(function (response) {
                if (!response.hasOwnProperty('error')) {
                    self.agreements(response);
                    setPaymentInformationAction(self.messageContainer, self.getData());
                }
            });
        },

        agreementChanged: function () {
            setPaymentInformationAction(this.messageContainer, this.getData());
        },

        getCheckedAgreementsIds: function () {
            var agreementData = $('.payment-method._active[data-name=' + this.name + '] .bluepayment-agreements-block input')
                .serializeArray();
            var agreementsIds = [];

            agreementData.forEach(function (item) {
                agreementsIds.push(item.value);
            });

            return agreementsIds.join(',');
        },
    });
});
