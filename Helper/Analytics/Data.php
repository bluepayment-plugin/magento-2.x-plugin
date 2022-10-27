<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Helper\Analytics;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ACTIVE = 'google/analytics/active';
    public const XML_PATH_ACCOUNT = 'google/analytics/account';
    public const XML_PATH_ACCOUNT_GA4 = 'google/analytics/account_ga4';
    public const XML_PATH_ANONYMIZE = 'google/analytics/anonymize';
    public const XML_PATH_API_SECRET = 'google/analytics/api_secret';

    /**
     * Check is old Google Analytics is active.
     *
     * @param null|StoreInterface|int $store
     * @return bool
     */
    public function isGoogleAnalyticsAvailable($store = null): bool
    {
        $accountId = $this->getAccountId();
        return $accountId && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Check is Google Analytics 4 available.
     *
     * @param null|StoreInterface|int $store
     * @return bool
     */
    public function isGoogleAnalytics4Available($store = null): bool
    {
        $accountId = $this->getAccountIdGa4($store);
        return $accountId && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns Account ID for old Google Analytics .
     *
     * @param null|StoreInterface|int $store
     * @return string|null
     */
    public function getAccountId($store = null): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns Account ID for Google Analytics 4.
     *
     * @param null|StoreInterface|int $store
     * @return string|null
     */
    public function getAccountIdGa4($store = null): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT_GA4, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns API Secret for Google Analytics 4.
     *
     * @param null|StoreInterface|int $store
     * @return string|null
     */
    public function getApiSecret($store = null): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_SECRET, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns whether IP address anonymization is enabled.
     *
     * @param null|StoreInterface|int $store
     * @return bool
     */
    public function isAnonymizedIpActive($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ANONYMIZE, ScopeInterface::SCOPE_STORE, $store);
    }
}
