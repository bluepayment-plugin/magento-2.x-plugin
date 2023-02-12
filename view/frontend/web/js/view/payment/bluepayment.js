define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-gateways'
], function (
    Component,
    rendererList,
    bluepaymentGateways
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
    if (window.checkoutConfig.payment.bluepayment.separated) {
        window.checkoutConfig.payment.bluepayment.separated.forEach(function (method) {
            let component;
            switch (method.gateway_id) {
                // case bluepaymentGateways.ids.blik:
                //     component = 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-blik';
                //     break;
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
                    gateway_logo_url: method.gateway_logo_url,
                    gateway_name: method.gateway_name,
                    gateway_description: method.gateway_description
                }
            });

            if (method.sort_order >= 10 && !baseRendered) {
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
