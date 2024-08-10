<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Queue\Model;

use BlueMedia\BluePayment\Api\Data\ItnProcessRequestInterface;
use SimpleXMLElement;

class ItnProcessRequest implements ItnProcessRequestInterface
{
    /** @var SimpleXMLElement */
    private $payment;

    /** @var string */
    private $serviceId;

    /** @var int */
    private $storeId;

    public function getPayment(): SimpleXMLElement
    {
        return $this->payment;
    }

    public function setPayment(SimpleXMLElement $payment): ItnProcessRequestInterface
    {
        $this->payment = $payment;
        return $this;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): ItnProcessRequestInterface
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    public function getStoreId(): int
    {
        return $this->store;
    }

    public function setStoreId(int $store): ItnProcessRequestInterface
    {
        $this->store = $store;
        return $this;
    }
}
