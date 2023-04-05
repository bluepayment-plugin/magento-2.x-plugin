<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\CartInterface;
use BlueMedia\BluePayment\Api\Data\ConfigurationInterface;
use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface;
use BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface;
use BlueMedia\BluePayment\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Interface for quote management for Autopay
 * @api
 */
interface QuoteManagementInterface
{
    /**
     * Get website configuration.
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration(): Data\ConfigurationInterface;

    /**
     * Get cart details by id.
     *
     * @param int $cartId
     * @return CartInterface
     */
    public function getCartDetails(int $cartId): CartInterface;

    /**
     * Get available addresses for cart.
     *
     * @param int $cartId
     * @return mixed
     */
    public function getAddresses(int $cartId);

    /**
     * Set shipping address
     *
     * @param int $cartId
     * @param AddressInterface $address
     * @return boolean
     */
    public function setShippingAddress(int $cartId, AddressInterface $address): bool;

    /**
     * Set shipping address by ID
     *
     * @param int $cartId
     * @param int $addressId
     * @return boolean
     */
    public function setShippingAddressById(int $cartId, int $addressId);


    /**
     * Set billing address
     *
     * @param int $cartId
     * @param AddressInterface $address
     * @return boolean
     */
    public function setBillingAddress(int $cartId, AddressInterface $address): bool;

    /**
     * Set billing address by ID
     *
     * @param int $cartId
     * @param int $addressId
     * @return boolean
     */
    public function setBillingAddressById(int $cartId, int $addressId): bool;

    /**
     * Get available shipping methods for cart.
     *
     * @param int $cartId
     * @return ShippingMethodInterface[]
     */
    public function getShippingMethods(int $cartId): array;

    /**
     * Set shipping method for cart.
     *
     * @param int $cartId
     * @param string $carrierCode
     * @param string $methodCode
     * @param ShippingMethodAdditionalInterface|null $additional
     * @return boolean
     */
    public function setShippingMethod(
        int $cartId,
        string $carrierCode,
        string $methodCode,
        ShippingMethodAdditionalInterface $additional = null
    ): bool;

    /**
     * Place order.
     *
     * @param int $cartId
     * @param float $amount
     * @return PlaceOrderResponseInterface
     */
    public function placeOrder(int $cartId, float $amount): PlaceOrderResponseInterface;
}
