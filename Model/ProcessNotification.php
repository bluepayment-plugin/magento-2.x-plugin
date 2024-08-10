<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\ItnProcessRequestInterface;
use BlueMedia\BluePayment\Api\Data\ItnProcessRequestInterfaceFactory;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Helper\Webapi;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayFactory;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use SimpleXMLElement;

class ProcessNotification
{
    /** @var Logger */
    protected $logger;

    /** @var GatewayFactory */
    protected $gatewayFactory;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var GetStateForStatus */
    protected $getStateForStatus;

    /** @var TransactionFactory */
    protected $transactionFactory;

    /** @var TransactionRepositoryInterface */
    protected $transactionRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var SendConfirmationEmail */
    protected $sendConfirmationEmail;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var Webapi */
    protected $webapi;

    /** @var OrderFactory */
    protected $orderFactory;

    /** @var PublisherInterface */
    protected $publisher;

    /** @var ItnProcessRequestInterfaceFactory */
    protected $itnProcessRequestFactory;

    public function __construct(
        Logger $logger,
        GatewayFactory $gatewayFactory,
        ConfigProvider $configProvider,
        GetStateForStatus $getStateForStatus,
        TransactionFactory $transactionFactory,
        TransactionRepositoryInterface $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $eventManager,
        SendConfirmationEmail $sendConfirmationEmail,
        OrderCollectionFactory $orderCollectionFactory,
        Webapi $webapi,
        OrderFactory $orderFactory,
        PublisherInterface $publisher,
        ItnProcessRequestInterfaceFactory $itnProcessRequestFactory
    ) {
        $this->logger = $logger;
        $this->gatewayFactory = $gatewayFactory;
        $this->configProvider = $configProvider;
        $this->getStateForStatus = $getStateForStatus;
        $this->transactionFactory = $transactionFactory;
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->sendConfirmationEmail = $sendConfirmationEmail;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->webapi = $webapi;
        $this->orderFactory = $orderFactory;
        $this->publisher = $publisher;
        $this->itnProcessRequestFactory = $itnProcessRequestFactory;
    }

    public function asyncExecute(
        SimpleXMLElement $payment,
        string $serviceId,
        StoreInterface $store
    ) {
        $isAsyncEnabled = $this->configProvider->isAsyncProcess();

        $this->logger->info('ProcessNotification:' . __LINE__, [
            'isAsyncEnabled' => $isAsyncEnabled,
        ]);

        if ($isAsyncEnabled) {
            /** @var ItnProcessRequestInterface $data */
            $data = $this->itnProcessRequestFactory->create()
                ->setPayment($payment)
                ->setServiceId($serviceId)
                ->setStoreId($store->getId());

            $this->publisher->publish(
                'autopay.itn.process',
                $data
            );

            $this->logger->info('ProcessNotification:' . __LINE__, [
                'published' => 'autopay.itn.process',
            ]);
        } else {
            $this->execute($payment, $serviceId, $store);
        }
    }

