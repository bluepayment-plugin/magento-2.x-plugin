<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Logger\Logger;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;

class Data extends \Magento\Payment\Helper\Data
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH = 32;

    /**
     * Logger
     *
     * @var Logger
     */
    public $logger;

    /**
     * @var Client
     */
    public $apiClient;

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
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Client $apiClient,
        Logger $logger
    )
    {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );

        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }


    /**
     * @param array $data
     *
     * @return string
     */
    public function generateAndReturnHash($data)
    {
        $algorithm = $this->scopeConfig->getValue("payment/bluepayment/hash_algorithm");
        $separator = $this->scopeConfig->getValue("payment/bluepayment/hash_separator");
        $values_array = array_values($data);
        $values_array_filter = array_filter(($values_array));
        $comma_separated = implode(",", $values_array_filter);
        $replaced = str_replace(",", $separator, $comma_separated);
        $hash = hash($algorithm, $replaced);

        return $hash;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function randomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}
