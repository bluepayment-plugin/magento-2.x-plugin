<?php

namespace BlueMedia\BluePayment\Cron;

use BlueMedia\BluePayment\Helper\Gateways;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

/**
 * Gateway synchronization CRON Job
 */
class Synchronization
{
    /** @var Logger */
    public $logger;

    /**
     * @var Gateways
     */
    public $gatewayHelper;

    /**
     * Synchronization constructor.
     *
     * @param Gateways $gatewayHelper
     */
    public function __construct(Gateways $gatewayHelper)
    {
        $writer = new Stream(BP . '/var/log/bluemedia.log');
        $this->logger = new Logger();
        $this->logger->addWriter($writer);

        $this->gatewayHelper = $gatewayHelper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $this->logger->info(__METHOD__);
        $this->gatewayHelper->syncGateways();

        return $this;
    }
}
