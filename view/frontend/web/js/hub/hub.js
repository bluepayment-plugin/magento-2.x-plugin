/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/* jscs:disable */
/* eslint-disable */
define([
    // 'BlueMedia_BluePayment/js/hub/sdk',
], function () {
    'use strict';

    let whenAvailable = function(name, callback) {
        var interval = 10;
        window.setTimeout(function() {
            if (window[name]) {
                callback(window[name]);
            } else {
                this.whenAvailable(name, callback);
            }
        }.bind(this), interval);
    };

    /**
     * @param {Object} config
     */
    return function (config) {
        whenAvailable('SDKConfigurationBmHub', function(SDKConfigurationBmHub) {
            SDKConfigurationBmHub.setConfiguration(
                config.merchantId,
                config.productPriceClass,
                config.paymentPriceClass,
                config.paymentSelectorClass,
            );
        });
    }
});
