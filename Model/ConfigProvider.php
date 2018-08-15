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
    protected $_activeGateways;

    /**
     * @var array
     */
    protected $_activeGatewaysResponse;

    protected $block;

    /**
     * ConfigProvider constructor.
     *
     * @param \BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection $gatewaysCollection
     * @param Form $block
     */
    public function __construct(
        GatewaysCollection $gatewaysCollection,
        Form $block
    ) {
        $this->gatewaysCollection = $gatewaysCollection;
        $this->block = $block;
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
        if (!$this->_activeGateways) {
            $resultCard         = [];
            $result             = [];
            $gatewaysCollection = $this->gatewaysCollection->load();

            /** @var Gateways $gateway */
            foreach ($gatewaysCollection as $gateway) {
                if ($gateway->isActive()) {
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

            $this->_activeGateways = [
                'bluePaymentOptions' => $result,
                'bluePaymentCard' => $resultCard,
                'bluePaymentLogo' => $this->block->getLogoSrc(),
                'bluePaymentAutomatic' => $this->prepareGatewayAutomatic(),
                'bluePaymentBlik' => $this->prepareGatewayBlik(),
            ];
        }

        return $this->_activeGateways;
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
        return [
            [
                'gateway_id'          => self::IFRAME_GATEWAY_ID,
                'name'                => $this->block->getTitleAutomatic(),
            ]
        ];
    }

    /**
     * @return array
     */
    private function prepareGatewayBlik()
    {
        return [
            [
                'gateway_id'          => self::BLIK_GATEWAY_ID,
                'name'                => $this->block->getTitleBlik(),
            ]
        ];
    }
}
