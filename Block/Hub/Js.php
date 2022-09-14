<?php

namespace BlueMedia\BluePayment\Block\Hub;

use BlueMedia\BluePayment\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;

class Js extends Template
{
    protected $configProvider;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    public function isTestMode(): bool
    {
        return $this->configProvider->isTestMode();
    }

    public function getServiceId(): string
    {
        return (string) $this->configProvider->getServiceId(
            $this->configProvider->getCurrentCurrencyCode()
        );
    }

    public function isInstallmentHubAvailable(): bool
    {
        return $this->configProvider->isInstallmentHubAvailable();
    }
}
