<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\Collection as GatewayCollection;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const IFRAME_GATEWAY_ID = 1500;
    const BLIK_GATEWAY_ID = 509;
    const GPAY_GATEWAY_ID = 1512;
    const APPLE_PAY_GATEWAY_ID = 1513;
    const AUTOPAY_GATEWAY_ID = 1503;
    const CREDIT_GATEWAY_ID = 700;

    /** @var GatewayCollection */
    private $gatewayCollection;

    /** @var array */
    private $activeGateways = [];

    /** @var Form */
    private $block;

    /** @var PriceCurrencyInterface */
    private $priceCurrency;

    /** @var Logger */
    private $logger;

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

    /** @var array */
    private $defaultSortOrder = [
        '', // Avoid pushing first element to the end
        509, // BLIK
        1503, // Kartowa płatność automatyczna
        1500, // Płatność kartą
        1512, // Google Pay
        1513, // Apple Pay
        1511, // Visa Checkout
        700, // Smartney
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
        1506, // Alior Raty
    ];

    /**
     * ConfigProvider constructor.
     *
     * @param GatewayCollection $gatewayCollection
     * @param Form $block
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param CardCollectionFactory $cardCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GatewayCollection $gatewayCollection,
        Form $block,
        PriceCurrencyInterface $priceCurrency,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        CardCollectionFactory $cardCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->gatewayCollection = $gatewayCollection;
        $this->block = $block;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => $this->getPaymentConfig(),
        ];
    }

    /**
     * @return array
     */
    public function getPaymentConfig()
    {
        if (! $this->isGatewaySelectionEnabled()) {
            return [
                'bluePaymentOptions' => false,
                'bluePaymentSeparated' => false,
                'bluePaymentLogo' => $this->block->getLogoSrc(),
            ];
        }

        $currency = $this->getCurrentCurrencyCode();

        if (!isset($this->activeGateways[$currency])) {
            $resultSeparated = [];
            $result = [];

            $amount = $this->checkoutSession->getQuote()->getGrandTotal();

            $gateways = $this->getActiveGateways($amount, $currency);

            /** @var Gateway $gateway */
            foreach ($gateways as $gateway) {
                if (
                    ($gateway->getGatewayId() != self::AUTOPAY_GATEWAY_ID || $this->customerSession->isLoggedIn()) // AutoPay only for logger users
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
                'bluePaymentOptions' => $result,
                'bluePaymentSeparated' => $resultSeparated,
                'bluePaymentLogo' => $this->block->getLogoSrc(),
                'bluePaymentTestMode' => $this->scopeConfig->getValue(
                    'payment/bluepayment/test_mode',
                    ScopeInterface::SCOPE_STORE
                ),
                'bluePaymentCards' => $this->prepareCards(),
                'bluePaymentAutopayAgreement' => $this->scopeConfig->getValue(
                    'payment/bluepayment/autopay_agreement',
                    ScopeInterface::SCOPE_STORE
                ),
                'bluePaymentCollapsible' => $this->scopeConfig->getValue(
                    'payment/bluepayment/collapsible',
                    ScopeInterface::SCOPE_STORE
                )
            ];
        }

        return $this->activeGateways[$currency];
    }

    public function isGatewaySelectionEnabled()
    {
        return (bool) $this->scopeConfig->getValue(
            'payment/bluepayment/gateway_selection',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }

    /**
     * @param Gateway $gateway
     *
     * @return array
     */
    private function prepareGatewayStructure($gateway)
    {
        $logoUrl = $gateway->getLogoUrl();
        if ($gateway->getUseOwnLogo()) {
            $logoUrl = $gateway->getLogoPath();
        }

        $name = $gateway->getName();
        $isIframe = false;
        $isBlik = false;
        $isGPay = false;
        $isAutopay = false;
        $isApplePay = false;

        switch ($gateway->getGatewayId()) {
            case self::IFRAME_GATEWAY_ID:
                if ($this->scopeConfig->getValue(
                    'payment/bluepayment/iframe_payment',
                    ScopeInterface::SCOPE_STORE
                )) {
                    $isIframe = true;
                }
                break;
            case self::AUTOPAY_GATEWAY_ID:
                $isAutopay = true;
                if ($this->scopeConfig->getValue(
                    'payment/bluepayment/iframe_payment',
                    ScopeInterface::SCOPE_STORE
                )) {
                    $isIframe = true;
                }
                break;
            case self::BLIK_GATEWAY_ID:
                $isBlik = true;
                break;
            case self::GPAY_GATEWAY_ID:
                $isGPay = true;
                break;
            case self::APPLE_PAY_GATEWAY_ID:
                $isApplePay = true;
                break;
        }

        return [
            'gateway_id' => $gateway->getGatewayId(),
            'name' => $name,
            'bank' => $gateway->getBankName(),
            'description' => $gateway->getDescription(),
            'sort_order' => $gateway->getSortOrder(),
            'type' => $gateway->getType(),
            'logo_url' => $logoUrl,
            'is_separated_method' => $gateway->isSeparatedMethod(),
            'is_iframe' => $isIframe,
            'is_blik' => $isBlik,
            'is_gpay' => $isGPay,
            'is_autopay' => $isAutopay,
            'is_apple_pay' => $isApplePay
        ];
    }

    /**
     * @param array $array
     * @return array
     */
    public function sortGateways(&$array)
    {
        $sortOrder = $this->defaultSortOrder;

        usort($array, function ($a, $b) use ($sortOrder) {
            $aPos = (int)$a['sort_order'];
            $bPos = (int)$b['sort_order'];

            if ($aPos == $bPos) {
                $aPos = array_search($a["gateway_id"], $sortOrder);
                $bPos = array_search($b["gateway_id"], $sortOrder);

                if ($aPos === false) {
                    // New gateway
                    return true;
                }

                if ($bPos === false) {
                    // New gateway
                    return false;
                }

            } elseif ($aPos == 0) {
                return true;
            } elseif ($bPos == 0) {
                return false;
            }

            return $aPos >= $bPos;
        });

        return $array;
    }

    /**
     * @return array
     */
    private function prepareCards()
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
     * @param string $currency
     * @return GatewayCollection
     */
    public function getActiveGateways($amount, $currency)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $serviceId = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );

        $gateways = $this->gatewayCollection
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('gateway_service_id', ['eq' => $serviceId])
            ->addFieldToFilter('gateway_currency', ['eq' => $currency])
            ->addFieldToFilter('gateway_status', ['eq' => 1])
            ->addFieldToFilter('force_disable', ['eq' => 0]);

        if ($amount < 100 || $amount > 2500) {
            // Credit for 100-2500zł
            $gateways->addFieldToFilter('gateway_id', ['neq' => self::CREDIT_GATEWAY_ID]);
        }

        return $gateways->load();
    }
}
