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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
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

    /** @var ResourceConnection */
    protected $resourceConnection;

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
        ItnProcessRequestInterfaceFactory $itnProcessRequestFactory,
        ResourceConnection $resourceConnection
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
        $this->resourceConnection = $resourceConnection;
    }

    public function asyncExecute(
        SimpleXMLElement $payment,
        string $serviceId,
        int $storeId
    ) {
        $isAsyncEnabled = $this->configProvider->isAsyncProcess();

        $this->logger->info('ProcessNotification:' . __LINE__, [
            'isAsyncEnabled' => $isAsyncEnabled,
        ]);

        if ($isAsyncEnabled) {
            $data = $this->itnProcessRequestFactory->create()
                ->setPaymentXml($payment->asXML())
                ->setServiceId($serviceId)
                ->setStoreId($storeId);

            $this->publisher->publish(
                'autopay.itn.process',
                $data
            );

            $this->logger->info('ProcessNotification:' . __LINE__, [
                'published' => 'autopay.itn.process',
            ]);
        } else {
            $this->execute($payment, $serviceId, $storeId);
        }
    }

    public function execute(
        SimpleXMLElement $payment,
        string $serviceId,
        int $storeId
    ) {
        $paymentStatus = (string) $payment->paymentStatus;

        $remoteId = (string) $payment->remoteID;
        $orderId = (string) $payment->orderID;
        $gatewayId = (int) $payment->gatewayID;
        $currency = (string) $payment->currency;
        $amount = (float) str_replace(',', '.', (string) $payment->amount);

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

        $unchangeableStatuses = $this->configProvider->getUnchangableStatuses($storeId);
        $statusSuccess = $this->configProvider->getStatusSuccessPayment($storeId);

        switch ($paymentStatus) {
            case Payment::PAYMENT_STATUS_SUCCESS:
                $status = $statusSuccess;
                $defaultState = Order::STATE_PROCESSING;
                break;
            case Payment::PAYMENT_STATUS_FAILURE:
                $status = $this->configProvider->getStatusErrorPayment($storeId);
                $defaultState = Order::STATE_CANCELED;
                break;
            case Payment::PAYMENT_STATUS_PENDING:
            default:
                $status = $this->configProvider->getStatusWaitingPayment($storeId);
                $defaultState = Order::STATE_PENDING_PAYMENT;
                break;
        }

        $state = $this->getStateForStatus->execute($status, $defaultState);

        $shouldUpdateOrder = $this->shouldUpdateOrder(
            $paymentStatus,
            $serviceId,
            $orderId,
            $currency,
            $storeId
        );

        $orders = $this->getOrdersByOrderId($orderId);

        $time1 = microtime(true);
        $orderPaymentState = null;

        $connection = $this->resourceConnection->getConnection();

        foreach ($orders as $order) {
            try {
                $orderPayment = $order->getPayment();

                if ($orderPayment === null || $orderPayment->getMethod() !== Payment::METHOD_CODE) {
                    continue;
                }

                // Lock the order row
                $tableName = $this->resourceConnection->getTableName('sales_order');
                $orderId = $order->getId();

                $connection->beginTransaction();
                $select = $connection->select()
                    ->from($tableName)
                    ->where('entity_id = ?', $orderId)
                    ->forUpdate();
                $connection->fetchAll($select);

                $orderPaymentState = $this->processOrder(
                    $orderPayment,
                    $amount,
                    $shouldUpdateOrder,
                    $order,
                    $unchangeableStatuses,
                    $statusSuccess,
                    $paymentStatus,
                    $remoteId,
                    $currency,
                    $state,
                    $status,
                    $gatewayId,
                    $gateway,
                    $payment
                );

                // Unlock
                $connection->commit();
            } catch (Exception $e) {
                $this->logger->critical('ProcessNotification:' . __LINE__ . ' - ' . $e->getMessage());
                $connection->rollBack();
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

    /**
     * For failure status, check if order has only failure statuses by API.
     *
     * @param string $paymentStatus
     * @param string $serviceId
     * @param string $orderId
     * @param string $currency
     * @param int $storeId
     * @return bool
     */
    protected function shouldUpdateOrder(
        string $paymentStatus,
        string $serviceId,
        string $orderId,
        string $currency,
        int $storeId
    ): bool {
        if ($paymentStatus === Payment::PAYMENT_STATUS_FAILURE) {
            // Double verify current order status, based on response from WebAPI.
            if (!$this->hasOnlyFailureStatuses($serviceId, $orderId, $currency, $storeId)) {
                // Order has one success transaction - do not change status to failure
                $this->logger->info('Change order ignored');
                return false;
            }
        }

        return true;
    }

    /**
     * Check if order has only failure statuses by API.
     *
     * @param string $serviceId
     * @param string $orderId
     * @param string $currency
     * @param int $storeId
     * @return bool
     */
    private function hasOnlyFailureStatuses(
        string $serviceId,
        string $orderId,
        string $currency,
        int $storeId
    ): bool {
        $response = $this->webapi->transactionStatus(
            $serviceId,
            $orderId,
            $currency,
            $storeId
        );

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

    /**
     * @param $orderPayment
     * @param $amount
     * @param bool $shouldUpdateOrder
     * @param Order $order
     * @param array $unchangeableStatuses
     * @param ?string $statusSuccess
     * @param string $paymentStatus
     * @param string $remoteId
     * @param $currency
     * @param $state
     * @param string|null $status
     * @param int $gatewayId
     * @param DataObject $gateway
     * @param SimpleXMLElement $payment
     * @return string
     */
    protected function processOrder(
        $orderPayment,
        $amount,
        bool $shouldUpdateOrder,
        Order $order,
        array $unchangeableStatuses,
        ?string $statusSuccess,
        string $paymentStatus,
        string $remoteId,
        $currency,
        $state,
        ?string $status,
        int $gatewayId,
        DataObject $gateway,
        SimpleXMLElement $payment
    ): ?string {
        /** @var string $orderPaymentState */
        $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');
        $formattedAmount = number_format(round($amount, 2), 2, '.', '');

        $changeable = $shouldUpdateOrder;

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

        if ($changeable && $orderPaymentState != $paymentStatus) {
            $orderComment =
                '[BM] Transaction ID: '.$remoteId
                .' | Amount: '.$formattedAmount.' '.$currency
                .' | Status: '.$paymentStatus;

            $order->setState($state);
            $order->addStatusToHistory($status, $orderComment);
            $order->setBlueGatewayId($gatewayId);
            $order->setPaymentChannel($gateway->getData('gateway_name'));

            $orderPayment->setTransactionId($remoteId);
            $orderPayment->prependMessage('['.$paymentStatus.']');
            $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
            $orderPayment->setAdditionalInformation('bluepayment_gateway', $gatewayId);

            switch ($paymentStatus) {
                case Payment::PAYMENT_STATUS_PENDING:
                    $orderPayment->setIsTransactionPending(true);
                    break;
                case Payment::PAYMENT_STATUS_SUCCESS:
                    if ($order->getBaseCurrencyCode() !== $currency) {
                        $rate = $order->getBaseToOrderRate();
                        $amount = $amount / $rate;
                    }

                    $orderPayment->registerCaptureNotification($amount, true);
                    $orderPayment->setIsTransactionApproved(true);
                    $orderPayment->setIsTransactionClosed(true);
                    break;
                default:
                case Payment::PAYMENT_STATUS_FAILURE:
                    break;
            }

            $this->dispatchEvent(
                $paymentStatus,
                $order,
                $payment,
                $remoteId
            );
        } else {
            $orderComment =
                '[BM] Transaction ID: '.$remoteId
                .' | Amount: '.$formattedAmount.' '.$currency
                .' | Status: '.$paymentStatus.' [IGNORED]'
                .(!$shouldUpdateOrder ? ' Status SUCCESS/PENDING is in other transaction based on WebAPI.' : '');

            $order->addStatusToHistory($order->getStatus(), $orderComment);
        }

        $this->orderRepository->save($order);
        $this->sendConfirmationEmail->execute($order);

        return $orderPaymentState;
    }

    /**
     * @param $paymentStatus
     * @param  Order  $order
     * @param  SimpleXMLElement  $payment
     * @param  string  $remoteId
     * @return void
     */
    protected function dispatchEvent($paymentStatus, Order $order, SimpleXMLElement $payment, string $remoteId): void
    {
        $eventToCall = null;
        switch ($paymentStatus) {
            case Payment::PAYMENT_STATUS_FAILURE:
                $eventToCall = 'bluemedia_payment_failure';
                break;
            case Payment::PAYMENT_STATUS_PENDING:
                $eventToCall = 'bluemedia_payment_pending';
                break;
            case Payment::PAYMENT_STATUS_SUCCESS:
                $eventToCall = 'bluemedia_payment_success';
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
    }
}
