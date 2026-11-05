<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DisableProcessingResponseCache implements ObserverInterface
{
    private const ACTIONS = [
        'bluepayment_processing_create',
        'bluepayment_processing_back',
        'bluepayment_processing_continuepayment',
        'bluepayment_processing_paymentstatus',
        'bluepayment_processing_redirect',
    ];

    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $action = strtolower($event->getRequest()->getFullActionName());
        if (!in_array($action, self::ACTIONS, true)) {
            return;
        }

        $response = $event->getResponse();
        if ($response === null) {
            $response = $event->getControllerAction()->getResponse();
        }

        // Email and layout rendering can replace Magento's initial no-cache headers.
        $response->setNoCacheHeaders();
    }
}
