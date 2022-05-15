<?php

namespace BlueMedia\Autopay\Model\Data;

use BlueMedia\Autopay\Api\Data\ShippingMethodAdditionalInterface;
use Magento\Framework\DataObject;

class ShippingMethodAdditional extends DataObject implements ShippingMethodAdditionalInterface
{
    /**
     * @inheritDoc
     */
    public function getLockerId(): ?string
    {
        return $this->getData(self::LOCKER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLockerId($lockerId)
    {
        $this->setData(self::LOCKER_ID, $lockerId);
        return $this;
    }
}
