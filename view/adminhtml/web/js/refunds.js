/**
 *
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/confirm',
    'domReady!'
], function ($) {
    'use strict';

    var refunds = {
        /**
         *
         */
        options: {
            popupId: null,
            confirmId: null,
            successId: null,
            form: {
                radioName: null,
                inputId: null,
                submitId: null,
                errorContainerId: null
            },
            refundUrl: null
        },
        /**
         *
         */
        _modal: undefined,
        /**
         *
         */
        _confirm: undefined,
        /**
         *
         */
        _response: undefined,
        /**
         *
         * @param config
         */
        initialize: function (config) {
            refunds.options = $.extend(refunds.options, config);
        },
        /**
         *
         */
        showPopup: function () {
            if (this._modal !== undefined) {
                this._modal.modal('openModal');
            } else {
                this._modal = $('<div />').html(this._getPopupContent(this.options.popupId))
                    .modal({
                        autoOpen: true,
                        responsive: true,
                        buttons: []
                    });
                this._addListeners();
            }
        },
        /**
         *
         */
        showConfirmation: function () {
            this._confirm = $('<div />').html(this._getPopupContent(this.options.confirmId))
                .confirm({
                    actions: {
                        confirm: this._onConfirmAction
                    }
                });
        },
        /**
         *
         * @private
         */
        _addListeners: function () {
            $(this._modal).on('change', '[name="' + this.options.form.radioName + '"]', this._onRadioChange);
            $(this._modal).on('click', '#' + this.options.form.submitId, this._onSubmit);
        },
        /**
         *
         * @private
         */
        _onRadioChange: function () {
            $('#' + refunds.options.form.submitId).removeAttr('disabled');
            var partialInput = $('#' + refunds.options.form.inputId);
            if (parseInt($(this).val()) === 1) {
                partialInput.removeAttr('readonly');
            } else {
                partialInput.attr('readonly', true);
            }
        },
        /**
         *
         * @returns {boolean}
         * @private
         */
        _onSubmit: function (e) {
            var self = refunds;
            var errorContainer = $('#' + self.options.form.errorContainerId);
            errorContainer.html('');

            var isPartialReturn = $(this).parents('form').find('[name="' + self.options.form.radioName + '"]:checked');
            if (!isPartialReturn.is(':checked')) {
                errorContainer.append($('<li />').html($.mage.__('Refund type is not selected.')));
                return false;
            }
            if (parseInt(isPartialReturn.val()) === 1
                && !/^\d+\.?\d{0,2}$/g.test($('#' + self.options.form.inputId).val())
            ) {
                errorContainer.append($('<li />').html($.mage.__('Invalid refund amount. Example of correct value: 123.23')));
                return false;
            }

            self._modal.modal('closeModal');
            self.showConfirmation();
            e.stopPropagation();
            e.preventDefault();
        },
        /**
         *
         * @private
         */
        _onConfirmAction: function () {
            var self = refunds;
            if (self.options.refundUrl === null) {
                console.error('BlueMedia_BluePayment error: 101');
                return;
            }

            $.ajax({
                url: self.options.refundUrl,
                data: self._modal.find('form').serializeArray(),
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                context: $('body'),
                cache: false,
                success: self._onConfirmSuccess,
                error: self._onConfirmError
            });
        },
        /**
         *
         * @param request
         * @private
         */
        _onConfirmSuccess: function (request) {
            var self = refunds;
            if (request.error === true) {
                self._onConfirmError(request);
                return;
            }
            this._response = $('<div />').html(self._getPopupContent(self.options.successId))
                .confirm({
                    actions: {
                        confirm: function() {location.reload()}
                    }
                });
        },
        /**
         *
         * @param request
         * @private
         */
        _onConfirmError: function (request) {
            if (request.error === true && request.message !== undefined) {
                $('<div />').html(request.message).alert();
            }
        },
        /**
         *
         * @returns {*|jQuery}
         * @private
         */
        _getPopupContent: function (element) {
            return $('#' + element).html();
        }
    };

    window.BlueMedia = window.BlueMedia || {};
    window.BlueMedia.BluePayment = refunds;
    return window.BlueMedia.BluePayment.initialize;
});