<?php

namespace BlueMedia\BluePayment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class AutopayClient implements ClientInterface
{
    /**
     * Void
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        return [];
    }
}
