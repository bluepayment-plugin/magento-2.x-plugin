define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        var config = window.checkoutConfig.payment;

        if (config.autopay.isActive) {
            rendererList.push(
                {
                    type: 'autopay',
                    component: 'BlueMedia_BluePayment/js/view/payment/method-renderer/autopay'
                },
            );
        }

        return Component.extend({});
    }
);
