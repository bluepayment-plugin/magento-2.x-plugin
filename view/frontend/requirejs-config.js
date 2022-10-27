var config = {
    config: {
        mixins: {
            'Magento_Customer/js/customer-data': {
                'BlueMedia_BluePayment/js/customer/customer-data-mixin': true
            }
        }
    },
    map: {
        '*': {
            'Magento_Checkout/js/model/place-order': 'BlueMedia_BluePayment/js/model/place-order',
            autopayShortcut: 'BlueMedia_BluePayment/js/view/shortcut',
        }
    }
};
