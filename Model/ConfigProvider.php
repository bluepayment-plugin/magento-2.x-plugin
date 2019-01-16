<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection as GatewaysCollection;
use Magento\Checkout\Model\ConfigProviderInterface;

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

    /**
     * @var \BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection
     */
    protected $gatewaysCollection;

    /**
     * @var array
     */
    protected $_activeGateways = [];

    /**
     * @var array
     */
    protected $_activeGatewaysResponse;

    /** @var Form  */
    protected $block;

    protected $priceCurrency;

    protected $logger;

    protected $scopeConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param \BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection $gatewaysCollection
     * @param Form $block
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        GatewaysCollection $gatewaysCollection,
        Form $block,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->gatewaysCollection = $gatewaysCollection;
        $this->block = $block;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
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

        if (!isset($this->_activeGateways[$currency])) {
            $resultSeparated         = [];
            $result                  = [];

            $gatewaysCollection = $this->gatewaysCollection
                ->addFilter('gateway_currency', $currency)
                ->load();

            /** @var Gateways $gateway */
            foreach ($gatewaysCollection as $gateway) {
                if ($gateway->isActive()) {
                    if ($gateway->getIsSeparatedMethod()) {
                        $resultSeparated[] = $this->prepareGatewayStructure($gateway);
                    } else {
                        $result[] = $this->prepareGatewayStructure($gateway);
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
                'GPayMerchantId' => $this->scopeConfig->getValue("payment/bluepayment/gpay_merchant_id"),
                'bluePaymentAcceptorId' => $this->scopeConfig->getValue("payment/bluepayment/bm_acceptor_id"),
                'bluePaymentTestMode' => $this->scopeConfig->getValue("payment/bluepayment/test_mode"),
            ];

            $this->_activeGateways[$currency] = $activeGateways;
        }

        return $this->_activeGateways[$currency];
    }

    /**
     * @param Gateways $gateway
     *
     * @return array
     */
    protected function prepareGatewayStructure($gateway)
    {
        $logoUrl = $gateway->getGatewayLogoUrl();
        if ((int)$gateway->getUseOwnLogo()) {
            $logoUrl = $gateway->getGatewayLogoPath();
        }

        $name = $gateway->getGatewayName();
        $isIframe = false;
        $isBlik = false;
        $isGPay = false;

        if ($this->scopeConfig->getValue('payment/bluepayment/iframe_payment')
            && $gateway->getGatewayId() == self::IFRAME_GATEWAY_ID) {
            $name = $this->block->getTitleAutomatic();
            $isIframe = true;
        } elseif ($gateway->getGatewayId() == self::BLIK_GATEWAY_ID) {
            $name = $this->block->getTitleBlik();
            $isBlik = true;
        } elseif ($gateway->getGatewayId() == self::GPAY_GATEWAY_ID) {
            $isGPay = true;
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
        ];
    }

    public function getCurrentCurrencyCode()
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }
}
