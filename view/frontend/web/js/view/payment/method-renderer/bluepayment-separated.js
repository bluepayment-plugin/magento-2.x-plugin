define([
    'ko',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-abstract',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
], function (
    ko,
    $t,
    quote,
    Component,
    model,
    config
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

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'separated': true,
                    'gateway_id': this.gateway_id,
                    'agreements_ids': model.getCheckedAgreementsIds(),
                }
            }
        },

        /**
         * Get payment method code.
         */
        getCode: function () {
            return this.item.method + '_' + this.gateway_id;
        },

        /**
         * Is payment method selected.
         */
        isChecked: ko.computed(function () {
            const paymentMethod = quote.paymentMethod();

            if (!paymentMethod || !paymentMethod.additional_data || !paymentMethod.additional_data.gateway_id) {
                return null;
            }

            return paymentMethod.method + '_' + paymentMethod.additional_data.gateway_id;
        }),

        /**
         * Get gateway title.
         *
         * @returns {string|null}
         */
        getGatewayTitle: function () {
            let gatewayId = Number(this.gateway_id);

            if (gatewayId === model.gatewaysIds.card) {
                return $t('Card Payment');
            }

            if (gatewayId === model.gatewaysIds.alior_installments) {
                return $t('Spread the cost over installments');
            }

            if (gatewayId === model.gatewaysIds.visa_mobile) {
                return $t('Visa Mobile');
            }

            return this.gateway_name;
        },

        /**
         * Get gateway description.
         *
         * @returns {string|null}
         */
        getGatewayDescription: function () {
            let gatewayId = Number(this.gateway_id);

            if (gatewayId === model.gatewaysIds.alior_installments) {
                const link = '<a href="' + config.aliorCalculatorUrl + '"  target="_blank">'
                    + $t('Learn more') +
                    '</a>';

                return $t('Pay for your purchases using convenient instalments. %1')
                    .replace('%1', link);
            }

            if (gatewayId === model.gatewaysIds.paypo) {
                return $t('Pick up your purchases, check them out and pay later &mdash; in 30 days or in convenient installments. %1')
                    .replace('%1', '<a href="https://start.paypo.pl/" target="_blank">' + $t('Learn more') + '</a>');
            }

            if (gatewayId === model.gatewaysIds.visa_mobile) {
                return $t('Enter the phone number and confirm the payment in the mobile app.');
            }

            return this.gateway_description;
        },

        /**
         * Get gateway help.
         *
         * @returns {string|null}
         */
        getGatewayHelp: function () {
            let gatewayId = Number(this.gateway_id);

            if (gatewayId === model.gatewaysIds.alior_installments) {
                return $t("You will be redirected to the bank's website. After your application and positive verification, the bank will send you a loan agreement by email. You can accept it online. Average time of the whole transaction - 15 minutes.");
            }

            if (gatewayId === model.gatewaysIds.paypo) {
                return $t("You will be redirected to PayPo's partner website.");
            }

            return null;
        },
    });
});
