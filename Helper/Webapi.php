<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\Cache\AgreementsCache;
use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SimpleXMLElement;
use Zend\Uri\Http;

/**
 * Class Webapi
 */
class Webapi extends Data
{
    public const DEFAULT_HASH_SEPARATOR = '|';

    /** @var Http */
    public $zendUri;

    /** @var CacheInterface */
    public $cache;

    /** @var SerializerInterface */
    public $serializer;

    /**
     * Gateways constructor.
     *
     * @param  Context  $context
     * @param  LayoutFactory  $layoutFactory
     * @param  Factory  $paymentMethodFactory
     * @param  Emulation  $appEmulation
     * @param  Config  $paymentConfig
     * @param  Initial  $initialConfig
     * @param  Client  $apiClient
     * @param  Logger  $logger
     * @param  StoreManagerInterface  $storeManager
     * @param  CacheInterface  $cache
     * @param  SerializerInterface  $serializer
     * @param  Http  $zendUri
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
        CacheInterface $cache,
        SerializerInterface $serializer,
        Http $zendUri
    ) {
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
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * Get Google Pay Merchant info.
     *
     * @return array|bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function googlePayMerchantInfo()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        $url = $store->getBaseUrl();
        $merchantDomain = $this->zendUri->parse($url)->getHost();
        $currency = $store->getCurrentCurrency()->getCode();

        $serviceId = $this->getConfigValue('service_id', $currency);
        $sharedKey = $this->getConfigValue('shared_key', $currency);

        $data = [
            'ServiceID' => $serviceId,
            'MerchantDomain' => $merchantDomain
        ];

        return $this->callAPI(
            $data,
            $sharedKey,
            $this->getGPayMerchantInfoURL()
        );
    }

    /**
     * Get gateway list for service.
     *
     * @param  int  $serviceId
     * @param  string  $sharedKey
     * @param  string  $currency
     *
     * @return array|bool
     */
    public function gatewayList(int $serviceId, string $sharedKey, string $currency)
    {
        $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);
        $data = [
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'Currencies' => $currency
        ];

        return $this->callAPI(
            $data,
            $sharedKey,
            $this->getGatewayListUrl()
        );
    }

    /**
     * Get agreements for payment gateway.
     *
     * @param  int  $gatewayId
     * @param  string  $currency
     * @param  string  $locale
     *
     * @return array|bool
     */
    public function agreements(int $gatewayId, string $currency, string $locale)
    {
        $serviceId = $this->getConfigValue('service_id', $currency);
        $sharedKey = $this->getConfigValue('shared_key', $currency);

        $cacheKey = implode('_', [AgreementsCache::CACHE_TAG, $serviceId, $gatewayId, $locale]);

        if ($this->cache->getFrontend()->test($cacheKey)) {
            return $this->serializer->unserialize($this->cache->load($cacheKey));
        }

        $result = $this->callAPI(
            [
                'ServiceID' => $serviceId,
                'MessageID' => $this->randomString(self::MESSAGE_ID_STRING_LENGTH),
                'GatewayID' => $gatewayId,
                'Language' => $this->getLanguageFromLocale($locale)
            ],
            $sharedKey,
            $this->getLegalDataUrl()
        );

        $this->cache->save(
            $this->serializer->serialize($result),
            $cacheKey,
            [AgreementsCache::CACHE_TAG],
            (15 * 60)
        );

        return $result;
    }

    public function transactionStatus(int $serviceId, string $orderId, string $currency, StoreInterface $store)
    {
        return $this->callXMLApi(
            [
                'ServiceID' => $serviceId,
                'OrderID' => $orderId,
            ],
            $this->getConfigValue('shared_key', $currency, $store),
            $this->getTransactionStatusUrl()
        );
    }

    /**
     * @param  string  $name
     * @param  string|null  $currency
     * @param  StoreInterface|null  $store
     *
     * @return mixed
     */
    private function getConfigValue(string $name, string $currency = null, ?StoreInterface $store = null)
    {
        if ($currency) {
            return $this->scopeConfig->getValue(
                'payment/bluepayment/' . strtolower($currency) . '/' . $name,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return $this->scopeConfig->getValue(
            'payment/bluepayment/' . $name,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    private function getGPayMerchantInfoURL(): string
    {
        if ($this->getConfigValue('test_mode')) {
            return $this->getConfigValue('gpay_merchant_info_url_test');
        }

        return $this->getConfigValue('gpay_merchant_info_url_prod');
    }

    /**
     * @return string
     */
    private function getGatewayListUrl(): string
    {
        if ($this->getConfigValue('test_mode')) {
            return $this->getConfigValue('gateway_list_url_test');
        }

        return $this->getConfigValue('gateway_list_url_prod');
    }

    /**
     * @return string
     */
    private function getLegalDataUrl(): string
    {
        if ($this->getConfigValue('test_mode')) {
            return $this->getConfigValue('legal_data_url_test');
        }

        return $this->getConfigValue('legal_data_url_prod');
    }

    /**
     * @return string
     */
    private function getTransactionStatusUrl(): string
    {
        if ($this->getConfigValue('test_mode')) {
            return $this->getConfigValue('transaction_status_url_test');
        }

        return $this->getConfigValue('transaction_status_url_prod');
    }

    /**
     * @param array $data
     * @param  string  $hashKey
     * @param  string  $url
     *
     * @return SimpleXMLElement|false
     */
    private function callXMLApi(array $data, string $hashKey, string $url)
    {
        $data = $this->prepareData($data, $hashKey);

        try {
            return $this->apiClient->call($url, $data);
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }
    }

    /**
     * @param array $data
     * @param  string  $hashKey
     * @param  string  $url
     *
     * @return bool|array
     */
    private function callAPI(array $data, string $hashKey, string $url)
    {
        $data = $this->prepareData($data, $hashKey);

        try {
            return $this->apiClient->callJson($url, $data);
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }
    }

    /**
     * @param  array  $data
     * @param  string  $hashKey
     *
     * @return array
     */
    private function prepareData(array $data, string $hashKey): array
    {
        $hashMethod = $this->getConfigValue('hash_algorithm');
        $hashSeparator = $this->getConfigValue('hash_separator') ?? self::DEFAULT_HASH_SEPARATOR;
        $data['Hash'] = hash($hashMethod, implode($hashSeparator, array_merge(array_values($data), [$hashKey])));
        return $data;
    }

}
