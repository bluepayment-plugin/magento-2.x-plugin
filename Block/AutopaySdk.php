<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Api\ShouldShowAutopayInterface;
use BlueMedia\BluePayment\Model\Autopay\ConfigProvider;
use Magento\Framework\View\Element\Template;

class AutopaySdk extends Template
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var ShouldShowAutopayInterface  */
    private $shouldShowAutopay;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        ShouldShowAutopayInterface $shouldShowAutopay,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->shouldShowAutopay = $shouldShowAutopay;
    }

    /**
     * Get Blue Media Service ID for PLN currency.
     *
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->configProvider->getServiceId();
    }

    /**
     * Whether autopay should be showed - is active or hidden and request is correct.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->shouldShowAutopay->execute();
    }

    /**
     * Whether APC is in test mode.
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->configProvider->isTestMode();
    }
}
