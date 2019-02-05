<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Model\GatewaysFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;
use BlueMedia\BluePayment\Helper\Email as EmailHelper;

/**
 * Class Gateways
 *
 * @package BlueMedia\BluePayment\Helper
 */
class Gateways extends Data
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH      = 32;
    const UPLOAD_PATH                   = '/BlueMedia/';

    public $currencies = [
        'PLN', 'EUR', 'GBP', 'USD'
    ];

    /**
     * Gateways model factory
     *
     * @var \BlueMedia\BluePayment\Model\GatewaysFactory
     */
    protected $_gatewaysFactory;

    /**
     * Logger
     *
     * @var \Zend\Log\Logger
     */
    protected $_logger;


    /**
     * @var Email
     */
    protected $_emailHelper;

    /**
     * Gateways constructor.
     *
     * @param \Magento\Framework\App\Helper\Context        $context
     * @param \Magento\Framework\View\LayoutFactory        $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory        $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation           $appEmulation
     * @param \Magento\Payment\Model\Config                $paymentConfig
     * @param \Magento\Framework\App\Config\Initial        $initialConfig
     * @param \BlueMedia\BluePayment\Model\GatewaysFactory $gatewaysFactory
     * @param \BlueMedia\BluePayment\Api\Client            $apiClient
     * @param EmailHelper                                  $emailHelper
     */
    public function __construct(
        Context         $context,
        LayoutFactory   $layoutFactory,
        Factory         $paymentMethodFactory,
        Emulation       $appEmulation,
        Config          $paymentConfig,
        Initial         $initialConfig,
        GatewaysFactory $gatewaysFactory,
        Client          $apiClient,
        EmailHelper     $emailHelper
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig, $apiClient);
        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
        $this->_gatewaysFactory = $gatewaysFactory;
        $this->_emailHelper     = $emailHelper;
    }

    /**
     * @return array
     */
    public function syncGateways()
    {
        $result            = [];
        $hashMethod        = $this->scopeConfig->getValue("payment/bluepayment/hash_algorithm");
        $gatewayListAPIUrl = $this->getGatewayListUrl();

        foreach ($this->currencies as $currency) {
            $serviceId = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
            $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);
            $hashKey = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");

            if ($serviceId) {
                $tryCount = 0;
                $loadResult = false;
                while (!$loadResult) {
                    $loadResult = $this->loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey,
                        $gatewayListAPIUrl);

                    if ($loadResult) {
                        $result['success'] = $this->saveGateways((array)$loadResult, $currency);
                        break;
                    } else {
                        if ($tryCount >= self::FAILED_CONNECTION_RETRY_COUNT) {
                            $result['error'] = 'Exceeded the limit of attempts to sync gateways list!';
                            break;
                        }
                    }
                    $tryCount++;
                }
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getGatewayListUrl()
    {
        $mode = $this->scopeConfig->getValue("payment/bluepayment/test_mode");
        if ($mode) {
            return $this->scopeConfig->getValue("payment/bluepayment/test_address_gateways_url");
        }

        return $this->scopeConfig->getValue("payment/bluepayment/prod_address_gateways_url");
    }

    /**
     * @param string $hashMethod
     * @param string $serviceId
     * @param int    $messageId
     * @param string $hashKey
     * @param string $gatewayListAPIUrl
     *
     * @return bool|\SimpleXMLElement
     */
    private function loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl)
    {
        $hash   = hash($hashMethod, $serviceId . '|' . $messageId . '|' . $hashKey);
        $data   = [
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'Hash'      => $hash,
        ];

        try {
            return $this->apiClient->call($gatewayListAPIUrl, $data);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());

            return false;
        }
    }

    /**
     * @param array $gatewayList
     */
    private function saveGateways($gatewayList, $currency = 'PLN')
    {
        $existingGateways          = $this->getSimpleGatewaysList();
        $currentlyActiveGatewayIDs = [];

        if (isset($gatewayList['gateway'])) {
            if (is_array($gatewayList['gateway'])) {
                $gatewayXMLObjects = $gatewayList['gateway'];
            } else {
                $gatewayXMLObjects = [$gatewayList['gateway']];
            }

            foreach ($gatewayXMLObjects as $gatewayXMLObject) {
                $gateway = (array)$gatewayXMLObject;
                if (isset($gateway['gatewayID'])
                    && isset($gateway['gatewayName'])
                    && isset($gateway['gatewayType'])
                    && isset($gateway['bankName'])
                    && isset($gateway['statusDate'])
                ) {
                    $currentlyActiveGatewayIDs[] = $gateway['gatewayID'];

                    if (isset($existingGateways[$currency][$gateway['gatewayID']])) {
                        $gatewayModel = $this->_gatewaysFactory->create();
                        $gatewayModel->load($existingGateways[$currency][$gateway['gatewayID']]['entity_id']);
                    } else {
                        $gatewayModel = $this->_gatewaysFactory->create();
                        $gatewayModel->setData('force_disable', 0);
                    }

                    $gatewayModel->setData('gateway_currency', $currency);
                    $gatewayModel->setData('gateway_id', $gateway['gatewayID']);
                    $gatewayModel->setData('gateway_status', 1);
                    $gatewayModel->setData('bank_name', $gateway['bankName']);
                    $gatewayModel->setData('gateway_name', $gateway['gatewayName']);
                    $gatewayModel->setData('gateway_type', $gateway['gatewayType']);
                    $gatewayModel->setData('gateway_logo_url', isset($gateway['iconURL']) ? $gateway['iconURL'] : null);
                    $gatewayModel->setData('status_date', $gateway['statusDate']);
                    try {
                        $gatewayModel->save();
                    } catch (\Exception $e) {
                        $this->_logger->info($e->getMessage());
                    }
                }
            }

            $disabledGateways = [];
            foreach ($existingGateways[$currency] as $existingGatewayId => $existingGatewayData) {
                if (!in_array($existingGatewayId, $currentlyActiveGatewayIDs)
                    && $existingGatewayData['gateway_status'] != 0
                ) {
                    $gatewayModel = $this->_gatewaysFactory->create()->load($existingGatewayData['entity_id']);
                    $gatewayModel->setData('gateway_status', 0);
                    try {
                        $gatewayModel->save();
                        $disabledGateways[] = [
                            'gateway_name' => $existingGatewayData['gateway_name'],
                            'gateway_id'   => $existingGatewayId,
                        ];
                    } catch (\Exception $e) {
                        $this->_logger->info($e->getMessage());
                    }
                }
            }
            if (!empty($disabledGateways)) {
                $this->_emailHelper->sendGatewayDeactivationEmail($disabledGateways);
            }
        }
    }

    /**
     * @return array
     */
    public function getSimpleGatewaysList()
    {
        $objectManager          = ObjectManager::getInstance();
        $bluegatewaysCollection = $objectManager->create(Collection::class);
        $bluegatewaysCollection->load();

        $existingGateways = [];

        foreach ($this->currencies as $currency) {
            $existingGateways[$currency] = [];
        }

        foreach ($bluegatewaysCollection as $blueGateways) {
            $existingGateways[$blueGateways->getData('gateway_currency')][$blueGateways->getData('gateway_id')] = [
                'entity_id'           => $blueGateways->getId(),
                'gateway_currency'    => $blueGateways->getData('gateway_currency'),
                'gateway_status'      => $blueGateways->getData('gateway_status'),
                'bank_name'           => $blueGateways->getData('bank_name'),
                'gateway_name'        => $blueGateways->getData('gateway_name'),
                'gateway_description' => $blueGateways->getData('gateway_description'),
                'gateway_sort_order'  => $blueGateways->getData('gateway_sort_order'),
                'gateway_type'        => $blueGateways->getData('gateway_type'),
                'gateway_logo_url'    => $blueGateways->getData('gateway_logo_url'),
                'use_own_logo'        => $blueGateways->getData('use_own_logo'),
                'gateway_logo_path'   => $blueGateways->getData('gateway_logo_path'),
                'status_date'         => $blueGateways->getData('status_date'),
            ];
        }

        return $existingGateways;
    }

    /**
     * @return bool
     */
    public function showGatewayLogo()
    {
        return $this->scopeConfig->getValue("payment/bluepayment/show_gateway_logo") == 1;
    }
}
