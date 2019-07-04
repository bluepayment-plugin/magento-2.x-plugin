/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global define*/
define(
    [
        'MagentoCheckoutOldBillingAddress'
    ],
    function (
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'BlueMedia_BluePayment/billing-address'
            }
        });
    }
);
