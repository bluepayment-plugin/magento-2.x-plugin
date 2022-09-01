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
            obj.bmSelectedPaymentMethod = data;
            saveData(obj);
        },
        getBlueMediaPaymentMethod: function () {
            return getData().bmSelectedPaymentMethod;
        },
        setIndividualGatewayFlag: function (data) {
            var obj = getData();
            obj.bmIndividualGatewayFlag = data;
            saveData(obj);
        },
        getIndividualGatewayFlag: function () {
            return getData().bmIndividualGatewayFlag;
        },
        setCardIndex: function (index) {
            var obj = getData();
            obj.bmCardIndex = index;
            saveData(obj);
        },
        getCardIndex: function () {
            return getData().bmCardIndex;
        },
        setHubToken: function (token) {
            var obj = getData();
            obj.bmHubToken = token;
            saveData(obj);
        },
        getHubToken: function () {
            return getData().bmHubToken;
        }
    });
});
