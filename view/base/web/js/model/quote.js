/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote'
    ],
    function (ko, $, quote) {
        'use strict';

        var blueMediaMethod = ko.observable(null);

        return $.extend(quote, {
            blueMediaMethod: blueMediaMethod,
            setBlueMediaPaymentMethod: function (paymentMethodCode) {
                blueMediaMethod(paymentMethodCode);
            },
            getBlueMediaPaymentMethod: function () {
                return blueMediaMethod;
            }
        });
    }
);
