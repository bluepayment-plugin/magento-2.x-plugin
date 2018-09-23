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
            $resultCard         = [];
            $result             = [];
            $automaticAvailable = false;
            $blikAvailable      = false;

            $gatewaysCollection = $this->gatewaysCollection
                ->addFilter('gateway_currency', $currency)
                ->load();

            /** @var Gateways $gateway */
            foreach ($gatewaysCollection as $gateway) {
                if ($gateway->isActive()) {
                    // Płatność kartą w iframe
                    if ($this->scopeConfig->getValue('payment/bluepayment/iframe_payment')
                        && $gateway->getGatewayId() == self::IFRAME_GATEWAY_ID) {
                        $automaticAvailable = true;
                        continue;
                    }

                    // Blik 0
                    if ($gateway->getGatewayId() == self::BLIK_GATEWAY_ID) {
                        $blikAvailable = true;
                        continue;
                    }

                    if ($gateway->isCreditCard()) {
                        $resultCard[] = $this->prepareGatewayStructure($gateway);
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
                'bluePaymentCard' => $resultCard,
                'bluePaymentLogo' => $this->block->getLogoSrc(),
            ];

            $activeGateways['bluePaymentAutomatic'] = $automaticAvailable ? $this->prepareGatewayAutomatic() : [];
            $activeGateways['bluePaymentBlik'] = $blikAvailable ? $this->prepareGatewayBlik() : [];

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

        return [
            'gateway_id'          => $gateway->getGatewayId(),
            'name'                => $gateway->getGatewayName(),
            'bank'                => $gateway->getBankName(),
            'description'         => $gateway->getGatewayDescription(),
            'sort_order'          => $gateway->getGatewaySortOrder(),
            'type'                => $gateway->getGatewayType(),
            'logo_url'            => $logoUrl,
            'is_separated_method' => $gateway->getIsSeparatedMethod(),
        ];
    }

    /**
     * @return array
     */
    private function prepareGatewayAutomatic()
    {
        return [[
            'gateway_id'          => self::IFRAME_GATEWAY_ID,
            'name'                => $this->block->getTitleAutomatic(),
        ]];
    }

    /**
     * @return array
     */
    private function prepareGatewayBlik()
    {
        return [[
            'gateway_id'          => self::BLIK_GATEWAY_ID,
            'name'                => $this->block->getTitleBlik(),
        ]];
    }

    public function getCurrentCurrencyCode()
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }
}
