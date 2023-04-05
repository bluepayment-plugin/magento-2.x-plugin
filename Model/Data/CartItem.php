<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\CartItemInterface;
use Magento\Framework\DataObject;

class CartItem extends DataObject implements CartItemInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return (int) $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId(int $id): CartItemInterface
{
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getSku(): string
    {
        return (string) $this->getData(self::SKU);
    }

    /**
     * @inheritDoc
     */
    public function setSku(string $sku): CartItemInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): CartItemInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): float
    {
        return (float) $this->getData(self::PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setPrice(float $price): CartItemInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritDoc
     */
    public function getQuantity(): int
    {
        return (int) $this->getData(self::QUANTITY);
    }

    /**
     * @inheritDoc
     */
    public function setQuantity(int $quantity): CartItemInterface
    {
        return $this->setData(self::QUANTITY, $quantity);
    }
}
