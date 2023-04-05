<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

interface CartItemInterface
{
    public const ID = 'id';
    public const SKU = 'sku';
    public const NAME = 'name';
    public const PRICE = 'price';
    public const QUANTITY = 'quantity';

    /**
     * Get Cart Item ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set Cart Item ID
     *
     * @param int $id Cart Item ID
     * @return CartItemInterface
     */
    public function setId(int $id): CartItemInterface;

    /**
     * Get Cart Item SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set Cart Item SKU
     *
     * @param string $sku Cart Item SKU
     * @return CartItemInterface
     */
    public function setSku(string $sku): CartItemInterface;

    /**
     * Get Cart Item Name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set Cart Item Name
     *
     * @param string $name Cart Item Name
     * @return CartItemInterface
     */
    public function setName(string $name): CartItemInterface;

    /**
     * Get Cart Item Price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Set Cart Item Price
     *
     * @param float $price Cart Item Price
     * @return CartItemInterface
     */
    public function setPrice(float $price): CartItemInterface;

    /**
     * Get Cart Item Quantity
     *
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Set Cart Item Quantity
     *
     * @param int $quantity Cart Item Quantity
     * @return CartItemInterface
     */
    public function setQuantity(int $quantity): CartItemInterface;
}
