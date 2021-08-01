<?php

namespace BlueMedia\BluePayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    public const CREATE_PAYMENT = 'create_payment';
    public const BACK_URL = 'back_url';
    public const GATEWAY_ID = 'gateway_id';
    public const GATEWAY_INDEX = 'gateway_index';
    public const AGREEMENTS_IDS = 'agreements_ids';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::CREATE_PAYMENT,
        self::BACK_URL,
        self::GATEWAY_ID,
        self::GATEWAY_INDEX,
        self::AGREEMENTS_IDS,
    ];

    /**
     * @param  Observer  $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $key) {
            if (isset($additionalData[$key])) {
                $paymentInfo->setAdditionalInformation($key, $additionalData[$key]);
            }
        }
    }
}
