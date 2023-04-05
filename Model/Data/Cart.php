<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\CartInterface;
use Magento\Framework\DataObject;

class Cart extends DataObject implements CartInterface
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
    public function setId(int $id): CartInterface
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getTotal(): float
    {
        return (float) $this->getData(self::TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setTotal(float $total): CartInterface
    {
        return $this->setData(self::TOTAL, $total);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return (string) $this->getData(self::CURRENCY);
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): CartInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        return (array) $this->getData(self::ITEMS);
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items): CartInterface
    {
        return $this->setData(self::ITEMS, $items);
    }

    /**
     * @inheritDoc
     */
    public function getRules(): array
    {
        return (array) $this->getData(self::RULES);
    }

    /**
     * @inheritDoc
     */
    public function setRules(array $rules): CartInterface
    {
        return $this->setData(self::RULES, $rules);
    }
}
