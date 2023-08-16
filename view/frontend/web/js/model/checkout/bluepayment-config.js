define(function () {
    'use strict';

    const config = window.checkoutConfig.payment.bluepayment;

    return {
        testMode: config?.test_mode ?? true,
        logo: config?.logo,
        iframeEnabled: config?.iframe_enabled ?? false,
        options: config?.options ?? [],
        separated: config?.separated ?? [],
        collapsible: config?.collapsible ?? false,
        cards: config?.cards ?? [],
        oneClickAgreement: config?.one_click_agreement ?? false,
        blikZeroEnabled: config?.blik_zero_enabled ?? true,
    };
});
