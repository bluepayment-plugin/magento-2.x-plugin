<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Model\ConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ResetActiveGateways implements ObserverInterface
{
    /** @var ConfigProvider */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Clear gateways cached by ConfigProvider
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->configProvider->_resetState();
    }
}
