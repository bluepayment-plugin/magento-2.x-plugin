define(function () {
    'use strict';

    const config = window.checkoutConfig.payment.bluepayment;

    return {
        testMode: config.test_mode || true,
        logo: config.logo || 'https://bm.pl/img/www/logos/bmLogo.png',
        iframeEnabled: config.iframe_enabled || false,
        options: config.options || [],
        separated: config.separated || [],
        collapsible: config.collapsible || false,
        cards: config.cards || [],
        autopayAgreement: config.autopay_agreement || false,
    };
});
