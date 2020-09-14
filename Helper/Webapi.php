<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Logger\Logger;
use Exception;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zend\Uri\Http;

/**
 * Class Gateways
 */
class Webapi extends Data
{
    const DEFAULT_HASH_SEPARATOR = '|';

    /** @var Http */
    public $zendUri;

    /**
     * Gateways constructor.
     *
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param Client $apiClient
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Http $zendUri
     * @parama Http $zendUri
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Client $apiClient,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Http $zendUri
    )
    {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig,
            $apiClient,
            $logger,
            $storeManager
        );

        $this->zendUri = $zendUri;
    }

    /**
     * @return array|bool
     */
    public function googlePayMerchantInfo()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        $hashMethod = $this->getConfigValue('hash_algorithm');
        $GPayMerchantInfoURL = $this->getGPayMerchantInfoURL();

        $url = $store->getBaseUrl();
        $merchantDomain = $this->zendUri->parse($url)->getHost();

        $currency = $store->getCurrentCurrency()->getCode();

        if (in_array($currency, ['PLN', 'EUR'])) {
            $serviceId = $this->getConfigValue('service_id', $currency);
            $sharedKey = $this->getConfigValue('shared_key', $currency);

            return $this->callAPI(
                $hashMethod,
                $serviceId,
                $merchantDomain,
                $sharedKey,
                $GPayMerchantInfoURL
            );
        } else {
            return false;
        }
    }

    /**
     * @param string $name
     * @param string $currency
     *
     * @return mixed
     */
    public function getConfigValue($name, $currency = null)
    {
        $website = $this->storeManager->getWebsite();

        if ($currency) {
            return $this->scopeConfig->getValue(
                'payment/bluepayment/' . strtolower($currency) . '/' . $name,
                ScopeInterface::SCOPE_WEBSITE,
                $website->getCode()
            );
        }

        return $this->scopeConfig->getValue(
            'payment/bluepayment/' . $name,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getCode()
        );
    }

    /**
     * @return mixed
     */
    public function getGPayMerchantInfoURL()
    {
        if ($this->getConfigValue('test_mode')) {
            return $this->getConfigValue('test_address_gpay_merchant_info_url');
        }

        return $this->getConfigValue('prod_address_gpay_merchant_info_url');
    }

    /**
     * @param string $hashMethod
     * @param string $serviceId
     * @param string $merchantDomain
     * @param string $hashKey
     * @param string $apiUrl
     *
     * @return bool|array
     */
    public function callAPI($hashMethod, $serviceId, $merchantDomain, $hashKey, $apiUrl)
    {
        $data = [
            'ServiceID' => $serviceId,
            'MerchantDomain' => $merchantDomain
        ];
        $hashSeparator = $this->getConfigValue('hash_separator') ? $this->getConfigValue('hash_separator') :
            self::DEFAULT_HASH_SEPARATOR;
        $data['Hash'] = hash($hashMethod, implode($hashSeparator, array_merge(array_values($data), [$hashKey])));

        try {
            return (array)$this->apiClient->callJson($apiUrl, $data);
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }
    }
}
