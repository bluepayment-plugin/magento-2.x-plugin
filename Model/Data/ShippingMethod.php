<?php

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\ShippingMethodInterface;
use Magento\Framework\DataObject;

class ShippingMethod extends DataObject implements ShippingMethodInterface
{
    /**
     * @inheritDoc
     */
    public function getCarrierCode(): ?string
    {
        return $this->getData(self::CARRIER_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCarrierCode($carrierCode)
    {
        $this->setData(self::CARRIER_CODE, $carrierCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCarrierTitle(): ?string
    {
        return $this->getData(self::CARRIER_TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setCarrierTitle($carrierTitle)
    {
        $this->setData(self::CARRIER_TITLE, $carrierTitle);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMethodCode()
    {
        return $this->getData(self::METHOD_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setMethodCode($methodCode)
    {
        $this->setData(self::METHOD_CODE, $methodCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMethodTitle()
    {
        return $this->getData(self::METHOD_TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setMethodTitle($methodTitle)
    {
        $this->setData(self::METHOD_TITLE, $methodTitle);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAmount()
    {
        return (float) $this->getData(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount($amount)
    {
        $this->setData(self::AMOUNT, $amount);
        return $this;
    }
}
