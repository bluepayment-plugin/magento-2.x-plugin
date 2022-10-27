<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\GetStateForStatus;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;

class PlaceOrderObserver implements ObserverInterface
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var GetStateForStatus */
    private $getStateForStatus;

    /**
     * PlaceOrderObserver constructor
     *
     * @param ConfigProvider $configProvider
     * @param GetStateForStatus $getStateForStatus
     */
    public function __construct(
        ConfigProvider $configProvider,
        GetStateForStatus $getStateForStatus
    ) {
        $this->configProvider = $configProvider;
        $this->getStateForStatus = $getStateForStatus;
    }

    /**
     * Observer for sales_order_payment_place_end
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order\Payment $payment */
        $payment = $observer->getData('payment');
        $method = $payment->getMethod();
        if ($method !== Payment::METHOD_CODE) {
            return;
        }

        $status = $this->configProvider->getStatusWaitingPayment();
        $state = $this->getStateForStatus->execute($status, Order::STATE_PENDING_PAYMENT);

        $order = $payment->getOrder();
        $order->setStatus($status)
            ->setState($state);
    }
}
