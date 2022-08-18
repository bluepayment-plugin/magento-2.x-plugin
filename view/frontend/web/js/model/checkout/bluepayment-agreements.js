define([
    'ko',
    'underscore'
], function (ko, _) {
    'use strict';

    return {
        // Array of available regulations
        agreements: ko.observableArray([]),

        // Array of selected regulationIDs
        selected: ko.observableArray([]),

        getCheckedAgreementsIds: function () {
            return _.uniq(this.selected())
                .join(',');
        },
    };
});
