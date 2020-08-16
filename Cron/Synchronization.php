<?php

namespace BlueMedia\BluePayment\Cron;

use BlueMedia\BluePayment\Helper\Gateways;
use BlueMedia\BluePayment\Logger\Logger;

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
    public function __construct(Gateways $gatewayHelper, Logger $logger)
    {
        $this->gatewayHelper = $gatewayHelper;
        $this->logger = $logger;
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
