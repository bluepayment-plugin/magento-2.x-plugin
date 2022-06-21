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
            'payment/bluepayment/pln/merchant_id',
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
}
