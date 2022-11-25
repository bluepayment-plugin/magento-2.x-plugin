<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface GatewayInterface
 */
interface PlaceOrderResponseDataInterface
{
    public const REMOTE_ORDER_ID = 'remote_order_id';

    /**
     * Increment Order ID from sales_table
     *
     * @return string
     */
    public function getRemoteOrderId(): string;

    /**
     * @param string $remoteOrderId
     * @return $this
     */
    public function setRemoteOrderId(string $remoteOrderId): PlaceOrderResponseDataInterface;
}
