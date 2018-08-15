/*browser:true*/
/*global define*/
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
        rendererList.push(
            {
                type: 'bluepayment',
                component: 'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
