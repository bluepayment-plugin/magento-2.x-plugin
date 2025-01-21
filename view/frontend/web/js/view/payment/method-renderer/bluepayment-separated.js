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

        grandTotalAmount: ko.observable(0),

        /**
         * Subscribe to grand totals
         */
        initObservable: function () {
            this._super();

            this.grandTotalAmount(parseFloat(quote.totals()['base_grand_total']).toFixed(2));
            quote.totals.subscribe(function () {
                if (this.grandTotalAmount() !== quote.totals()['base_grand_total']) {
                    this.grandTotalAmount(parseFloat(quote.totals()['base_grand_total']).toFixed(2));
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
            return this.gateway_name;
        },

        /**
         * Get gateway description.
         *
         * @returns {string|null}
         */
        getGatewayShortDescription: function () {
            return this.gateway_short_description;
        },

        /**
         * Get gateway help.
         *
         * @returns {string|null}
         */
        getGatewayHelp: function () {
            return this.gateway_description;
        },
    });
});
