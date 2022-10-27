define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/select-payment-method',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-selected-gateway',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-agreements'
], function (
    $,
    ko,
    Component,
    fullScreenLoader,
    setPaymentInformationAction,
    additionalValidators,
    selectPaymentMethodAction,
    selectedGateway,
    agreements
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
                    'agreements_ids': agreements.getCheckedAgreementsIds(),
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
            selectedGateway(this.item);

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
    });
});
