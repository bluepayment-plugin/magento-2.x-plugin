define([
    'jquery',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data'
], function ($, checkoutData, storage) {
    'use strict';

    var cacheKey = 'checkout-data';

    var getData = function () {
        return storage.get(cacheKey)();
    };

    var saveData = function (checkoutData) {
        storage.set(cacheKey, checkoutData);
    };

    return $.extend(checkoutData, {
        setBlueMediaPaymentMethod: function (data) {
            var obj = getData();

            obj.blueMediaSelectedPaymentMethod = data;
            saveData(obj);
        },
        getBlueMediaPaymentMethod: function () {
            return getData().blueMediaSelectedPaymentMethod;
        },
        setIndividualGatewayFlag: function (data) {
            var obj = getData();

            obj.individual_gateway = data;
            saveData(obj);
        },
        getIndividualGatewayFlag: function () {
            return getData().individual_gateway;
        },
        setCardIndex: function (index) {
            var obj = getData();

            obj.card_index = index;
            saveData(obj);
        },
        getCardIndex: function () {
            return getData().card_index;
        }
    });
});
