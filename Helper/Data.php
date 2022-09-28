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
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Payment\Helper\Data
{
    public const FAILED_CONNECTION_RETRY_COUNT = 5;
    public const MESSAGE_ID_STRING_LENGTH = 32;

    /** @var Logger */
    public $logger;

    /** @var Client */
    public $apiClient;

    /** StoreManagerInterface $storeManager */
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
     * @param Logger $logger
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
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
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
        $this->storeManager = $storeManager;
    }


    /**
     * @param array $data
     *
     * @return string
     */
    public function generateAndReturnHash($data)
    {
        $algorithm = $this->scopeConfig->getValue(
            'payment/bluepayment/hash_algorithm',
            ScopeInterface::SCOPE_STORE
        );
        $separator = $this->scopeConfig->getValue(
            'payment/bluepayment/hash_separator',
            ScopeInterface::SCOPE_STORE
        );
        $values_array = array_values($data);
        $values_array_filter = array_filter(($values_array));
        $comma_separated = implode(",", $values_array_filter);
        $replaced = str_replace(",", $separator, $comma_separated);

        return hash($algorithm, $replaced);
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

    public function getLanguageFromLocale($locale)
    {
        $locales = [
            'pl_' => 'PL', // polski
            'en_' => 'EN', // angielski
            'de_' => 'DE', // niemiecki
            'cs_' => 'CS', // czeski
            'fr_' => 'FR', // francuski
            'it_' => 'IT', // włoski
            'es_' => 'ES', // hiszpański
            'sk_' => 'SK', // słowacki
            'ro_' => 'RO', // rumuński
            'uk_' => 'UK', // ukraiński
            'hu_' => 'HU', // węgierski
        ];

        $prefix = substr($locale, 0, 3);

        if (isset($locales[$prefix])) {
            return $locales[$prefix];
        }

        return 'PL';
    }
}
