<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface;

/**
 * Interface for quote management for Autopay
 * @api
 */
interface QuoteManagementInterface
{
    /**
     * @return \BlueMedia\BluePayment\Api\Data\ConfigurationInterface
     */
    public function getConfiguration();

    /**
     * @param int $cartId
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getCartDetails($cartId);

    /**
     * @param  int  $cartId
     *
     * @return mixed
     */
    public function getAddresses($cartId);

    /**
     * Set shipping address
     *
     * @param  int  $cartId
     * @param  \Magento\Quote\Api\Data\AddressInterface  $address
     *
     * @return boolean
     */
    public function setShippingAddress($cartId, \Magento\Quote\Api\Data\AddressInterface $address);

    /**
     * Set shipping address by ID
     *
     * @param  int  $cartId
     * @param  int  $addressId
     *
     * @return boolean
     */
    public function setShippingAddressById($cartId, $addressId);


    /**
     * Set billing address
     *
     * @param  int  $cartId
     * @param  \Magento\Quote\Api\Data\AddressInterface  $address
     *
     * @return boolean
     */
    public function setBillingAddress($cartId, \Magento\Quote\Api\Data\AddressInterface $address);

    /**
     * Set billing address by ID
     *
     * @param  int  $cartId
     * @param  int  $addressId
     *
     * @return boolean
     */
    public function setBillingAddressById($cartId, $addressId);

    /**
     * @param  int  $cartId
     *
     * @return \BlueMedia\BluePayment\Api\Data\ShippingMethodInterface[]
     */
    public function getShippingMethods($cartId);

    /**
     * @param  int  $cartId
     * @param  string  $carrierCode
     * @param  string  $methodCode
     * @param  \BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface|null  $additional
     *
     * @return boolean
     */
    public function setShippingMethod(
        $cartId,
        $carrierCode,
        $methodCode,
        \BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface $additional = null
    );

    /**
     * @param int $cartId
     * @param float $amount
     *
     * @return \BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface
     */
    public function placeOrder(int $cartId, float $amount): PlaceOrderResponseInterface;
}
