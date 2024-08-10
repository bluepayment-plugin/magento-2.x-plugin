<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Queue\Handler;

use BlueMedia\BluePayment\Api\Data\ItnProcessRequestInterface;
use BlueMedia\BluePayment\Logger\Logger;

class ItnProcessRequestHandler
{
    /** @var Logger */
    protected $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function process(
        ItnProcessRequestInterface $itnProcessRequest
    ) {
        $this->logger->info('ItnProcessRequestHandler:' . __LINE__, [
            'payment' => (array) $itnProcessRequest->getPayment(),
            'storeId' => $itnProcessRequest->getStoreId(),
            'serviceId' => $itnProcessRequest->getServiceId(),
        ]);
    }
}
