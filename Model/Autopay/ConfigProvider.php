<?php

namespace BlueMedia\BluePayment\Model\Autopay;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {
        return [];
    }

    public function isActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/autopay/active',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isTestMode(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/autopay/test_mode',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getServiceId()
    {
        return $this->scopeConfig->getValue(
            'payment/bluepayment/pln/service_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSecretKey()
    {
        return $this->scopeConfig->getValue(
            'payment/autopay/secret_key',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMerchantId()
    {
        return $this->scopeConfig->getValue(
            'payment/autopay/merchant_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getQuoteLifetime()
    {
        return $this->scopeConfig->getValue(
            'checkout/cart/delete_quote_after',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getLanguage(): string
    {
        $locale = $this->scopeConfig
            ->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE
            );

        return $this->getLanguageFromLocale($locale);
    }

    private function getLanguageFromLocale($locale): string
    {
        $locales = [
            'pl_' => 'pl', // polski
            'en_' => 'en', // angielski
        ];

        $prefix = substr($locale, 0, 3);

        if (isset($locales[$prefix])) {
            return $locales[$prefix];
        }

        return 'en';
    }
}
