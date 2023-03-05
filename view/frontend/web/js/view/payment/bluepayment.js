define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
], function (
    Component,
    rendererList,
    bluepaymentConfig,
    model,
) {
    'use strict';

    const bluepaymentType = 'bluepayment';
    const comparator = function (type, method) {
        return method === bluepaymentType;
    }

    // Prepend - only frame
    rendererList.push({
        type: bluepaymentType + '-prepend',
        component: 'uiComponent',
        config: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-prepend',
        },
        typeComparatorCallback: comparator
    });

    let baseRendered = false;

    if (bluepaymentConfig.separated) {
        bluepaymentConfig.separated.forEach(function (method) {
            let component;
            switch (Number(method.gateway_id)) {
                case model.gatewaysIds.blik:
                    component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-blik';
                    break;
                case model.gatewaysIds.card:
                    component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-card';
                    break;
                case model.gatewaysIds.one_click:
                    component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-one-click';
                    break;
                case model.gatewaysIds.google_pay:
                    component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-google-pay';
                    break;
                case model.gatewaysIds.apple_pay:
                    component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-apple-pay';
                    break;
                default:
                    component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated';
                    break;
            }

            rendererList.push({
                type: bluepaymentType + '-' + method.gateway_id,
                component: component,
                typeComparatorCallback: comparator,
                config: {
                    gateway_id: method.gateway_id,
                    gateway_logo_url: method.logo_url,
                    gateway_name: method.name,
                    gateway_description: method.description
                }
            });

            if (method.sort_order >= 0 && !baseRendered) {
                // @ToDo sorting in admin panel

                rendererList.push({
                    type: bluepaymentType,
                    component: 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment',
                    typeComparatorCallback: comparator
                });

                baseRendered = true;
            }
        });
    }

    if (!baseRendered) {
        rendererList.push({
            type: bluepaymentType,
            component: 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment'
        });
        baseRendered = true;
    }

    // Append - only frame
    rendererList.push({
        type: bluepaymentType + '-append',
        component: 'uiComponent',
        config: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-append',
        },
        typeComparatorCallback: comparator
    });

    return Component.extend({});
});
