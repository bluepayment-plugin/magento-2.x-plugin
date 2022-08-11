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
        // let hubDialog = document.createElement('div');
        // hubDialog.id = 'bmhub_OpenCalculatorDialog';
        // document.body.appendChild(hubDialog);
        whenAvailable('SDKConfigurationBmHub', function(SDKConfigurationBmHub) {
            SDKConfigurationBmHub.setConfiguration(
                1234,
                'price',
                'total-price-in-checkout',
                'payment-selector-hub'
            );
        });
    }
});
