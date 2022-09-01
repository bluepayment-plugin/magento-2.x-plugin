define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function (config) {
        var seconds = parseInt(config['seconds']);

        if (seconds < 1) {
            seconds = 5;
        }

        setTimeout(function() {
            window.location.href = config['redirectURL'];
        }, seconds * 1000)
    };
});
