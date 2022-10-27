define([
    'jquery',
    'ko',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment',
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

    var widget;

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/multishipping/bluepayment',
            gatewayId: ko.observable(null),
        },
        imports: {
            onActiveChange: 'active'
        },

        initialize: function () {
            widget = this;
            return this._super();
        },

        initObservable: function () {
            // Observable is needed only once
            agreements.selected.subscribe(function () {
                this.agreementChanged();
            }.bind(this));

            return this._super();
        },

        /**
         * Trigger order placing
         */
        placeOrderClick: function () {
            // Selected payment method validation
            if (this.renderSubOptions !== false && _.isNull(this.gatewayId())) {
                this.validationFailed(true);
                return false;
            }

            return true;
        },

        /**
         * @override
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'gateway_id': this.gatewayId(),
                    'agreements_ids': agreements.getCheckedAgreementsIds(),
                }
            };
        },

        agreementChanged: function () {
            setPaymentInformationAction(this.messageContainer, this.getData());
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

            return true;
        },

        /**
         * @override
         */
        selectPaymentOption: function (value) {
            widget.setBlueMediaGatewayMethod(value);
            return true;
        },

        /**
         * @override
         */
        setBlueMediaGatewayMethod: function (value) {
            this.gatewayId(value.gateway_id);
            setPaymentInformationAction(this.messageContainer, this.getData());
            selectedGateway(value);

            return true;
        },
    });
});
