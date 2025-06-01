define([
    'jquery',
    'ko',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'BlueMedia_BluePayment/js/view/payment/method-renderer/bluepayment-abstract',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment',
    'BlueMedia_BluePayment/js/model/checkout/bluepayment-config',
    'BlueMedia_BluePayment/js/checkout-data',
], function (
    $,
    ko,
    $t,
    quote,
    Component,
    model,
    config,
    checkoutData,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'BlueMedia_BluePayment/payment/bluepayment',
            logoUrl: config.logo,
        },

        gateways: config.options,
        testMode: config.testMode,
        bluePaymentCollapsible:
            config.collapsible === '1'
            && config.options.length > 8,
        selectedGatewayId: model.selectedGatewayId,
        collapsed: ko.observable(true),

        slides: [],
        slideIndex: 0,

        /**
         * Get payment method code
         *
         * @returns {string}
         */
        getCode: function () {
            return this.item.method;
        },

        /**
         * Get payment method data
         *
         * @returns {{additional_data: {separated: boolean, agreements_ids, gateway_id}, method}}
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'separated': false,
                    'gateway_id': model.selectedGatewayId(),
                    'agreements_ids': model.getCheckedAgreementsIds(),
                }
            };
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();

            const gatewayId = checkoutData.getBluepaymentGatewayId();
            if (gatewayId) {
                // Set selected gateway id fetched from checkout data
                model.selectedGatewayId(gatewayId);
            }
            model.selectedGatewayId.subscribe(function (value) {
                checkoutData.setBluepaymentGatewayId(value);
            });

            // Subscribe first to ensure changes are captured
            model.selectedGatewayId.subscribe(function (value) {
                // Only save if the current method is bluepayment to avoid overwriting other methods' data potentially
                const currentQuoteMethod = quote.paymentMethod();
                if (currentQuoteMethod && currentQuoteMethod.method === this.item.method) {
                    checkoutData.setBluepaymentGatewayId(value);
                }
            }.bind(this));

            setTimeout(() => {
                const storedGatewayId = checkoutData.getBluepaymentGatewayId();
                const currentQuoteMethod = quote.paymentMethod();

                if (currentQuoteMethod && currentQuoteMethod.method === this.item.method) {
                    const isSeparatedStored = config.separated.some(gateway => gateway.gateway_id === storedGatewayId);

                    if (storedGatewayId && !isSeparatedStored) {
                        model.selectedGatewayId(storedGatewayId);

                        if (!currentQuoteMethod.additional_data || currentQuoteMethod.additional_data.gateway_id !== storedGatewayId || currentQuoteMethod.additional_data.separated) {
                            const newData = currentQuoteMethod ?? {};
                            newData.additional_data = newData.additional_data || {};
                            newData.additional_data.gateway_id = storedGatewayId;
                            newData.additional_data.separated = false;
                            quote.paymentMethod(newData);
                        }
                    } else {
                        model.selectedGatewayId(null);

                        if (currentQuoteMethod.additional_data && currentQuoteMethod.additional_data.gateway_id && !currentQuoteMethod.additional_data.separated) {
                            const newData = currentQuoteMethod ?? {};
                            newData.additional_data.gateway_id = storedGatewayId;
                            newData.additional_data.separated = false;
                            quote.paymentMethod(newData);
                        }
                    }
                } else {
                    model.selectedGatewayId(null);
                }
            }, 250);

            // Slideshow
            this.initSlideshow();
        },

        /**
         * Override selectPaymentMethod method
         *
         * @returns {*}
         */
        selectPaymentMethod: function () {
            // Remove selected gateway id from checkout data
            model.selectedGatewayId(null);
            return this._super();
        },

        /**
         * Select gateway
         *
         * @param {object} value
         * @returns {boolean}
         */
        selectPaymentOption: function (value) {
            model.selectedGatewayId(value.gateway_id);
            return true;
        },

        /**
         * Is payment method checked
         */
        isChecked: ko.computed(function () {
            const paymentMethod = quote.paymentMethod();

            if (!paymentMethod) {
                return null;
            }

            if (paymentMethod.additional_data && paymentMethod.additional_data.separated) {
                return null;
            }

            return paymentMethod.method;
        }),

        /**
         * Get payment method title
         *
         * @returns {string}
         */
        getTitle: function () {
            if (this.gateways === false) {
                return $t('Quick payment');
            }

            return $t('Internet transfer');
        },

        /**
         * Get payment method description
         *
         * @returns {string|false}
         */
        getDescription: function () {
            if (this.gateways === false) {
                return $t('Internet transfer, BLIK, payment card, Google Pay, Apple Pay');
            }

            return false;
        },

        /**
         * @return {Boolean}
         */
        validate: function () {
            // Selected payment method validation
            if (this.gateways !== false && !this.selectedGatewayId()) {
                this.messageContainer.addErrorMessage({
                    message: $t('Choose the way you want to pay.')
                });
                return false;
            }

            return true;
        },

        /**
         * Initialize slideshow
         */
        initSlideshow: function () {
            if (document.querySelector('.blue-payment__slideshow')) {
                this.slideshowStart();
            } else {
                var self = this;

                var observer = new MutationObserver(function () {
                    var available = !!document.querySelector('.blue-payment__slideshow');

                    if (available) {
                        self.slideshowStart();
                        observer.disconnect();
                    }
                });

                observer.observe(document.querySelector('body'), {
                    childList: true,
                    subtree: true,
                });
            }
        },

        /**
         * Start slideshow
         */
        slideshowStart: function () {
            this.slides = document.querySelectorAll('.blue-payment__slideshow-slide');

            if (this.slides.length > 1) {
                this.slideshowGoTo(this.slideIndex);
                setInterval(() => {
                    this.slideIndex++;
                    if (this.slideIndex === this.slides.length) {
                        this.slideIndex = 0;
                    }

                    this.slideshowGoTo(this.slideIndex);
                }, 3000);
            }
        },

        /**
         * Go to slide
         * @param index
         */
        slideshowGoTo: function (index) {
            this.slides.forEach(slide => {
                slide.classList.remove('active');
            });
            this.slides[index].classList.add('active');
        },
    });
});
