define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-agreements',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-selected-gateway'
], function (
    $,
    ko,
    Component,
    url,
    agreements,
    selectedGateway
) {
    'use strict';

    selectedGateway.subscribe(function () {
        // Singleton - refresh agreement when gateway changed

        var gatewayId = selectedGateway()?.gateway_id;
        if (gatewayId) {
            $.ajax({
                showLoader: true,
                url: url.build('bluepayment/processing/agreements'),
                type: 'GET',
                data: {
                    'gateway_id': gatewayId
                },
                dataType: 'json'
            }).done(function (response) {
                agreements.selected([]);

                if (!response.hasOwnProperty('error')) {
                    agreements.agreements(response);

                    response.forEach(function (agreement) {
                        agreement['labelList'].forEach(function (label) {
                            if (label['showCheckbox'] === false) {
                                agreements.selected.push(agreement['regulationID']);
                            }
                        });
                    });
                }
            });
        }
    }, this);

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/checkout/bluepayment-agreements'
        },
        agreements: agreements.agreements,
        selected: agreements.selected
    });
});
