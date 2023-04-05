<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface GatewayInterface
 */
interface CartInterface
{
    public const ID = 'id';
    public const TOTAL = 'total';
    public const CURRENCY = 'currency';
    public const ITEMS = 'items';
    public const RULES = 'rules';

    /**
     * Get Cart ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set Cart ID
     *
     * @param int $id Cart ID
     * @return CartInterface
     */
    public function setId(int $id): CartInterface;

    /**
     * Get Cart Total
     *
     * @return float
     */
    public function getTotal(): float;

    /**
     * Set Cart Total
     *
     * @param float $total Cart Total
     * @return CartInterface
     */
    public function setTotal(float $total): CartInterface;

    /**
     * Get Cart Currency
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set Cart Currency
     *
     * @param string $currency Cart Currency
     * @return CartInterface
     */
    public function setCurrency(string $currency): CartInterface;

    /**
     * Get Cart Items
     *
     * @return \BlueMedia\BluePayment\Api\Data\CartItemInterface[]
     */
    public function getItems(): array;

    /**
     * Set Cart Items
     *
     * @param \BlueMedia\BluePayment\Api\Data\CartItemInterface[] $items Cart Items
     * @return CartInterface
     */
    public function setItems(array $items): CartInterface;

    /**
     * Get Cart Rules
     *
     * @return \BlueMedia\BluePayment\Api\Data\CartRuleInterface[]
     */
    public function getRules(): array;

    /**
     * Set Cart Rules
     *
     * @param \BlueMedia\BluePayment\Api\Data\CartRuleInterface[] $rules Cart Rules
     * @return CartInterface
     */
    public function setRules(array $rules): CartInterface;
}
