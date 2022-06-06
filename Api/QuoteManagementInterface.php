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
     * @param  AddressInterface  $address
     *
     * @return boolean
     */
    public function setShippingAddress($cartId, AddressInterface $address);

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
     * @param  AddressInterface  $address
     *
     * @return boolean
     */
    public function setBillingAddress($cartId, AddressInterface $address);

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
     * @param  \BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface  $additional
     *
     * @return boolean
     */
    public function setShippingMethod(
        $cartId,
        $carrierCode,
        $methodCode,
        ShippingMethodAdditionalInterface $additional
    );

    /**
     * @param int $cartId
     * @param float $amount
     *
     * @return int Order ID
     */
    public function placeOrder($cartId, $amount);
}
