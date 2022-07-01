<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\Autopay\ConfigProvider;
use Magento\Framework\View\Element\Template;

class AutopaySdk extends Template
{
    /** @var ConfigProvider */
    private $configProvider;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    public function getServiceId()
    {
        return $this->configProvider->getServiceId();
    }

    public function isTestMode()
    {
        return $this->configProvider->isTestMode();
    }
}
