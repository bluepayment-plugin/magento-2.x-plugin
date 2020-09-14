<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Helper\Email as EmailHelper;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\GatewaysFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection;
use Exception;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use SimpleXMLElement;

class Gateways extends Data
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH = 32;
    const UPLOAD_PATH = '/BlueMedia/';

    /** @var array Available currencies */
    public static $currencies = [
        'PLN', 'EUR', 'GBP', 'USD', 'CZK', 'RON', 'HUF', 'BGN', 'UAH'
    ];

    /** @var GatewaysFactory */
    public $gatewaysFactory;

    /** @var \Zend\Log\Logger */
    public $logger;

    /** @var Email */
    public $emailHelper;

    /** @var Collection */
    public $collection;

    /**
     * Gateways constructor.
     *
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param GatewaysFactory $gatewaysFactory
     * @param Client $apiClient
     * @param Logger $logger
     * @param EmailHelper $emailHelper
     * @param Collection $collection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        GatewaysFactory $gatewaysFactory,
        Client $apiClient,
        Logger $logger,
        EmailHelper $emailHelper,
        Collection $collection,
        StoreManagerInterface $storeManager
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

        $this->gatewaysFactory = $gatewaysFactory;
        $this->emailHelper = $emailHelper;
        $this->collection = $collection;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function syncGateways()
    {
        $result = [];
        $existingGateways = $this->getSimpleGatewaysList();

        foreach ($this->storeManager->getWebsites() as $website) {
            $hashMethod = $this->scopeConfig->getValue(
                'payment/bluepayment/hash_algorithm',
                ScopeInterface::SCOPE_WEBSITE,
                $website->getCode()
            );
            $gatewayListAPIUrl = $this->getGatewayListUrl($website);

            foreach (self::$currencies as $currency) {
                $serviceId = $this->scopeConfig->getValue(
                    'payment/bluepayment/' . strtolower($currency) . '/service_id',
                    ScopeInterface::SCOPE_WEBSITE,
                    $website->getCode()
                );
                $hashKey = $this->scopeConfig->getValue(
                    'payment/bluepayment/' . strtolower($currency) . '/shared_key',
                    ScopeInterface::SCOPE_WEBSITE,
                    $website->getCode()
                );

                $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);

                if ($serviceId) {
                    $tryCount = 0;
                    $loadResult = false;
                    while (!$loadResult) {
                        $loadResult = $this->loadGatewaysFromAPI(
                            $hashMethod,
                            $serviceId,
                            $messageId,
                            $hashKey,
                            $gatewayListAPIUrl
                        );

                        if ($loadResult) {
                            $result['success'] = $this->saveGateways(
                                $serviceId,
                                (array)$loadResult,
                                $existingGateways,
                                $currency
                            );
                            break;
                        } else {
                            if ($tryCount >= self::FAILED_CONNECTION_RETRY_COUNT) {
                                $result['error'] = 'Exceeded the limit of attempts to sync gateways list for ' . $serviceId . '!';
                                break;
                            }
                        }
                        $tryCount++;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param WebsiteInterface $website
     * @return string
     */
    private function getGatewayListUrl(WebsiteInterface $website)
    {
        $testMode = $this->scopeConfig->getValue(
            'payment/bluepayment/test_mode',
            ScopeInterface::SCOPE_WEBSITE,
            $website->getCode()
        );

        if ($testMode) {
            return $this->scopeConfig->getValue(
                'payment/bluepayment/test_address_gateways_url',
                ScopeInterface::SCOPE_WEBSITE,
                $website->getCode()
            );
        }

        return $this->scopeConfig->getValue(
            'payment/bluepayment/prod_address_gateways_url',
            ScopeInterface::SCOPE_WEBSITE,
            $website->getCode()
        );
    }

    /**
     * @param string $hashMethod
     * @param string $serviceId
     * @param string $messageId
     * @param string $hashKey
     * @param string $gatewayListAPIUrl
     *
     * @return bool|SimpleXMLElement
     */
    private function loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl)
    {
        $hash = hash($hashMethod, $serviceId . '|' . $messageId . '|' . $hashKey);
        $data = [
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'Hash' => $hash,
        ];

        try {
            return $this->apiClient->call($gatewayListAPIUrl, $data);
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());

            return false;
        }
    }

    /**
     * @param integer $serviceId
     * @param array $gatewayList
     * @param array $existingGateways
     * @param string $currency
     *
     * @return bool
     */
    public function saveGateways($serviceId, $gatewayList, $existingGateways, $currency = 'PLN')
    {
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

                    if (isset($existingGateways[$serviceId][$currency][$gateway['gatewayID']])) {
                        $gatewayModel = $this->gatewaysFactory->create();
                        $gatewayModel->load($existingGateways[$serviceId][$currency][$gateway['gatewayID']]['entity_id']);
                    } else {
                        $gatewayModel = $this->gatewaysFactory->create();
                        $gatewayModel->setData('force_disable', 0);
                        $gatewayModel->setData('gateway_name', $gateway['gatewayName']);
                        $gatewayModel->setData('gateway_service_id', $serviceId);
                    }

                    if (in_array($gateway['gatewayID'], [
                        ConfigProvider::AUTOPAY_GATEWAY_ID,
                        ConfigProvider::GPAY_GATEWAY_ID,
                        ConfigProvider::APPLE_PAY_GATEWAY_ID
                    ])) {
                        $gatewayModel->setData('is_separated_method', '1');
                    }

                    $gatewayModel->setData('gateway_currency', $currency);
                    $gatewayModel->setData('gateway_id', $gateway['gatewayID']);
                    $gatewayModel->setData('gateway_status', 1);
                    $gatewayModel->setData('bank_name', $gateway['bankName']);
                    $gatewayModel->setData('gateway_type', $gateway['gatewayType']);
                    $gatewayModel->setData('gateway_logo_url', isset($gateway['iconURL']) ? $gateway['iconURL'] : null);
                    $gatewayModel->setData('status_date', $gateway['statusDate']);
                    try {
                        $gatewayModel->save();
                    } catch (Exception $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }

            $disabledGateways = [];
            foreach ($existingGateways[$serviceId][$currency] as $existingGatewayId => $existingGatewayData) {
                if (!in_array($existingGatewayId, $currentlyActiveGatewayIDs)
                    && $existingGatewayData['gateway_status'] != 0
                ) {
                    $gatewayModel = $this->gatewaysFactory->create()->load($existingGatewayData['entity_id']);
                    $gatewayModel->setData('gateway_status', 0);
                    try {
                        $gatewayModel->save();
                        $disabledGateways[] = [
                            'gateway_name' => $existingGatewayData['gateway_name'],
                            'gateway_id' => $existingGatewayId,
                        ];
                    } catch (Exception $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }

            if (!empty($disabledGateways)) {
                $this->emailHelper->sendGatewayDeactivationEmail($disabledGateways);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSimpleGatewaysList()
    {
        $bluegatewaysCollection = $this->collection;
        $bluegatewaysCollection->load();

        $existingGateways = [];

        foreach (self::$currencies as $currency) {
            $existingGateways[$currency] = [];
        }

        foreach ($bluegatewaysCollection as $blueGateways) {
            $existingGateways[$blueGateways->getData('gateway_service_id')][$blueGateways->getData('gateway_currency')][$blueGateways->getData('gateway_id')] = [
                'entity_id' => $blueGateways->getId(),
                'gateway_service_id' => $blueGateways->getData('gateway_service_id'),
                'gateway_currency' => $blueGateways->getData('gateway_currency'),
                'gateway_status' => $blueGateways->getData('gateway_status'),
                'bank_name' => $blueGateways->getData('bank_name'),
                'gateway_name' => $blueGateways->getData('gateway_name'),
                'gateway_description' => $blueGateways->getData('gateway_description'),
                'gateway_sort_order' => $blueGateways->getData('gateway_sort_order'),
                'gateway_type' => $blueGateways->getData('gateway_type'),
                'gateway_logo_url' => $blueGateways->getData('gateway_logo_url'),
                'use_own_logo' => $blueGateways->getData('use_own_logo'),
                'gateway_logo_path' => $blueGateways->getData('gateway_logo_path'),
                'status_date' => $blueGateways->getData('status_date'),
            ];
        }

        return $existingGateways;
    }
}
