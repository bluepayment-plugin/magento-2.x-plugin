<?php

namespace BlueMedia\BluePayment\Helper\Analytics;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ACTIVE = 'google/analytics/active';
    public const XML_PATH_ACCOUNT = 'google/analytics/account';
    public const XML_PATH_ACCOUNT_GA4 = 'google/analytics/account_ga4';
    public const XML_PATH_ANONYMIZE = 'google/analytics/anonymize';
    public const XML_PATH_API_SECRET = 'google/analytics/api_secret';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function isGoogleAnalyticsAvailable($store = null): bool
    {
        $accountId = $this->getAccountId();
        return $accountId && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $store);
    }

    public function isGoogleAnalytics4Available($store = null): bool
    {
        $accountId = $this->getAccountIdGa4($store);
        return $accountId && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $store);
    }

    public function getAccountId($store = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT, ScopeInterface::SCOPE_STORE, $store);
    }

    public function getAccountIdGa4($store = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT_GA4, ScopeInterface::SCOPE_STORE, $store);
    }

    public function getApiSecret($store = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_SECRET, ScopeInterface::SCOPE_STORE, $store);
    }

    public function isAnonymizedIpActive($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ANONYMIZE, ScopeInterface::SCOPE_STORE, $store);
    }
}
