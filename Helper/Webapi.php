<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Gateways
 *
 * @package BlueMedia\BluePayment\Helper
 */
class Webapi extends Data
{
    const DEFAULT_HASH_SEPARATOR = '|';

    /** @var StoreManagerInterface */
    public $storeManager;

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
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Client $apiClient,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig,
            $apiClient
        );

        $this->storeManager = $storeManager;
    }

    /**
     * @param TransactionInterface $transaction
     * @param null$amount
     *
     * @return array
     * @throws EmptyRemoteIdException
     */
    public function googlePayMerchantInfo()
    {
        $store = $this->storeManager->getStore();

        $hashMethod   = $this->getConfigValue('hash_algorithm');
        $GPayMerchantInfoURL = $this->getGPayMerchantInfoURL();

        $url = $store->getBaseUrl();
        $merchantDomain = parse_url($url)['host'];

        $currency = $store->getCurrentCurrency()->getCode();

        if ($currency == 'PLN') {
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
            return null;
        }
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getConfigValue($name, $currency = null)
    {
        if ($currency) {
            return $this->scopeConfig->getValue('payment/bluepayment/'.strtolower($currency).'/'.$name);
        }

        return $this->scopeConfig->getValue('payment/bluepayment/'.$name);
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
     * @param $hashMethod
     * @param $serviceId
     * @param $messageId
     * @param $remoteId
     * @param $amount
     * @param $hashKey
     * @param $refundAPIUrl
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
        $data['Hash']  = hash($hashMethod, implode($hashSeparator, array_merge(array_values($data), [$hashKey])));

        try {
            return (array)$this->apiClient->callJson($apiUrl, $data);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            var_dump($e->getMessage());

            return false;
        }
    }
}
