<?php

namespace BlueMedia\BluePayment\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\Collection as GatewayCollection;
use Magento\Store\Model\ScopeInterface;

class OrderObserver implements ObserverInterface
{
    /** @var GatewayCollection */
    public $gatewayCollection;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    public function __construct(GatewayCollection $gatewayCollection, ScopeConfigInterface $scopeConfig)
    {
        $this->gatewayCollection = $gatewayCollection;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer)
    {
        /** @var DataObject $transport */
        $transport = $observer->getEvent()->getData('transportObject');

        /** @var Order $order */
        $order = $transport->getData('order');

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        $currency = $order->getOrderCurrencyCode();
        $serviceId = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        $gatewayId = $order->getBlueGatewayId();

        $gateway = $this->gatewayCollection
            ->addFieldToFilter('gateway_service_id', $serviceId)
            ->addFieldToFilter('gateway_id', $gatewayId)
            ->addFieldToFilter('gateway_currency', $currency)
            ->getFirstItem();

        $transport->setData('payment_channel', $gateway->getData('gateway_name'));
    }
}
