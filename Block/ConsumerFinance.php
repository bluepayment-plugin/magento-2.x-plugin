<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ConsumerFinance extends Template
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var array */
    private $enabledGateways = null;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    public function isEnabled(): bool
    {
        if ($this->getData('position')) {
            return $this->configProvider->isActive()
                && $this->configProvider->isConsumerFinanceEnabled($this->getData('position'))
                && count($this->getEnabledGateways()) > 0;
        }

        return false;
    }

    public function isSmartneyEnabled(): bool
    {
        return ($this->isEnabled() && $this->isGatewayEnabled(ConfigProvider::SMARTNEY_GATEWAY_ID));
    }

    public function isInstallmentsAvailable(): bool
    {
        return ($this->isEnabled() && $this->isGatewayEnabled(ConfigProvider::ALIOR_INSTALLMENTS_GATEWAY_ID));
    }

    public function isHubAvailable(): bool
    {
        return ($this->isEnabled() && $this->isGatewayEnabled(ConfigProvider::HUB_GATEWAY_ID));
    }

    private function getEnabledGateways(): array
    {
        if ($this->enabledGateways === null) {
            $this->enabledGateways = $this->configProvider->getConsumerFinanceGatewaysEnabledIds();
        }

        return $this->enabledGateways;
    }

    private function isGatewayEnabled($gatewayId): bool
    {
        if ($this->enabledGateways === null) {
            $this->enabledGateways = $this->configProvider->getConsumerFinanceGatewaysEnabledIds();
        }

        return in_array($gatewayId, $this->enabledGateways, false);
    }
}
