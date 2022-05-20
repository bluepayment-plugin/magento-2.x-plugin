<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Interface for quote management for AutoPay
 * @api
 */
interface QuoteManagementInterface
{
    /**
     * @param int cartId
     * @param string $hash
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getCartDetails($cartId, $hash);

    /**
     * @param  int  $cartId
     * @param  string  $hash
     *
     * @return mixed
     */
    public function getAddresses($cartId, $hash);

    /**
     * Set shipping address
     *
     * @param  int  $cartId
     * @param  string  $hash
     * @param  AddressInterface  $address
     *
     * @return boolean
     */
    public function setShippingAddress($cartId, $hash, AddressInterface $address);

    /**
     * Set shipping address by ID
     *
     * @param  int  $cartId
     * @param  string  $hash
     * @param  int  $addressId
     *
     * @return boolean
     */
    public function setShippingAddressById($cartId, $hash, $addressId);


    /**
     * Set billing address
     *
     * @param  int  $cartId
     * @param  string  $hash
     * @param  AddressInterface  $address
     *
     * @return boolean
     */
    public function setBillingAddress($cartId, $hash, AddressInterface $address);

    /**
     * Set billing address by ID
     *
     * @param  int  $cartId
     * @param  string  $hash
     * @param  int  $addressId
     *
     * @return boolean
     */
    public function setBillingAddressById($cartId, $hash, $addressId);

    /**
     * @param  int  $cartId
     * @param  string  $hash
     *
     * @return \BlueMedia\BluePayment\Api\Data\ShippingMethodInterface[]
     */
    public function getShippingMethods($cartId, $hash);

    /**
     * @param  int  $cartId
     * @param  string  $hash
     * @param  string  $carrierCode
     * @param  string  $methodCode
     * @param  \BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface  $additional
     *
     * @return boolean
     */
    public function setShippingMethod(
        $cartId,
        $hash,
        $carrierCode,
        $methodCode,
        ShippingMethodAdditionalInterface $additional
    );

    /**
     * @param int $cartId
     * @param string $hash
     * @param float $amount
     *
     * @return int Order ID
     */
    public function placeOrder($cartId, $hash, $amount);
}
