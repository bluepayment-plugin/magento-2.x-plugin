define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'BlueMedia_BluePayment/js/model/agreements-validator'
    ],
    function (Component, additionalValidators, agreementsValidator) {
        'use strict';
        additionalValidators.registerValidator(agreementsValidator);

        return Component.extend({});
    }
);
