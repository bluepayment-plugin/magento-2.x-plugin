<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Cron;

use BlueMedia\BluePayment\Api\RefundStatusUpdaterInterface;
use BlueMedia\BluePayment\Service\RefundStatusUpdaterService as RefundStatusUpdaterService;

class RefundStatusUpdater
{
    /**
     * @var RefundStatusUpdaterService
     */
    protected $refundStatusUpdater;

    public function __construct(
        RefundStatusUpdaterInterface $refundStatusUpdater
    ) {
        $this->refundStatusUpdater = $refundStatusUpdater;
    }

    public function execute()
    {
        $this->refundStatusUpdater->updateRefundStatuses();
    }
}
