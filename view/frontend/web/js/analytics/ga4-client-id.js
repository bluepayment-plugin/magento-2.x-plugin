/* jscs:disable */
/* eslint-disable */
define([
    'jquery',
    'mage/url'
], function ($, url) {
    'use strict';

    /**
     * @param {Object} config
     */
    return function (config) {
        gtag('get', config.client_id, 'client_id', (client_id) => {
            $.ajax({
                url: url.build('/bluepayment/analytics/setclientid'),
                type: 'POST',
                data: {
                    client_id: client_id
                },
                error: function () {
                    console.error('Something went wrong');
                },
                success: function (data) {}
            });
        });
    }
});
