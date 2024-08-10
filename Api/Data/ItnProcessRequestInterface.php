<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

use SimpleXMLElement;

interface ItnProcessRequestInterface
{
    /**
     * @return SimpleXMLElement
     */
    public function getPayment(): SimpleXMLElement;

    /**
     * @param SimpleXMLElement $payment
     *
     * @return ItnProcessRequestInterface
     */
    public function setPayment(SimpleXMLElement $payment): ItnProcessRequestInterface;

    /**
     * @return string
     */
    public function getServiceId(): string;

    /**
     * @param string $serviceId
     *
     * @return ItnProcessRequestInterface
     */
    public function setServiceId(string $serviceId): ItnProcessRequestInterface;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     *
     * @return ItnProcessRequestInterface
     */
    public function setStoreId(int $storeId): ItnProcessRequestInterface;
}
