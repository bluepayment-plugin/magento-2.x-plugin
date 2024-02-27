<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use Magento\Sales\Api\Data\OrderInterface;

class GetPhoneForOrder
{
    public function execute(OrderInterface  $order)
    {
        $address = $order->getBillingAddress();
        if (! $address) {
            return null;
        }

        $phone = $address->getTelephone();
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 9 || strlen($phone) > 15) {
            return null;
        }

        return $phone;
    }
}
