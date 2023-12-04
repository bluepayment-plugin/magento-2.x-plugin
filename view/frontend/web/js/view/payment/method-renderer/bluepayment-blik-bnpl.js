define([
    'jquery',
    'ko',
    'mage/translate',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/modal/modal',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-separated',
], function (
    $,
    ko,
    $t,
    url,
    quote,
    modal,
    Component,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment-blik-bnpl',
            gateway_id: null,
            gateway_logo_url: null,
            gateway_name: null,
            gateway_description: null,
        },

        modalSelector: '#blik-bnpl-modal',
        modalOptions: {
            type: 'popup',
            wrapperClass: 'blik-bnpl-modal-wrapper',
            responsive: true,
            title: false,
            buttons: [],
        },

        expanded: ko.observable(false),

        // Modal
        initializeModal: function () {
            $(this.modalSelector).modal(this.modalOptions);

            let self = this;
            // Add custom ESC key event
            $(document).on('keydown', function (event) {
                if (event.keyCode === 27) {
                    self.closeModal();
                }
            });
        },

        openModal: function () {
            $(this.modalSelector).modal('openModal');
        },

        closeModal: function () {
            $(this.modalSelector).modal('closeModal');
        },

        expand: function () {
            this.expanded(true);
        },

        collapse: function () {
            this.expanded(false);
        },
    });
});
