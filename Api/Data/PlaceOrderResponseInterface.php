<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface GatewayInterface
 */
interface PlaceOrderResponseInterface
{
    public const STATUS = 'status';
    public const ERROR_CODE = 'error_code';
    public const ERROR_MESSAGE = 'error_message';
    public const ORDER_DATA = 'data';

    /**
     * Could be "SUCCESS" or "INVALID"
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): PlaceOrderResponseInterface;

    /**
     * @return ?string
     */
    public function getErrorCode(): ?string;

    /**
     * @param string $errorCode
     * @return $this
     */
    public function setErrorCode(string $errorCode): PlaceOrderResponseInterface;

    /**
     * @return ?string
     */
    public function getErrorMessage(): ?string;

    /**
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage(string $errorMessage): PlaceOrderResponseInterface;

    /**
     * @return \BlueMedia\BluePayment\Api\Data\PlaceOrderResponseDataInterface|null
     */
    public function getOrderData(): ?PlaceOrderResponseDataInterface;

    /**
     * @param \BlueMedia\BluePayment\Api\Data\PlaceOrderResponseDataInterface $data
     * @return $this
     */
    public function setOrderData(PlaceOrderResponseDataInterface $data): PlaceOrderResponseInterface;
}