    public function execute(
        SimpleXMLElement $payment,
        string $serviceId,
        StoreInterface $store
    ) {
        $paymentStatus = (string) $payment->paymentStatus;

        $remoteId = (string) $payment->remoteID;
        $orderId = (string) $payment->orderID;
        $gatewayId = (int) $payment->gatewayID;
        $currency = (string) $payment->currency;
        $amount = (float) str_replace(',', '.', $payment->amount);

        $this->logger->info('ProcessNotification:' . __LINE__, [
            'remoteId' => $remoteId,
            'orderId' => $orderId,
            'gatewayId' => $gatewayId,
        ]);

        $gateway = $this->gatewayFactory->create()
            ->addFieldToFilter('gateway_service_id', $serviceId)
            ->addFieldToFilter('gateway_id', $gatewayId)
            ->getFirstItem();

        $this->saveTransactionResponse($payment);

        $unchangeableStatuses = $this->configProvider->getUnchangableStatuses($store);
        $statusSuccess = $this->configProvider->getStatusSuccessPayment($store);

        switch ($paymentStatus) {
            case Payment::PAYMENT_STATUS_SUCCESS:
                $status = $this->configProvider->getStatusSuccessPayment($store);
                $state = Order::STATE_PROCESSING;
                break;
            case Payment::PAYMENT_STATUS_FAILURE:
                $status = $this->configProvider->getStatusErrorPayment($store);
                $state = Order::STATE_CANCELED;
                break;
            case Payment::PAYMENT_STATUS_PENDING:
            default:
                $status = $this->configProvider->getStatusWaitingPayment($store);
                $state = Order::STATE_PENDING_PAYMENT;
                break;
        }

        $state = $this->getStateForStatus->execute($status, $state);

        $updateOrders = true;
        if ($paymentStatus === Payment::PAYMENT_STATUS_FAILURE) {
            // Double verify current order status, based on response from WebAPI.
            if (! $this->hasOnlyFailureStatuses((int) $serviceId, $orderId, $currency, $store)) {
                // Order has one success transaction - do not change status to failure
                $updateOrders = false;
                $this->logger->info('Change order ignored');
            }
        }

        $orders = $this->getOrdersByOrderId($orderId);

        $time1 = microtime(true);
        $orderPaymentState = null;

        foreach ($orders as $order) {
            $orderPayment = $order->getPayment();

            if ($orderPayment === null || $orderPayment->getMethod() !== Payment::METHOD_CODE) {
                continue;
            }

            /** @var string $orderPaymentState */
            $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');
            $formattedAmount = number_format(round($amount, 2), 2, '.', '');

            $changeable = $updateOrders;

            if ($changeable) {
                if (in_array($order->getStatus(), $unchangeableStatuses)) {
                    $changeable = false;
                }
                foreach ($order->getAllStatusHistory() as $historyStatus) {
                    if ($historyStatus->getStatus() == $statusSuccess && $order->getTotalDue() == 0) {
                        $changeable = false;
                    }
                }
            }

            try {
                $eventToCall = null;

                if ($changeable && $orderPaymentState != $paymentStatus) {
                    $orderComment =
                        '[BM] Transaction ID: ' . $remoteId
                        . ' | Amount: ' . $formattedAmount . ' ' . $currency
                        . ' | Status: ' . $paymentStatus;

                    $order->setState($state);
                    $order->addStatusToHistory($status, $orderComment);
                    $order->setBlueGatewayId($gatewayId);
                    $order->setPaymentChannel($gateway->getData('gateway_name'));

                    $orderPayment->setTransactionId($remoteId);
                    $orderPayment->prependMessage('[' . $paymentStatus . ']');
                    $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
                    $orderPayment->setAdditionalInformation('bluepayment_gateway', $gatewayId);

                    switch ($paymentStatus) {
                        case Payment::PAYMENT_STATUS_FAILURE:
                            $eventToCall = 'bluemedia_payment_failure';
                            break;
                        case Payment::PAYMENT_STATUS_PENDING:
                            $eventToCall = 'bluemedia_payment_pending';
                            $orderPayment->setIsTransactionPending(true);
                            break;
                        case Payment::PAYMENT_STATUS_SUCCESS:
                            $eventToCall = 'bluemedia_payment_success';

                            if ($order->getBaseCurrencyCode() !== $currency) {
                                $rate = $order->getBaseToOrderRate();
                                $amount = $amount / $rate;
                            }

                            $orderPayment->registerCaptureNotification($amount, true);
                            $orderPayment->setIsTransactionApproved(true);
                            $orderPayment->setIsTransactionClosed(true);
                            break;
                        default:
                            break;
                    }

                    if ($eventToCall) {
                        // Dispatch event
                        $this->eventManager->dispatch($eventToCall, [
                            'order' => $order,
                            'payment' => $payment,
                            'transaction_id' => $remoteId,
                        ]);
                    }
                } else {
                    $orderComment =
                        '[BM] Transaction ID: ' . $remoteId
                        . ' | Amount: ' . $formattedAmount . ' ' . $currency
                        . ' | Status: ' . $paymentStatus . ' [IGNORED]'
                        . (!$updateOrders ? ' Status SUCCESS is in other transaction based on WebAPI.' : '');

                    $order->addStatusToHistory($order->getStatus(), $orderComment);
                }

                $this->orderRepository->save($order);
                $this->sendConfirmationEmail->execute($order);
            } catch (Exception $e) {
                $this->logger->critical($e);
            }
        }

        $time2 = microtime(true);

        $this->logger->info('ProcessNotification:' . __LINE__, [
            'orderID' => $orderId,
            'paymentStatus' => $paymentStatus,
            'orderPaymentState' => $orderPaymentState,
            'time' => round(($time2 - $time1) * 1000, 2) . ' ms',
        ]);
    }


    /**
     * @param SimpleXMLElement $transactionResponse
     *
     * @return void
     */
    private function saveTransactionResponse(SimpleXMLElement $transactionResponse): void
    {
        $transaction = $this->transactionFactory->create();
        $transaction->setOrderId((string) $transactionResponse->orderID)
            ->setRemoteId((string) $transactionResponse->remoteID)
            ->setAmount((float) $transactionResponse->amount)
            ->setCurrency((string) $transactionResponse->currency)
            ->setGatewayId((int) $transactionResponse->gatewayID)
            ->setPaymentDate((string) $transactionResponse->paymentDate)
            ->setPaymentStatus((string) $transactionResponse->paymentStatus)
            ->setPaymentStatusDetails((string) $transactionResponse->paymentStatusDetails);

        try {
            $this->transactionRepository->save($transaction);
        } catch (CouldNotSaveException $e) {
            $this->logger->error(__('Could not save Autopay Transaction entity: ') . $transaction->toJson());
        }
    }

    private function getOrdersByOrderId(string $orderId): array
    {
        if (strpos($orderId, Payment::QUOTE_PREFIX) === 0) {
            $quoteId = substr($orderId, strlen(Payment::QUOTE_PREFIX));

            /** @var DataObject|Order[] $orders */
            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('quote_id', $quoteId)
                ->load();

            $orderIds = [];
            foreach ($orders as $order) {
                $orderIds[] = $order->getIncrementId();
            }

            $this->logger->info('ProcessNotification:' . __LINE__, [
                'quoteId' => (string)$quoteId,
                'orderIds' => $orderIds,
            ]);
        } else {
            /** @var Order[] $orders */
            $orders = [$this->orderFactory->create()->loadByIncrementId($orderId)];
        }

        return $orders;
    }

    private function hasOnlyFailureStatuses(
        int $serviceId,
        string $orderId,
        string $currency,
        StoreInterface $store
    ): bool {
        $response = $this->webapi->transactionStatus($serviceId, $orderId, $currency, $store);

        $this->logger->info('ProcessNotification:' . __LINE__, [
            'serviceId' => $serviceId,
            'orderId' => $orderId,
            'currency' => $currency,
            'transactions' => json_decode(json_encode($response), true),
        ]);

        foreach ($response->transactions->transaction as $transaction) {
            $status = (string) $transaction->paymentStatus;

            $this->logger->info('ProcessNotification:' . __LINE__, [
                'paymentStatus' => $status,
                'transaction' => json_decode(json_encode($transaction), true),
            ]);

            if ($status !== Payment::PAYMENT_STATUS_FAILURE) {
                $this->logger->info('Has not final status.');
                return false;
            }
        }

        $this->logger->info('Has ONLY FAILURE statuses');
        return true;
    }
}
