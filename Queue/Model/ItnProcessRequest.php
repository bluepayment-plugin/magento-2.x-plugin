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

    /**
     * ItnProcessRequest constructor.
     * @return SimpleXMLElement
     */
    public function getPayment(): SimpleXMLElement
    {
        return $this->payment;
    }

    /**
     * @param  SimpleXMLElement  $payment
     * @return ItnProcessRequestInterface
     */
    public function setPayment(SimpleXMLElement $payment): ItnProcessRequestInterface
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    /**
     * @param  string  $serviceId
     * @return ItnProcessRequestInterface
     */
    public function setServiceId(string $serviceId): ItnProcessRequestInterface
    {
        $this->serviceId = $serviceId;

        return $this;
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
    }

    /**
     * @param  int  $storeId
     * @return ItnProcessRequestInterface
     */
    public function setStoreId(int $storeId): ItnProcessRequestInterface
    {
        $this->storeId = $storeId;

        return $this;
    }
}
