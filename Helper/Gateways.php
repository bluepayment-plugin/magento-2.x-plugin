<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\Data\GatewayInterfaceFactory;
use BlueMedia\BluePayment\Api\GatewayRepositoryInterface;
use BlueMedia\BluePayment\Helper\Email as EmailHelper;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Gateway;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\Collection;
use Exception;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Gateways extends Data
{
    /** @var array Available currencies */
    public static $currencies = [
        'PLN',
        'EUR',
        'GBP',
        'USD',
        'CZK',
        'RON',
        'HUF',
        'BGN',
        'UAH',
        'SEK',
    ];

    /** @var GatewayInterfaceFactory */
    protected $gatewayFactory;

    /** @var GatewayRepositoryInterface */
    protected $gatewayRepository;

    /** @var Email */
    protected $emailHelper;

    /** @var Collection */
    protected $collection;

    /** @var Webapi */
    protected $webapi;

    /**
     * Gateways constructor.
     *
     * @param  Context  $context
     * @param  LayoutFactory  $layoutFactory
     * @param  Factory  $paymentMethodFactory
     * @param  Emulation  $appEmulation
     * @param  Config  $paymentConfig
     * @param  Initial  $initialConfig
     * @param  GatewayInterfaceFactory  $gatewayFactory
     * @param  GatewayRepositoryInterface  $gatewayRepository
     * @param  Client  $apiClient
     * @param  Logger  $logger
     * @param  EmailHelper  $emailHelper
     * @param  Collection  $collection
     * @param  StoreManagerInterface  $storeManager
     * @param  Webapi  $webapi
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        GatewayInterfaceFactory $gatewayFactory,
        GatewayRepositoryInterface $gatewayRepository,
        Client $apiClient,
        Logger $logger,
        EmailHelper $emailHelper,
        Collection $collection,
        StoreManagerInterface $storeManager,
        Webapi $webapi
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

        $this->gatewayFactory = $gatewayFactory;
        $this->gatewayRepository = $gatewayRepository;
        $this->emailHelper = $emailHelper;
        $this->collection = $collection;
        $this->logger = $logger;
        $this->webapi = $webapi;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncGateways(): array
    {
        $result = [];

        foreach ($this->storeManager->getStores() as $store) {
            $existingGateways = $this->getSimpleGatewaysList();

            $gatewaySelection = $this->scopeConfig->getValue(
                'payment/bluepayment/gateway_selection',
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );

            if ($gatewaySelection) {
                foreach (self::$currencies as $currency) {
                    $serviceId = (int) $this->scopeConfig->getValue(
                        'payment/bluepayment/'.strtolower($currency).'/service_id',
                        ScopeInterface::SCOPE_STORE,
                        $store->getCode()
                    );
                    $hashKey = $this->scopeConfig->getValue(
                        'payment/bluepayment/'.strtolower($currency).'/shared_key',
                        ScopeInterface::SCOPE_STORE,
                        $store->getCode()
                    );

                    if ($serviceId) {
                        $tryCount = 0;
                        $loadResult = false;
                        while (!$loadResult) {
                            $loadResult = $this->webapi->gatewayList($serviceId, $hashKey, $currency);

                            if (isset($loadResult['result']) && $loadResult['result'] == 'OK') {
                                $result['success'] = $this->saveGateways(
                                    $serviceId,
                                    $store,
                                    $loadResult['gatewayList'],
                                    $existingGateways,
                                    $currency
                                );
                                break;
                            } elseif ($tryCount >= self::FAILED_CONNECTION_RETRY_COUNT) {
                                $result['error'] ='Exceeded the limit of attempts to sync gateways list for '.$serviceId.'!';
                                break;
                            }
                            $tryCount++;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getSimpleGatewaysList(): array
    {
        $bluegatewaysCollection = $this->collection;
        $bluegatewaysCollection->load();

        $existingGateways = [];
        $globalServiceIds = [];
        $config = $this->scopeConfig->getValue('payment/bluepayment');
        foreach (self::$currencies as $currency) {
            if (isset($config[strtolower($currency)]) && isset($config[strtolower($currency)]['service_id'])) {
                $globalServiceIds[$currency] = $config[strtolower($currency)]['service_id'];
            }
        }

        $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();

        foreach ($bluegatewaysCollection as $gateway) {
            /** @var Gateway $gateway */

            $storeId = $gateway->getData('store_id') ?? $defaultStoreId;
            $serviceId = $gateway->getData('gateway_service_id');
            $currency = $gateway->getData('gateway_currency');
            $gatewayId = $gateway->getData('gateway_id');

            if (isset($existingGateways[$storeId][$currency][$gatewayId])) {
                // Remove duplicates
                $this->gatewayRepository->delete($gateway);

                continue;
            }

            if ($serviceId == 0 && isset($globalServiceIds[$currency])) {
                $serviceId = $globalServiceIds[$currency];
            }

            $existingGateways[$storeId][$currency][$gatewayId] = [
                'entity_id' => $gateway->getId(),
                'store_id' => $storeId,
                'gateway_id' => $gatewayId,
                'gateway_service_id' => $serviceId,
                'gateway_currency' => $currency,
                'gateway_status' => $gateway->getData('gateway_status'),
                'bank_name' => $gateway->getData('bank_name'),
                'gateway_name' => $gateway->getData('gateway_name'),
                'gateway_description' => $gateway->getData('gateway_description'),
                'gateway_sort_order' => $gateway->getData('gateway_sort_order'),
                'gateway_type' => $gateway->getData('gateway_type'),
                'gateway_logo_url' => $gateway->getData('gateway_logo_url'),
                'use_own_logo' => $gateway->getData('use_own_logo'),
                'gateway_logo_path' => $gateway->getData('gateway_logo_path'),
                'status_date' => $gateway->getData('status_date'),
            ];
        }

        return $existingGateways;
    }

    /**
     * @param  integer  $serviceId
     * @param  StoreInterface  $store
     * @param  array  $gatewayList
     * @param  array  $existingGateways
     * @param  string  $currency
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function saveGateways(
        int $serviceId,
        StoreInterface $store,
        array $gatewayList,
        array $existingGateways,
        string $currency = 'PLN'
    ): bool {
        $currentlyActiveGatewayIDs = [];
        $storeId = $store->getId();

        foreach ($gatewayList as $gateway) {
            $gateway = (array) $gateway;

            if (isset($gateway['gatewayID'])
                && isset($gateway['gatewayName'])
                && isset($gateway['gatewayType'])
                && isset($gateway['bankName'])
                && isset($gateway['stateDate'])
            ) {
                $gatewayId = $gateway['gatewayID'];
                $currentlyActiveGatewayIDs[] = $gatewayId;

                if (isset($existingGateways[$storeId][$currency][$gatewayId])) {
                    $gatewayModel = $this->gatewayRepository->getById(
                        $existingGateways[$storeId][$currency][$gatewayId]['entity_id']
                    );
                } else {
                    $gatewayModel = $this->gatewayFactory->create();
                    $gatewayModel->setForceDisable(false);
                    $gatewayModel->setName($gateway['gatewayName']);
                }

                if (// Always separated
                    in_array($gateway['gatewayID'], ConfigProvider::ALWAYS_SEPARATED)
                    || ($gateway['gatewayID'] == ConfigProvider::BLIK_GATEWAY_ID && $this->blikZero($store))) {
                    $gatewayModel->setIsSeparatedMethod(true);
                }

                $gatewayModel->setStoreId($storeId);
                $gatewayModel->setServiceId($serviceId);
                $gatewayModel->setCurrency($currency);
                $gatewayModel->setGatewayId($gateway['gatewayID']);
                $gatewayModel->setStatus($gateway['state'] == 'OK');
                $gatewayModel->setBankName($gateway['bankName']);
                $gatewayModel->setType($gateway['gatewayType']);
                $gatewayModel->setLogoUrl($gateway['iconURL'] ?? null);
                $gatewayModel->setData('status_date', $gateway['stateDate']);

                $save = false;
                foreach ($gateway['currencyList'] as $currencyInfo) {
                    $currencyInfo = (array) $currencyInfo;

                    if ($currencyInfo['currency'] == $currency) {
                        // For now - we support only one currency per service
                        $save = true;

                        $gatewayModel->setMinAmount($currencyInfo['minAmount'] ?? null);
                        $gatewayModel->setMaxAmount($currencyInfo['maxAmount'] ?? null);
                    }
                }

                if ($save) {
                    try {
                        $this->gatewayRepository->save($gatewayModel);
                    } catch (Exception $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }
        }

        $disabledGateways = [];
        if (isset($existingGateways[$storeId]) && isset($existingGateways[$storeId][$currency])) {
            foreach ($existingGateways[$storeId][$currency] as $existingGatewayId => $existingGatewayData) {
                if (!in_array(
                    $existingGatewayId,
                    $currentlyActiveGatewayIDs
                ) && $existingGatewayData['gateway_status'] != 0) {
                    $gatewayModel = $this->gatewayRepository->getById($existingGatewayData['entity_id']);
                    $gatewayModel->setStatus(false);

                    try {
                        $this->gatewayRepository->save($gatewayModel);

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
     * Check if blik zero is enabled for current store.
     *
     * @param  StoreInterface $store
     * @return bool
     */
    protected function blikZero(StoreInterface $store): bool
    {
        return (boolean) $this->scopeConfig->getValue(
            'payment/bluepayment/blik_zero',
            ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }
}
