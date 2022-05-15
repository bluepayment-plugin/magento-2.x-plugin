<?php

namespace BlueMedia\Autopay\Api\Data;

interface ShippingMethodAdditionalInterface
{
    /**
     * String constants for property names
     */
    public const LOCKER_ID = "locker_id";

    /**
     * @return string|null
     */
    public function getLockerId();

    /**
     * @param string $lockerId
     * @return $this
     */
    public function setLockerId($lockerId);
}
