<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\GatewayInterface;
use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\Collection as GatewayCollection;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayCollectionFactory;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const TYPE_PBL = 'PBL';
    public const TYPE_FR = 'FR';

    public const BLIK_GATEWAY_ID = 509;
    public const BLIK_BNPL_GATEWAY_ID = 523;
    public const HUB_GATEWAY_ID = 702;
    public const PAYPO_GATEWAY_ID = 705;
    public const SPINGO_GATEWAY_ID = 706;
    public const CARD_GATEWAY_ID = 1500;
    public const ONECLICK_GATEWAY_ID = 1503;
    public const ALIOR_INSTALLMENTS_GATEWAY_ID = 1506;
    public const GPAY_GATEWAY_ID = 1512;
    public const APPLE_PAY_GATEWAY_ID = 1513;
    public const VISA_MOBILE_GATEWAY_ID = 1523;

    public const ALWAYS_SEPARATED = [
        self::BLIK_GATEWAY_ID,
        self::BLIK_BNPL_GATEWAY_ID,
        self::HUB_GATEWAY_ID,
        self::PAYPO_GATEWAY_ID,
        self::SPINGO_GATEWAY_ID,
        self::CARD_GATEWAY_ID,
        self::ONECLICK_GATEWAY_ID,
        self::ALIOR_INSTALLMENTS_GATEWAY_ID,
        self::GPAY_GATEWAY_ID,
        self::APPLE_PAY_GATEWAY_ID,
        self::VISA_MOBILE_GATEWAY_ID,
    ];

    /** @var GatewayCollectionFactory */
    private $gatewayCollectionFactory;

    /** @var array */
    private $activeGateways = [];

    /** @var Form */
    private $block;

    /** @var PriceCurrencyInterface */
    private $priceCurrency;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var CustomerSession */
    private $customerSession;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var CardCollectionFactory */
    private $cardCollectionFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Config */
    private $orderConfig;

    /** @var array */
    private $defaultSortOrder = [
        '', // Avoid pushing first element to the end
        self::BLIK_GATEWAY_ID, // BLIK
        self::ONECLICK_GATEWAY_ID, // Kartowa płatność automatyczna
        self::CARD_GATEWAY_ID, // Płatność kartą
        self::VISA_MOBILE_GATEWAY_ID, // Visa Mobile
        self::GPAY_GATEWAY_ID, // Google Pay
        self::APPLE_PAY_GATEWAY_ID, // Apple Pay

        self::HUB_GATEWAY_ID, // Hub
        self::ALIOR_INSTALLMENTS_GATEWAY_ID, // Alior Raty
        self::PAYPO_GATEWAY_ID, // PayPo
        self::SPINGO_GATEWAY_ID, // Spingo

        self::VISA_MOBILE_GATEWAY_ID, // Visa Checkout

        106, // Tylko na teście
        68, // Płać z ING
        1808, // Płatność z ING
        3, // mTransfer
        1800, // Płatność z mBank
        1063, // Płacę z IPKO
        1803, // Płatność z PKOBP
        27, // Santander online
        1806, // Płatność z Santander
        52, // Pekao24 PBL
        1805, // Płatność z PekaoSA
        85, // Millennium Bank PBL
        1807, // Płatność z Millenium
        95, // Płacę z Alior Bankiem
        1802, // Płatność z Alior
        59, // CA przelew online
        1809, // Płatność z Credit Agricole
        79, // Eurobank - płatność online
        1064, // Płacę z Inteligo
        1810, // Płatność z Inteligo
        1035, // BNP Paribas - płacę z Pl@net
        1804, // Płatność z BNP Paribas
        513, // Getin Bank
        1801, // Płatność z Getin
        1010, // T-Mobile Usługi Bankowe
        90, // Płacę z Citi Handlowy
        76, // BNP Paribas-Płacę z żółty online
        108, // e-transfer Pocztowy24
        517, // NestPrzelew
        131, //
        86, // Płać z BOŚ Bank
        98, // PBS Bank - przelew 24
        117, // Toyota Bank Pay Way
        1050, // Płacę z neoBANK
        514, // Noble Bank
        109, // EnveloBank
        1507, // Bank Spółdzielczy w Sztumie PBL
        1510, // Bank Spółdzielczy Lututów PBL
        1515, // Bank Spółdzielczy w Toruniu PBL
        1517, // Bank Spółdzielczy w Rumi PBL
        21, // Przelew Volkswagen Bank
        35, // Spółdzielcza Grupa Bankowa
        9, // Mam konto w innym banku
    ];

    /**
     * ConfigProvider constructor.
     *
     * @param GatewayCollectionFactory $gatewayCollectionFactory $gatewayCollectionFactory
     * @param Form $block
     * @param PriceCurrencyInterface $priceCurrency
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param CardCollectionFactory $cardCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $orderConfig
     */
    public function __construct(
        GatewayCollectionFactory $gatewayCollectionFactory,
        Form $block,
        PriceCurrencyInterface $priceCurrency,
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        CardCollectionFactory $cardCollectionFactory,
        StoreManagerInterface $storeManager,
        Config $orderConfig
    ) {
        $this->gatewayCollectionFactory = $gatewayCollectionFactory;
        $this->block = $block;
        $this->priceCurrency = $priceCurrency;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->storeManager = $storeManager;
        $this->orderConfig = $orderConfig;
    }

    /**
     * Is BlueMedia payment method active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/active',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is BlueMedia payment method in test (ACC) mode.
     *
     * @param  int|null  $storeId
     * @return bool
     */
    public function isTestMode(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/test_mode',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns whether the continuation link should be disabled after transaction expiration.
     *
     * @return int
     */
    public function getTransactionLifetime(): int
    {
        return (int) $this->scopeConfig->getValue(
            'payment/bluepayment/transaction_life_hours',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns whether the continuation link should be disabled after transaction expiration.
     *
     * @return bool
     */
    public function disableContinuationLinkAfterExpiration(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/bluepayment/disable_link_after_expiration',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                Payment::METHOD_CODE => $this->getPaymentConfig(),
            ],
        ];
    }

    /**
     * Return payment config for Blue Payment.
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getPaymentConfig(): array
    {
        if (! $this->isGatewaySelectionEnabled()) {
            return [
                'test_mode' => $this->isTestMode(),
                'logo' => $this->block->getLogoSrc(),
                'iframe_enabled' => $this->iframePaymentEnabled(),
                'options' => false,
                'separated' => false,
            ];
        }

        $currency = $this->getCurrentCurrencyCode();

        if (!isset($this->activeGateways[$currency])) {
            $resultSeparated = [];
            $result = [];

            $amount = $this->getGrandTotalForQuote();

            $gateways = $this->getActiveGateways($currency, (float) $amount);

            /** @var Gateway $gateway */
            foreach ($gateways as $gateway) {
                if ($gateway->getGatewayId() != self::ONECLICK_GATEWAY_ID
                    || $this->customerSession->isLoggedIn() // AutoPay only for logger users
                ) {
                    if ($gateway->isSeparatedMethod()) {
                        $resultSeparated[] = $this->prepareGatewayStructure($gateway);
                    } else {
                        $result[] = $this->prepareGatewayStructure($gateway);
                    }
                }
            }

            $this->sortGateways($result);
            $this->sortGateways($resultSeparated);

            $this->activeGateways[$currency] = [
                'test_mode' => $this->isTestMode(),
                'logo' => $this->block->getLogoSrc(),
                'iframe_enabled' => $this->iframePaymentEnabled(),
                'blik_zero_enabled' => $this->blikZeroEnabled(),
                'options' => $result,
                'separated' => $resultSeparated,
                'cards' => $this->prepareCards(),
                'one_click_agreement' => $this->scopeConfig->getValue(
                    'payment/bluepayment/autopay_agreement',
                    ScopeInterface::SCOPE_STORE
                ),
                'collapsible' => $this->scopeConfig->isSetFlag(
                    'payment/bluepayment/collapsible',
                    ScopeInterface::SCOPE_STORE
                ),
            ];
        }

        return $this->activeGateways[$currency];
    }

    /**
     * Get only separated methods
     *
     * @return Gateway[]
     * @throws NoSuchEntityException
     */
    public function getSeparatedGateways(): array
    {
        /** @var Gateway[] $separated */
        $separated = $this
            ->getActiveGateways(
                $this->getCurrentCurrencyCode(),
                null,
                true
            )
            ->getItems();
        $this->sortGateways($separated);

        return $separated;
    }

    /**
     * Get active currency code.
     *
     * @return string
     */
    public function getCurrentCurrencyCode(): string
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }

    /**
     * Prepare gateway detail
     *
     * @param Gateway $gateway
     * @return array
     */
    private function prepareGatewayStructure(GatewayInterface $gateway): array
    {
        $logoUrl = $gateway->getLogoUrl();
        if ($gateway->shouldUseOwnLogo()) {
            $logoUrl = $gateway->getLogoPath();
        }

        return [
            'gateway_id' => $gateway->getGatewayId(),
            'name' => $gateway->getName(),
            'bank' => $gateway->getBankName(),
            'short_description' => $gateway->getShortDescription(),
            'description' => $gateway->getDescription(),
            'sort_order' => $gateway->getSortOrder(),
            'type' => $gateway->getType(),
            'logo_url' => $logoUrl,
            'is_separated_method' => $gateway->isSeparatedMethod(),
        ];
    }

    /**
     * Sort gateways by sort_order and gateway_id
     *
     * @param array|GatewayCollection $array
     *
     * @return array
     */
    public function sortGateways(array &$array): array
    {
        $sortOrder = $this->defaultSortOrder;

        usort($array, static function ($a, $b) use ($sortOrder) {
            /** @var array|Gateway $a */
            /** @var array|Gateway $b */

            $aPos = is_array($a) ? (int)$a['sort_order'] : (int)$a->getSortOrder();
            $bPos = is_array($b) ? (int)$b['sort_order'] : (int)$b->getSortOrder();

            $aGatewayId = is_array($a) ? (int)$a['gateway_id'] : (int)$a->getGatewayId();
            $bGatewayId = is_array($b) ? (int)$b['gateway_id'] : (int)$b->getGatewayId();

            if ($aPos === $bPos) {
                $aPos = array_search($aGatewayId, $sortOrder, true);
                $bPos = array_search($bGatewayId, $sortOrder, true);

                if ($aPos === false) {
                    // New gateway
                    return 1;
                }

                if ($bPos === false) {
                    // New gateway
                    return 0;
                }

            } elseif ($aPos === 0) {
                return 1;
            } elseif ($bPos === 0) {
                return 0;
            }

            return $aPos >= $bPos ? 1 : -1;
        });

        return $array;
    }

    /**
     * Get customer saved cards (for one click payment).
     *
     * @return array
     */
    private function prepareCards(): array
    {
        $collection = $this->cardCollectionFactory->create();

        /** @var Card[] $cards */
        $cards = $collection
            ->addFieldToFilter('customer_id', (string)$this->customerSession->getCustomerId())
            ->load();

        $return = [];

        if ($cards !== null) {
            foreach ($cards as $card) {
                $return[] = [
                    'index' => $card->getCardIndex(),
                    'number' => $card->getNumber(),
                    'issuer' => $card->getIssuer(),
                    'logo' => $this->block->getViewFileUrl(
                        'BlueMedia_BluePayment::images/' . strtolower($card->getIssuer()) . '.png'
                    ),
                ];
            }
        }

        $return[] = [
            'index' => -1,
            'number' => __('Add new card'),
            'issuer' => 'None',
            'logo' => 'https://platnosci.bm.pl/storage/app/media/grafika/1503.png',
        ];

        return $return;
    }

    /**
     * Get service ID.
     *
     * @param  string  $currency
     * @param  int|null  $storeId
     * @return string
     */
    public function getServiceID(string $currency = 'PLN', ?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get shared key.
     *
     * @param  string  $currency
     * @param  int|null  $storeId
     * @return string
     */
    public function getSharedKey(string $currency = 'PLN', ?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get hash separator.
     *
     * @param  int|null  $storeId
     * @return string
     */
    public function getHashSeparator(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            'payment/bluepayment/hash_separator',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get hash algorithm.
     *
     * @param  int|null  $storeId
     * @return string
     */
    public function getHashAlgorithm(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            'payment/bluepayment/hash_algorithm',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? 'sha256';
    }

    /**
     * Get active gateways
     *
     * @param string $currency
     * @param float|null $amount
     * @param bool $onlySeparated
     * @return GatewayCollection|Gateway[]
     * @throws NoSuchEntityException
     */
    public function getActiveGateways(
        string $currency,
        ?float $amount = null,
        bool $onlySeparated = false
    ): GatewayCollection {
        $storeId = $this->storeManager->getStore()->getId();

        $serviceId = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );

        $gateways = $this->gatewayCollectionFactory->create()
            ->addFieldToFilter(GatewayInterface::STORE_ID, ['eq' => $storeId])
            ->addFieldToFilter(GatewayInterface::SERVICE_ID, ['eq' => $serviceId])
            ->addFieldToFilter(GatewayInterface::CURRENCY, ['eq' => $currency])
            ->addFieldToFilter(GatewayInterface::STATUS, ['eq' => 1])
            ->addFieldToFilter(GatewayInterface::IS_FORCE_DISABLED, ['eq' => 0]);

        if ($amount !== null) {
            $gateways
                ->addFieldToFilter(GatewayInterface::MIN_AMOUNT, [
                    ['lteq' => $amount],
                    ['null' => true],
                ])
                ->addFieldToFilter(GatewayInterface::MAX_AMOUNT, [
                    ['gteq' => $amount],
                    ['null' => true],
                ]);
        }

        if ($onlySeparated) {
            $gateways->addFieldToFilter(GatewayInterface::IS_SEPARATED_METHOD, ['eq' => 1]);
        }

        return $gateways->load();
    }

    /**
     * Checks whether gateway selection on checkout is enabled.
     *
     * @return bool
     */
    public function isGatewaySelectionEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/gateway_selection',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks whether card iframe payment is enabled
     *
     * @return bool
     */
    protected function iframePaymentEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/iframe_payment',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks whether blik zero payment is enabled.
     *
     * @return bool
     */
    protected function blikZeroEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/blik_zero',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get order statuses that cannot be changed
     *
     * @param int|null $storeId
     * @return array
     */
    public function getUnchangableStatuses(?int $storeId = null): array
    {
        return explode(
            ',',
            $this->scopeConfig->getValue(
                'payment/bluepayment/unchangeable_statuses',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?: ''
        );
    }

    /**
     * Get status for order with waiting payment
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getStatusWaitingPayment(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/bluepayment/status_waiting_payment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? $this->orderConfig->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT);
    }

    /**
     * Get status for order with error payment
     *
     * @param int|null $store
     * @return string|null
     */
    public function getStatusErrorPayment(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/bluepayment/status_error_payment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? $this->orderConfig->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT);
    }

    /**
     * Get status for order with success payment
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getStatusSuccessPayment(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/bluepayment/status_accept_payment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? $this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING);
    }

    /**
     * Get status for order with partial refund
     *
     * @param  int|null  $storeId
     * @return string|null
     */
    public function getStatusPartialRefund(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/bluepayment/status_partial_refund',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? $this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING);
    }

    /**
     * Get status for order with full refund
     *
     * @param  int|null  $storeId
     * @return string|null
     */
    public function getStatusFullRefund(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/bluepayment/status_full_refund',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? $this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING);
    }

    /**
     * Checks whether consumer finance is enabled for specific position.
     *
     * @param string $position
     * @return bool
     */
    public function isConsumerFinanceEnabled(string $position): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/consumer_finance/' . $position,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get enabled gateways ids used for consumer finance.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConsumerFinanceGatewaysEnabledIds(): array
    {
        $storeId = $this->storeManager->getStore()->getId();
        $currency = $this->getCurrentCurrencyCode();

        $serviceId = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );

        $gateways = $this->gatewayCollectionFactory->create()
            ->addFieldToSelect(GatewayInterface::GATEWAY_ID)
            ->addFieldToFilter(GatewayInterface::STORE_ID, ['eq' => $storeId])
            ->addFieldToFilter(GatewayInterface::SERVICE_ID, ['eq' => $serviceId])
            ->addFieldToFilter(GatewayInterface::CURRENCY, ['eq' => $currency])
            ->addFieldToFilter(GatewayInterface::STATUS, ['eq' => 1])
            ->addFieldToFilter(GatewayInterface::IS_FORCE_DISABLED, ['eq' => 0])
            ->addFieldToFilter(GatewayInterface::GATEWAY_ID, ['in' => [
                self::HUB_GATEWAY_ID,
                self::ALIOR_INSTALLMENTS_GATEWAY_ID,
            ]]);

        return $gateways->getColumnValues(GatewayInterface::GATEWAY_ID);
    }

    public function isWithPhoneEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/bluepayment/with_phone',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns current configuration for alior installments.
     *
     * @return string 'one' or 'zero'
     */
    public function getAliorInstallments(): string
    {
        $value = (string) $this->scopeConfig->getValue(
            'payment/bluepayment/alior_installments',
            ScopeInterface::SCOPE_STORE
        );

        if (!in_array($value, ['one', 'zero'])) {
            return 'one';
        }

        return $value;
    }

    /**
     * @param  int|null  $storeId
     * @return string
     */
    public function getRefundStatusUrl(?int $storeId = null): string
    {
        if ($this->isTestMode($storeId)) {
            return $this->scopeConfig->getValue(
                'payment/bluepayment/refund_status_url_test',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->scopeConfig->getValue(
            'payment/bluepayment/refund_status_url_prod',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isAsyncProcess(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/bluepayment/async_process',
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function getGrandTotalForQuote(): float
    {
        return (float) $this->checkoutSession->getQuote()->getGrandTotal();
    }
}
