<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Queue\Handler;

use BlueMedia\BluePayment\Api\Data\ItnProcessRequestInterface;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ProcessNotification;
use Magento\Store\Model\StoreManagerInterface;

class ItnProcessRequestHandler
{
    /** @var Logger */
    protected $logger;

    /** @var ProcessNotification */
    protected $processNotification;

    /** @var StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        Logger $logger,
        ProcessNotification $processNotification,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->processNotification = $processNotification;
        $this->storeManager = $storeManager;
    }

    public function process(
        ItnProcessRequestInterface $itnProcessRequest
    ) {
        $xml = simplexml_load_string($itnProcessRequest->getPaymentXml());

        $this->logger->info('ItnProcessRequestHandler:' . __LINE__, [
            'paymentXml' => $itnProcessRequest->getPaymentXml(),
            'xml' => (array) $xml,
            'storeId' => $itnProcessRequest->getStoreId(),
            'serviceId' => $itnProcessRequest->getServiceId(),
        ]);

        $this->processNotification->execute(
            $xml,
            (string)$itnProcessRequest->getServiceId(),
            $itnProcessRequest->getStoreId()
        );

        $this->logger->info('ItnProcessRequestHandler:' . __LINE__, [
            'handled' => true,
        ]);
    }
}
