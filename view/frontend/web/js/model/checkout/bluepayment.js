define([
    'ko',
    'underscore',
], function (
    ko,
    _,
) {
    'use strict';

    return {
        /**
         * Selected gateway id
         *
         * @type {function}
         */
        selectedGatewayId: ko.observable(null),

        /**
         * Is order placed
         *
         * @type {function}
         */
        ordered: ko.observable(false),

        /**
         * Agreements data
         *
         * @type {function}
         */
        agreements: ko.observableArray([]),

        /**
         * Array of selected regulationIDs
         *
         * @type {function}
         */
        selectedAgreements: ko.observableArray([]),

        /**
         * Get checked agreements ids, separated by comma.
         *
         * @returns {string}
         */
        getCheckedAgreementsIds: function () {
            return _.uniq(this.selectedAgreements())
                .join(',');
        },

        /**
         * List of gateways ids based on gateway code
         */
        gatewaysIds: {
            blik: 509,
            smartney: 700,
            hub: 702,
            paypo: 705,
            card: 1500,
            one_click: 1503,
            alior_installments: 1506,
            google_pay: 1512,
            apple_pay: 1513,
            visa_mobile: 1523,
        },
    };
});
