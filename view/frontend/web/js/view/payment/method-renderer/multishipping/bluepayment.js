/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/

define([
    'jquery',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/select-payment-method',
    'BlueMedia_BluePayment/js/checkout-data'
], function (
    $,
    Component,
    fullScreenLoader,
    setPaymentInformationAction,
    additionalValidators,
    selectPaymentMethodAction,
    checkoutData
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/multishipping/bluepayment',
            gatewayId: null
        },

        initialize: function (config) {
            this._super();
        },

        /**
         * @override
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'gateway_id': this.gatewayId
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

            return true;
        },

        /**
         * @override
         */
        setBlueMediaGatewayMethod: function (value) {
            this.gatewayId = value.gateway_id;
            setPaymentInformationAction(this.messageContainer, this.getData());

            return true;
        },
    });
});
