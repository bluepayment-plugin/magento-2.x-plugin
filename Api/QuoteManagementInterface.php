<?php

namespace BlueMedia\BluePayment\Api;

/**
 * Interface for quote management for Autopay
 * @api
 */
interface QuoteManagementInterface
{
    /**
     * Get website configuration.
     *
     * @return \BlueMedia\BluePayment\Api\Data\ConfigurationInterface
     */
    public function getConfiguration(): \BlueMedia\BluePayment\Api\Data\ConfigurationInterface;

    /**
     * Get cart details by id.
     *
     * @param int $cartId
     * @return \BlueMedia\BluePayment\Api\Data\CartInterface
     */
    public function getCartDetails(int $cartId): \BlueMedia\BluePayment\Api\Data\CartInterface;

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
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return boolean
     */
    public function setShippingAddress(int $cartId, \Magento\Quote\Api\Data\AddressInterface $address): bool;

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
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return boolean
     */
    public function setBillingAddress(int $cartId, \Magento\Quote\Api\Data\AddressInterface $address): bool;

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
     * @return \BlueMedia\BluePayment\Api\Data\ShippingMethodInterface[]
     */
    public function getShippingMethods(int $cartId): array;

    /**
     * Set shipping method for cart.
     *
     * @param int $cartId
     * @param string $carrierCode
     * @param string $methodCode
     * @param \BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface|null $additional
     * @return boolean
     */
    public function setShippingMethod(
        int $cartId,
        string $carrierCode,
        string $methodCode,
        \BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface $additional = null
    ): bool;

    /**
     * Place order.
     *
     * @param int $cartId
     * @param float $amount
     * @return \BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface
     */
    public function placeOrder(int $cartId, float $amount): \BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface;
}
