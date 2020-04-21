define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function (config) {
        function handleStatus(status, params)
        {
            if (status === 'SUCCESS' || status === 'FAILURE') {
                window.location.href = urlBuilder.build('bluepayment/processing/back')
                    + '?ServiceID=' + params.ServiceID
                    + '&OrderID=' + params.OrderID
                    + '&Hash=' + params.hash;

            } else {
                setTimeout(function () {
                    updateStatus();
                }, 2000);
            }
        }

        function updateStatus() {
            $.ajax({
                showLoader: false,
                url: urlBuilder.build('/bluepayment/processing/blik'),
                data: {
                    'ServiceID': config['ServiceID'],
                    'OrderID': config['OrderID'],
                    'Hash': config['Hash']
                },
                type: 'GET',
                dataType: "json"
            }).done(function (response) {
                handleStatus(response.Status, response);
            });
        }


        if (config['Status'] !== 'SUCCESS' && config['status'] !== 'FAILURE') {
            updateStatus();
        }
    };
});
