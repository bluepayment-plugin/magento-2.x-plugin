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
            template: 'BlueMedia_BluePayment/payment/multishipping/bluepayment_separated'
        },
        bluePaymentAutopayAgreement: window.checkoutConfig.payment.bluePaymentAutopayAgreement,
        agreements: ko.observable([]),

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'gateway_id': this.item.gateway_id,
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
    });
});
