<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\CartRuleInterface;
use Magento\Framework\DataObject;

class CartRule extends DataObject implements CartRuleInterface
{
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
    public function setName(string $name): CartRuleInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return (string) $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description): CartRuleInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getAction(): string
    {
        return (string) $this->getData(self::ACTION);
    }

    /**
     * @inheritDoc
     */
    public function setAction(string $action): CartRuleInterface
    {
        return $this->setData(self::ACTION, $action);
    }

    /**
     * @inheritDoc
     */
    public function getCouponCode(): string
    {
        return (string) $this->getData(self::COUPON_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCouponCode(?string $couponCode): CartRuleInterface
    {
        return $this->setData(self::COUPON_CODE, $couponCode);
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): float
    {
        return (float) $this->getData(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(float $amount): CartRuleInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @inheritDoc
     */
    public function getFreeShipping(): bool
    {
        return (bool) $this->getData(self::FREE_SHIPPING);
    }

    /**
     * @inheritDoc
     */
    public function setFreeShipping(bool $freeShipping): CartRuleInterface
    {
        return $this->setData(self::FREE_SHIPPING, $freeShipping);
    }
}
