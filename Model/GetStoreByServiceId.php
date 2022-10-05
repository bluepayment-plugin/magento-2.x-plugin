<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Helper\Gateways;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetStoreByServiceId
{
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param  string  $serviceIdToCheck
     *
     * @return array<StoreInterface, string>|false
     */
    public function execute(string $serviceIdToCheck)
    {
        foreach ($this->storeManager->getStores() as $store) {
            foreach (Gateways::$currencies as $currency) {
                $serviceId = (string) $this->scopeConfig->getValue(
                    'payment/bluepayment/'.strtolower($currency).'/service_id',
                    ScopeInterface::SCOPE_STORE,
                    $store->getCode()
                );

                if ($serviceIdToCheck === $serviceId) {
                    return [$store, $currency];
                }
            }
        }

        return false;
    }
}
