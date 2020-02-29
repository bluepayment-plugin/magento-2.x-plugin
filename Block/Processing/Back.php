<?php

namespace BlueMedia\BluePayment\Block\Processing;

use Magento\Framework\View\Element\Template;

class Back extends Template
{
    public function hasError()
    {
        return (bool) $this->getData('error');
    }

    public function getErrorMessage()
    {
        return $this->getData('message');
    }

    public function getOrderStatus()
    {
        return $this->getData('status');
    }

    public function getOrder()
    {
        return $this->getData('order');
    }

    public function getOrderId()
    {
        return $this->getData('OrderID');
    }

    public function getServiceId()
    {
        return $this->getData('ServiceID');
    }

    public function getHash()
    {
        return $this->getData('Hash');
    }
}
