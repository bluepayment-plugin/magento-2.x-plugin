<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;

/**
 * Class Data
 *
 * @package BlueMedia\BluePayment\Helper
 */
class Data extends \Magento\Payment\Helper\Data
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH      = 32;

    /**
     * Logger
     *
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @var \BlueMedia\BluePayment\Api\Client
     */
    protected $apiClient;

    /**
     * Gateways constructor.
     *
     * @param \Magento\Framework\App\Helper\Context   $context
     * @param \Magento\Framework\View\LayoutFactory   $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory   $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation      $appEmulation
     * @param \Magento\Payment\Model\Config           $paymentConfig
     * @param \Magento\Framework\App\Config\Initial   $initialConfig
     * @param \BlueMedia\BluePayment\Api\Client $apiClient
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
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
        $this->apiClient = $apiClient;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function generateAndReturnHash($data)
    {
        $algorithm           = $this->scopeConfig->getValue("payment/bluepayment/hash_algorithm");
        $separator           = $this->scopeConfig->getValue("payment/bluepayment/hash_separator");
        $values_array        = array_values($data);
        $values_array_filter = array_filter(($values_array));
        $comma_separated     = implode(",", $values_array_filter);
        $replaced            = str_replace(",", $separator, $comma_separated);
        $hash                = hash($algorithm, $replaced);

        return $hash;
    }

    /**
     * @param $length
     *
     * @return string
     */
    protected function randomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}
