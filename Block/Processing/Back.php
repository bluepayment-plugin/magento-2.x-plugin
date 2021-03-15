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

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->getData('orders');
    }

    /**
     * @return bool
     */
    public function isMultishipping()
    {
        return $this->getOrders() !== null;
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getViewOrderUrl($orderId)
    {
        return $this->getUrl('sales/order/view/', ['order_id' => $orderId, '_secure' => true]);
    }
}
