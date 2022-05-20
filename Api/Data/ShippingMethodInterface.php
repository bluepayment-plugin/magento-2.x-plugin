<?php

namespace BlueMedia\BluePayment\Api\Data;

interface ShippingMethodInterface
{
    /**
     * String constants for property names
     */
    public const CARRIER_CODE = "carrier_code";
    public const CARRIER_TITLE = "carrier_title";
    public const METHOD_CODE = "method_code";
    public const METHOD_TITLE = "method_title";
    public const AMOUNT = "amount";

    /**
     * Getter for CarrierCode.
     *
     * @return string|null
     */
    public function getCarrierCode();

    /**
     * Setter for CarrierCode.
     *
     * @param  string|null  $carrierCode
     *
     * @return $this
     */
    public function setCarrierCode($carrierCode);

    /**
     * Getter for CarrierTitle.
     *
     * @return string|null
     */
    public function getCarrierTitle();

    /**
     * Setter for CarrierTitle.
     *
     * @param  string|null  $carrierTitle
     *
     * @return $this
     */
    public function setCarrierTitle($carrierTitle);

    /**
     * Getter for MethodCode.
     *
     * @return string|null
     */
    public function getMethodCode();

    /**
     * Setter for MethodCode.
     *
     * @param  string|null  $methodCode
     *
     * @return $this
     */
    public function setMethodCode($methodCode);

    /**
     * Getter for MethodTitle.
     *
     * @return string|null
     */
    public function getMethodTitle();

    /**
     * Setter for MethodTitle.
     *
     * @param  string|null  $methodTitle
     *
     * @return $this
     */
    public function setMethodTitle($methodTitle);

    /**
     * Getter for Amount.
     *
     * @return float|null
     */
    public function getAmount();

    /**
     * Setter for Amount.
     *
     * @param  float|null  $amount
     *
     * @return $this
     */
    public function setAmount($amount);
}
