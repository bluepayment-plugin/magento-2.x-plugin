define([
    'ko',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-abstract',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-gateways',
    'BlueMedia_BluePayment/js/checkout-data',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
], function (
    ko,
    Component,
    bluepaymentGateways,
    checkoutData,
    quote,
    $t,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-separated',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,
        },

        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'separated': true,
                    'gateway_id': this.gateway_id,
                }
            }
        },

        getCode: function () {
            return this.item.method + '_' + this.gateway_id;
        },

        isChecked: ko.computed(function () {
            const paymentMethod = quote.paymentMethod();

            if (!paymentMethod) {
                return null;
            }

            if (!paymentMethod.additional_data || !paymentMethod.additional_data.gateway_id) {
                return null;
            }

            console.log(
                this.getCode(),
                paymentMethod.method + '_' + paymentMethod.additional_data.gateway_id
            );

            return paymentMethod.method + '_' + paymentMethod.additional_data.gateway_id;
        }),

        selectPaymentMethod: function () {
            console.log('selectPaymentMethod');
            debugger;
            this._super();
        },

        getGatewayTitle: function () {
            let gatewayId = Number(this.gateway_id);

            if (gatewayId === bluepaymentGateways.ids.card) {
                return $t('Card Payment');
            }

            if (gatewayId === bluepaymentGateways.ids.smartney) {
                return $t('Pay later');
            }

            if (gatewayId === bluepaymentGateways.ids.alior_installments) {
                return $t('Spread the cost over installments');
            }

            if (gatewayId === bluepaymentGateways.ids.visa_mobile) {
                return $t('Visa Mobile');
            }

            return this.gateway_name;
        },

        getGatewayDescription: function () {
            let gatewayId = Number(this.gateway_id);

            if (gatewayId === bluepaymentGateways.ids.card) {
                if (this.iframeEnabled) {
                    return $t("Pay with your credit or debit card.");
                } else {
                    return $t("You will be redirected to our partner Blue Media's website, where you will enter your card details.");
                }
            }

            if (gatewayId === bluepaymentGateways.ids.smartney) {
                return $t('Buy now and pay within 30 days. %1')
                    .replace('%1', '<a href="https://pomoc.bluemedia.pl/platnosci-online-w-e-commerce/pay-smartney" target="_blank">' + $t('Learn more') + '</a>');
            }

            if (gatewayId === bluepaymentGateways.ids.alior_installments) {
                return $t('0% installments and even 48 installments. %1')
                    .replace('%1', '<a href="https://kalkulator.raty.aliorbank.pl/init?supervisor=B776&promotionList=B" target="_blank">' + $t('Check out other installment options') + '</a>');
            }

            if (gatewayId === bluepaymentGateways.ids.paypo) {
                return $t('Pick up your purchases, check them out and pay later &mdash; in 30 days or in convenient installments. %1')
                    .replace('%1', '<a href="https://start.paypo.pl/" target="_blank">' + $t('Find out the details') + '</a>');
            }

            if (gatewayId === bluepaymentGateways.ids.visa_mobile) {
                return $t('Enter the phone number and confirm the payment in the mobile app.');
            }

            return this.gateway_description;
        },

        getGatewayHelp: function () {
            let gatewayId = Number(this.gateway_id);

            if (gatewayId === bluepaymentGateways.ids.smartney) {
                return $t("You will be redirected to Smartney's partner website. After your application and positive verification, Smartney will pay for your purchases for you.");
            }

            if (gatewayId === bluepaymentGateways.ids.alior_installments) {
                return $t("You will be redirected to the bank's website. After your application and positive verification, the bank will send you a loan agreement by email. You can accept it online. Average time of the whole transaction - 15 minutes.");
            }

            if (gatewayId === bluepaymentGateways.ids.paypo) {
                return $t("You will be redirected to PayPo's partner website.");
            }

            return null;
        },
    });
});
