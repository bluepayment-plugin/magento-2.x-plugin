define([
    'jquery',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data'
], function ($, checkoutData, storage) {
    'use strict';

    const cacheKey = 'checkout-data';

    const getData = function () {
        return storage.get(cacheKey)();
    };

    const saveData = function (checkoutData) {
        storage.set(cacheKey, checkoutData);
    };

    return $.extend(checkoutData, {
        setBluepaymentGatewayId: function (data) {
            const obj = getData();

            obj.bluepaymentGatewayId = data;
            saveData(obj);
        },
        getBluepaymentGatewayId: function () {
            return getData().bluepaymentGatewayId || null;
        },
        setCardIndex: function (index) {
            const obj = getData();

            obj.card_index = index;
            saveData(obj);
        },
        getCardIndex: function () {
            return getData().card_index || null;
        }
    });
});
