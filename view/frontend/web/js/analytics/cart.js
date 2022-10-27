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
        var getProductData = async function (productId) {
            return await $.ajax({
                url: url.build('/bluepayment/analytics/getproductdetails'),
                type: 'POST',
                data: {
                    product_id: productId
                }
            })
        }

        $(document).on('ajax:addToCart', async function (event, data) {
            var productId = data.productIds[0],
                sku = data.sku,
                formData = new FormData(data.form[0]),
                qty = formData.get('qty') || 1;

            var response = await getProductData(productId);

            if (response) {
                var data = {
                    id: response.id,
                    name: response.name,
                    category: response.category,
                    price: response.price,
                    qty: qty
                };
                gtag('event', 'add_to_cart', {'items': [data]});
                console.log('event', 'add_to_cart', {'items': [data]});
            }
        });

        $(document).on('ajax:removeFromCart', async function (event, data) {
            var productId = data.productIds[0];

            var response = await getProductData(productId);

            if (response) {
                var data = {
                    id: response.id,
                    name: response.name,
                    category: response.category,
                    price: response.price
                };
                gtag('event', 'remove_from_cart', {'items': [data]});
                console.log('event', 'remove_from_cart', {'items': [data]});
            }
        });
    }
});
