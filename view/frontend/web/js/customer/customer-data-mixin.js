define(['ko', 'jquery', 'Magento_Customer/js/section-config', 'mage/url'],
    function (ko, $, sectionConfig, url) {
        'use strict';
        return function (customerData) {
            var options = {};

            url.setBaseUrl(window.BASE_URL);
            options.sectionLoadUrl = url.build('customer/section/load');

            var dataProvider = {
                /**
                 * @param {Object} sectionNames
                 * @param {Boolean} forceNewSectionTimestamp
                 * @return {*}
                 */
                getFromServer: function (sectionNames, forceNewSectionTimestamp) {
                    var parameters;

                    sectionNames = sectionConfig.filterClientSideSections(sectionNames);
                    parameters = _.isArray(sectionNames) && sectionNames.indexOf('*') < 0 ? {
                        sections: sectionNames.join(',')
                    } : [];
                    parameters['force_new_section_timestamp'] = forceNewSectionTimestamp;

                    return $.getJSON(options.sectionLoadUrl, parameters).fail(function (jqXHR) {
                        throw new Error(jqXHR);
                    });
                }
            };

            customerData.reload = function (sectionNames, forceNewSectionTimestamp) {
                return dataProvider.getFromServer(sectionNames, forceNewSectionTimestamp).done(function (sections) {
                    $(document).trigger('customer-data-reload', [sectionNames]);

                    Object.entries(sections).forEach(function ([id, section]) {
                        customerData.set(id, section);
                    });

                    $(document).trigger('customer-data-reloaded', [sectionNames]);
                });
            };

            return customerData;
        }
    }
)
