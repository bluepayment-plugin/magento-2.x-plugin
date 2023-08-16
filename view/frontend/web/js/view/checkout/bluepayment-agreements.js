define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
], function (
    $,
    ko,
    Component,
    url,
    model,
) {
    'use strict';

    model.selectedGatewayId.subscribe(function (gatewayId) {
        // Singleton - refresh agreement when gateway changed
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
                model.selectedAgreements([]);

                if (!response.hasOwnProperty('error')) {
                    model.agreements(response);

                    response.forEach(function (agreement) {
                        agreement['labelList'].forEach(function (label) {
                            if (label['showCheckbox'] === false) {
                                model.selectedAgreements.push(agreement['regulationID']);
                            }
                        });
                    });
                }
            });
        }
    }, this);

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/checkout/bluepayment-agreements',
        },
        agreements: model.agreements,
        selected: model.selectedAgreements,
    });
});
