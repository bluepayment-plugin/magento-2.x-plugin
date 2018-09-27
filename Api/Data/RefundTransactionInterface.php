<?php

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface TransactionInterface
 * @package BlueMedia\BluePayment\Api\Data
 */
interface RefundTransactionInterface
{
    const ID = 'refund_id';

    const ORDER_ID = 'order_id';

    const REMOTE_ID = 'remote_id';

    const REMOTE_OUT_ID = 'remote_out_id';

    const AMOUNT = 'amount';

    const CURRENCY = 'currency';

    const IS_PARTIAL = 'is_partial';

    /**
     * @return string
     */
    public function getOrderId(): string;

    /**
     * @param string $orderId
     *
     * @return $this
     */
    public function setOrderId(string $orderId);

    /**
     * @return string
     */
    public function getRemoteId(): string;

    /**
     * @param string $remoteId
     *
     * @return $this
     */
    public function setRemoteId(string $remoteId);

    /**
     * @return string
     */
    public function getRemoteOutId(): string;

    /**
     * @param string $remoteId
     *
     * @return $this
     */
    public function setRemoteOutId(string $remoteId);

    /**
     * @return float
     */
    public function getAmount(): float;

    /**
     * @param float $amount
     *
     * @return $this
     */
    public function setAmount(float $amount);

    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency(string $currency);

    /**
     * @return bool
     */
    public function isPartial(): bool;

    /**
     * @param bool $isPartial
     *
     * @return $this
     */
    public function setIsPartial(bool $isPartial);

}