define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    var agreementsInputPath = '.payment-method._active div.bluepayment-agreements-block input';

    return {
        /**
         * Validate checkout agreements
         *
         * @returns {Boolean}
         */
        validate: function (hideError) {
            var isValid = true;

            if ($(agreementsInputPath).length === 0) {
                return true;
            }

            $(agreementsInputPath).each(function (index, element) {
                if (element.hasAttribute('required')) {
                    if (!$.validator.validateSingleElement(element, {
                        errorElement: 'div',
                        hideError: hideError || false
                    })) {
                        isValid = false;
                    }
                }
            });

            return isValid;
        }
    };
});
