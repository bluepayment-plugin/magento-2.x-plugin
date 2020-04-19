<?php

namespace BlueMedia\BluePayment\Block\Processing;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Back extends Template
{
    /**
     * @return bool
     */
    public function hasError()
    {
        return (bool) $this->getData('error');
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->getData('message');
    }

    /**
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->getData('status');
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->getData('order');
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData('OrderID');
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->getData('ServiceID');
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->getData('Hash');
    }
}
