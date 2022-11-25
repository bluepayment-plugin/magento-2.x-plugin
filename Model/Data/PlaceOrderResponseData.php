<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseDataInterface;
use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface;
use Magento\Framework\DataObject;

class PlaceOrderResponseData extends DataObject implements PlaceOrderResponseDataInterface
{
    /**
     * @inheritDoc
     */
    public function getRemoteOrderId(): string
    {
        return (string) $this->getData(self::REMOTE_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRemoteOrderId(string $remoteOrderId): PlaceOrderResponseDataInterface
    {
        return $this->setData(self::REMOTE_ORDER_ID, $remoteOrderId);
    }
}
