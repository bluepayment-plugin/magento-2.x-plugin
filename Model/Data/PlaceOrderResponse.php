<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseDataInterface;
use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface;
use Magento\Framework\DataObject;

class PlaceOrderResponse extends DataObject implements PlaceOrderResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return (string) $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): PlaceOrderResponseInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getErrorCode(): ?string
    {
        return $this->getData(self::ERROR_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setErrorCode(string $errorCode): PlaceOrderResponseInterface
    {
        return $this->setData(self::ERROR_CODE, $errorCode);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): ?string
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage(string $errorMessage): PlaceOrderResponseInterface
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }

    /**
     * @inheritDoc
     */
    public function getOrderData(): ?PlaceOrderResponseDataInterface
    {
        return $this->getData(self::ORDER_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setOrderData(PlaceOrderResponseDataInterface $data): PlaceOrderResponseInterface
    {
        return $this->setData(self::ORDER_DATA, $data);
    }
}
