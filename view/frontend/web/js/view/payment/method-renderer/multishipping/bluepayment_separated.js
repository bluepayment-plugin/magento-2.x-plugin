define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/select-payment-method',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
], function (
    $,
    ko,
    Component,
    fullScreenLoader,
    setPaymentInformationAction,
    additionalValidators,
    selectPaymentMethodAction,
    model,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/multishipping/bluepayment_separated'
        },

        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'gateway_id': this.item.gateway_id,
                    'agreements_ids': model.getCheckedAgreementsIds(),
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

        selectPaymentMethod: function () {
            model.selectedGatewayId(this.item);
            selectPaymentMethodAction(this.getData());
            setPaymentInformationAction(this.messageContainer, this.getData());

            return true;
        },

        canUseApplePay: function() {
            return window.ApplePaySession && ApplePaySession.canMakePayments();
        },
    });
});
