<?php

namespace BlueMedia\BluePayment\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection as GatewaysCollection;

class OrderObserver implements ObserverInterface
{
    /** @var GatewaysCollection */
    public $gatewayCollection;

    public function __construct(GatewaysCollection $gatewayCollection)
    {
        $this->gatewayCollection = $gatewayCollection;
    }

    public function execute(Observer $observer)
    {
        /** @var DataObject $transport */
        $transport = $observer->getEvent()->getData('transportObject');
        /** @var Order $order */
        $order = $transport->getData('order');
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        $gatewayId = $payment->getAdditionalInformation('bluepayment_gateway');

        $gateway = $this->gatewayCollection
            ->addFieldToFilter('gateway_id', $gatewayId)
            ->addFieldToFilter('gateway_currency', $order->getOrderCurrencyCode())
            ->getFirstItem();

        $transport->setData('payment_channel', $gateway->getData('gateway_name'));
    }
}
