<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

use SimpleXMLElement;

interface ItnProcessRequestInterface
{
    public function getPayment(): SimpleXMLElement;

    public function setPayment(SimpleXMLElement $payment): ItnProcessRequestInterface;

    public function getServiceId(): string;

    public function setServiceId(string $serviceId): ItnProcessRequestInterface;

    public function getStoreId(): int;

    public function setStoreId(int $storeId): ItnProcessRequestInterface;
}
