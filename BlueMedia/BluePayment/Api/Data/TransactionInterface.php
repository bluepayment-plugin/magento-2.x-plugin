<?php

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface TransactionInterface
 * @package BlueMedia\BluePayment\Api\Data
 */
interface TransactionInterface
{
    const ID = 'transaction_id';

    const ORDER_ID = 'order_id';

    const REMOTE_ID = 'remote_id';

    const AMOUNT = 'amount';

    const CURRENCY = 'currency';

    const GATEWAY_ID = 'gateway_id';

    const PAYMENT_DATE = 'payment_date';

    const PAYMENT_STATUS = 'payment_status';

    const PAYMENT_STATUS_DETAILS = 'payment_status_details';

    const STATUS_PENDING = 'PENDING';

    const STATUS_SUCCESS = 'SUCCESS';

    const STATUS_DETAIL_SUCCESS = ['AUTHORIZED', 'ACCEPTED'];

    /**
     * @return string
     */
    public function getOrderId();

    /**
     * @param string $orderId
     *
     * @return $this
     */
    public function setOrderId(string $orderId);

    /**
     * @return string
     */
    public function getRemoteId();

    /**
     * @param string $remoteId
     *
     * @return $this
     */
    public function setRemoteId(string $remoteId);

    /**
     * @return float
     */
    public function getAmount();

    /**
     * @param float $amount
     *
     * @return $this
     */
    public function setAmount(float $amount);

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency(string $currency);

    /**
     * @return int
     */
    public function getGatewayId();

    /**
     * @param int $gatewayId
     *
     * @return $this
     */
    public function setGatewayId(int $gatewayId);

    /**
     * @return string
     */
    public function getPaymentDate();

    /**
     * @param $date
     *
     * @return $this
     */
    public function setPaymentDate($date);

    /**
     * @return string
     */
    public function getPaymentStatus();

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setPaymentStatus(string $status);

    /**
     * @return string
     */
    public function getPaymentStatusDetails();

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setPaymentStatusDetails(string $status);
}
