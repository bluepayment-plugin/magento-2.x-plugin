<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection as GatewaysCollection;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class ConfigProvider
 *
 * @package BlueMedia\BluePayment\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    const IFRAME_GATEWAY_ID = 1500;
    const BLIK_GATEWAY_ID = 509;
    const GPAY_GATEWAY_ID = 1512;
    const AUTOPAY_GATEWAY_ID = 1503;

    /** @var GatewaysCollection */
    private $gatewaysCollection;

    /** @var array */
    private $activeGateways = [];

    /** @var Form  */
    private $block;

    /** @var PriceCurrencyInterface */
    private $priceCurrency;

    /** @var Logger */
    private $logger;

    /** @var ScopeConfigInterface  */
    private $scopeConfig;

    /** @var Session */
    private $session;

    /** @var CardCollectionFactory */
    private $cardCollectionFactory;

    /**
     * ConfigProvider constructor.
     *
     * @param GatewaysCollection $gatewaysCollection
     * @param Form $block
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $session,
     * @param CardCollectionFactory $cardCollectionFactory
     */
    public function __construct(
        GatewaysCollection $gatewaysCollection,
        Form $block,
        PriceCurrencyInterface $priceCurrency,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Session $session,
        CardCollectionFactory $cardCollectionFactory
    ) {
        $this->gatewaysCollection = $gatewaysCollection;
        $this->block = $block;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->cardCollectionFactory = $cardCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => $this->getActiveGateways(),
        ];
    }

    /**
     * @return array
     */
    public function getActiveGateways()
    {
        $currency = $this->getCurrentCurrencyCode();

        if (!isset($this->activeGateways[$currency])) {
            $resultSeparated         = [];
            $result                  = [];

            $gatewaysCollection = $this->gatewaysCollection
                ->addFilter('gateway_currency', $currency)
                ->load();

            /** @var Gateways $gateway */
            foreach ($gatewaysCollection as $gateway) {
                if ($gateway->isActive()) {
                    // AutoPay only for logger users
                    if ($gateway->getGatewayId() != self::AUTOPAY_GATEWAY_ID || $this->session->isLoggedIn()) {
                        if ($gateway->getIsSeparatedMethod()) {
                            $resultSeparated[] = $this->prepareGatewayStructure($gateway);
                        } else {
                            $result[] = $this->prepareGatewayStructure($gateway);
                        }
                    }
                }
            }

            usort($result, function ($a, $b) {
                return (int)$a['sort_order'] > (int)$b['sort_order'];
            });

            $activeGateways = [
                'bluePaymentOptions' => $result,
                'bluePaymentSeparated' => $resultSeparated,
                'bluePaymentLogo' => $this->block->getLogoSrc(),
                'GPayMerchantId' => $this->scopeConfig->getValue("payment/bluepayment/gpay/merchant_id"),
                'bluePaymentAcceptorId' => $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/acceptor_id"),
                'bluePaymentTestMode' => $this->scopeConfig->getValue("payment/bluepayment/test_mode"),
                'bluePaymentCards' => $this->prepareCards(),
                'bluePaymentAutopayAgreement' => $this->scopeConfig->getValue("payment/bluepayment/autopay_agreement")
            ];

            $this->activeGateways[$currency] = $activeGateways;
        }

        return $this->activeGateways[$currency];
    }

    /**
     * @param Gateways $gateway
     *
     * @return array
     */
    private function prepareGatewayStructure($gateway)
    {
        $logoUrl = $gateway->getGatewayLogoUrl();
        if ((int)$gateway->getUseOwnLogo()) {
            $logoUrl = $gateway->getGatewayLogoPath();
        }

        $name = $gateway->getGatewayName();
        $isIframe = false;
        $isBlik = false;
        $isGPay = false;
        $isAutopay = false;

        switch ($gateway->getGatewayId()) {
            case self::IFRAME_GATEWAY_ID:
                if ($this->scopeConfig->getValue('payment/bluepayment/iframe_payment')) {
                    $isIframe = true;
                }
                break;
            case self::AUTOPAY_GATEWAY_ID:
                $isAutopay = true;
                if ($this->scopeConfig->getValue('payment/bluepayment/iframe_payment')) {
                    $isIframe = true;
                }
                break;
            case self::BLIK_GATEWAY_ID:
                $isBlik = true;
                break;
            case self::GPAY_GATEWAY_ID:
                if ($this->scopeConfig->getValue('payment/bluepayment/gpay/direct') == 1) {
                    $isGPay = true;
                }
                break;
        }

        return [
            'gateway_id'          => $gateway->getGatewayId(),
            'name'                => $name,
            'bank'                => $gateway->getBankName(),
            'description'         => $gateway->getGatewayDescription(),
            'sort_order'          => $gateway->getGatewaySortOrder(),
            'type'                => $gateway->getGatewayType(),
            'logo_url'            => $logoUrl,
            'is_separated_method' => $gateway->getIsSeparatedMethod(),
            'is_iframe'           => $isIframe,
            'is_blik'             => $isBlik,
            'is_gpay'             => $isGPay,
            'is_autopay'          => $isAutopay,
        ];
    }

    public function getCurrentCurrencyCode()
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }

    private function prepareCards()
    {
        $collection = $this->cardCollectionFactory->create();

        /** @var Card[] $cards */
        $cards = $collection
            ->addFieldToFilter('customer_id', $this->session->getCustomerId())
            ->load();

        $return = [];

        if ($cards !== null) {
            foreach ($cards as $card) {
                $return[] = [
                    'index' => $card->getCardIndex(),
                    'number' => $card->getNumber(),
                    'issuer' => $card->getIssuer(),
                    'logo' => $this->block->getViewFileUrl(
                        'BlueMedia_BluePayment::images/'.strtolower($card->getIssuer()) .'.png'
                    ),
                ];
            }
        }

        $return[] = [
            'index' => -1,
            'number' => 'Dodaj nową kartę',
            'issuer' => 'None',
            'logo' => 'https://platnosci.bm.pl/storage/app/media/grafika/1503.png',
        ];

        return $return;
    }
}
