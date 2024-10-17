<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Queue\Model;

use BlueMedia\BluePayment\Api\Data\ItnProcessRequestInterface;
use SimpleXMLElement;

class ItnProcessRequest implements ItnProcessRequestInterface
{
    /** @var string */
    private $paymentXml;

    /** @var string */
    private $serviceId;

    /** @var int */
    private $storeId;

    /**
     * ItnProcessRequest constructor.
     * @return string
     */
    public function getPaymentXml(): string
    {
        return $this->paymentXml;
    }

    /**
     * @param  SimpleXMLElement  $payment
     * @return ItnProcessRequestInterface
     */
    public function setPaymentXml(string $payment): ItnProcessRequestInterface
    {
        $this->paymentXml = $payment;

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
