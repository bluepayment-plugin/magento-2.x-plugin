<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use BlueMedia\BluePayment\Helper\Email as EmailHelper;
use BlueMedia\BluePayment\Model\GatewaysFactory;
use BlueMedia\BluePayment\Model\RefundTransactionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\App\Emulation;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

/**
 * Class Gateways
 *
 * @package BlueMedia\BluePayment\Helper
 */
class Webapi extends Data
{
    const DEFAULT_HASH_SEPARATOR = '|';

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
     * @param OrderFactory $orderFactory
     * @param RefundTransactionFactory $refundTransactionFactory
     * @param RefundTransactionRepositoryInterface $refundTransactionRepository
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Client $apiClient
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
        $hashMethod   = $this->getConfigValue('hash_algorithm');
        $refundAPIUrl = $this->getGPayMerchantInfoURL();

//        $order     = $this->orderFactory->create()->loadByIncrementId($transaction->getOrderId());
        $serviceId = $this->getConfigValue('service_id', 'PLN');
        $sharedKey = $this->getConfigValue('shared_key', 'PLN');

        return $this->callAPI(
            $hashMethod,
            $serviceId,
            'magento2.bm.devmouse.pl',
            $sharedKey,
            $refundAPIUrl
        );
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
