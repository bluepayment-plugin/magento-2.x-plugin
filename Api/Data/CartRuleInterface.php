<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

interface CartRuleInterface
{
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const ACTION = 'action';
    public const COUPON_CODE = 'coupon_code';
    public const AMOUNT = 'amount';
    public const FREE_SHIPPING = 'free_shipping';

    /**
     * Get Cart Rule Name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set Cart Rule Name
     *
     * @param string $name Cart Rule Name
     * @return CartRuleInterface
     */
    public function setName(string $name): CartRuleInterface;

    /**
     * Get Cart Rule Description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Set Cart Rule Description
     *
     * @param string|null $description Cart Rule Description
     * @return CartRuleInterface
     */
    public function setDescription(?string $description): CartRuleInterface;

    /**
     * Get Cart Rule Action
     *
     * @return string
     */
    public function getAction(): string;

    /**
     * Set Cart Rule Action
     *
     * @param string $action Cart Rule Action
     * @return CartRuleInterface
     */
    public function setAction(string $action): CartRuleInterface;

    /**
     * Get Cart Rule Coupon Code
     *
     * @return string
     */
    public function getCouponCode(): string;

    /**
     * Set Cart Rule Coupon Code
     *
     * @param string|null $couponCode Cart Rule Coupon Code
     * @return CartRuleInterface
     */
    public function setCouponCode(?string $couponCode): CartRuleInterface;

    /**
     * Get Cart Rule Amount
     *
     * @return float
     */
    public function getAmount(): float;

    /**
     * Set Cart Rule Amount
     *
     * @param float $amount Cart Rule Amount
     * @return CartRuleInterface
     */
    public function setAmount(float $amount): CartRuleInterface;

    /**
     * Get Cart Rule Free Shipping
     *
     * @return bool
     */
    public function getFreeShipping(): bool;

    /**
     * Set Cart Rule Free Shipping
     *
     * @param bool $freeShipping Cart Rule Free Shipping
     * @return CartRuleInterface
     */
    public function setFreeShipping(bool $freeShipping): CartRuleInterface;
}
