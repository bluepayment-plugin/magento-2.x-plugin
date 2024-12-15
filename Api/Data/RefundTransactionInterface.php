<?php

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface TransactionInterface
 */
interface RefundTransactionInterface
{
    const ID = 'refund_id';
    const ORDER_ID = 'order_id';
    const REMOTE_ID = 'remote_id';
    const MESSAGE_ID = 'message_id';
    const REMOTE_OUT_ID = 'remote_out_id';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const IS_PARTIAL = 'is_partial';

    /**
     * @return string
     */
    public function getOrderId(): string;

    /**
     * @param  string  $orderId
     *
     * @return $this
     */
    public function setOrderId(string $orderId): RefundTransactionInterface;

    /**
     * @return string
     */
    public function getRemoteId(): string;

    /**
     * @param  string  $remoteId
     *
     * @return $this
     */
    public function setRemoteId(string $remoteId): RefundTransactionInterface;

    /**
     * @return string
     */
    public function getMessageId(): string;

    /**
     * @param  string  $messageId
     *
     * @return $this
     */
    public function setMessageId(string $messageId): RefundTransactionInterface;

    /**
     * @return string
     */
    public function getRemoteOutId(): string;

    /**
     * @param  string  $remoteId
     *
     * @return $this
     */
    public function setRemoteOutId(string $remoteId): RefundTransactionInterface;

    /**
     * @return float
     */
    public function getAmount(): float;

    /**
     * @param  float  $amount
     *
     * @return $this
     */
    public function setAmount(float $amount): RefundTransactionInterface;

    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @param  string  $currency
     *
     * @return $this
     */
    public function setCurrency(string $currency): RefundTransactionInterface;

    /**
     * @return bool
     */
    public function isPartial(): bool;

    /**
     * @param  bool  $isPartial
     *
     * @return $this
     */
    public function setIsPartial(bool $isPartial): RefundTransactionInterface;

    /**
     * Save object data
     *
     * @return $this
     */
    public function save();

    /**
     * Delete object from database
     *
     * @return $this
     */
    public function delete();
}
