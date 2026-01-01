<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Service;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundStatusUpdaterInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface as BlueTransactionRepositoryInterface;
use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ConfigProviderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface as TransactionInterfaceAlias;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface as MagentoTransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;

class RefundStatusUpdaterService implements RefundStatusUpdaterInterface
{
    protected const METHOD_REFUND = 'TRANSACTION_REFUND';

    /**
     * @var RefundTransactionRepositoryInterface
     */
    protected $refundTransactionRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ConfigProviderFactory
     */
    protected $configFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Client g
     */
    protected $apiClient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TransactionBuilder
     */
    protected $transactionBuilder;

    /**
     * @var BlueTransactionRepositoryInterface
     */
    protected $blueTransactionRepository;

    /**
     * @var MagentoTransactionRepositoryInterface
     */
    protected $magentoTransactionRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * @var NotifierInterface
     */
    protected $notifier;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        RefundTransactionRepositoryInterface $refundTransactionRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConfigProviderFactory $configFactory,
        Data $helper,
        Client $apiClient,
        Logger $logger,
        TransactionBuilder $transactionBuilder,
        BlueTransactionRepositoryInterface $blueTransactionRepository,
        MagentoTransactionRepositoryInterface $magentoTransactionRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        NotifierInterface $notifier,
        UrlInterface $urlBuilder
    ) {
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configFactory = $configFactory;
        $this->helper = $helper;
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->transactionBuilder = $transactionBuilder;
        $this->blueTransactionRepository = $blueTransactionRepository;
        $this->magentoTransactionRepository = $magentoTransactionRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->notifier = $notifier;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function updateRefundStatuses(): void
    {
        $transactions = $this->refundTransactionRepository->getPendingRefundTransactions();

        /** @var RefundTransactionInterface $transaction */
        foreach ($transactions->getItems() as $transaction) {
            $this->processItem($transaction);
        }
    }

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    protected function processItem(RefundTransactionInterface $item): void
    {
        // Process the item
        $order = $this->getOrderByIncrementId($item->getOrderId());

        if (!$order) {
            throw new NotFoundException(__('Order not found'));
        }

        $response = (array) $this->callApi($item, $order);

        if (!isset($response['Status'])) {
            $this->logger->error('Error while fetching refund status', [
                'transaction' => $item->getMessageId(),
                'response' => $response
            ]);
        }

        $status = $response['Status'];

        if ($status === 'DONE') {
            // Update transaction status
            $remoteOutID = $response['remoteOutID'];

            $parent = $this->blueTransactionRepository->getSuccessTransactionFromOrder($order);

            $this->saveTransaction($response, $parent, $order);
            $this->updateOrderOnRefund($response, $item->getAmount(), $parent, $order);
            $item->setRemoteOutId($remoteOutID);
            $this->refundTransactionRepository->save($item);
        } elseif ($status === 'ERROR') {
            // Update transaction status
            $this->logger->error('Error while processing refund transaction status', [
                'transaction' => $item->getMessageId(),
                'response' => $response
            ]);

            $url = $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId()]);
            $this->addAdminNotification(
                __('Refund error')->render(),
                __('Refund failed. Try again or contact Autopay technical support. (Order: %1, Message ID: %2)', $item->getOrderId(), $item->getMessageId())->render(),
                $url
            );
        }
    }

    protected function callApi(RefundTransactionInterface $transaction, OrderInterface $order)
    {
        $config = $this->configFactory->create();

        $storeId = (int) $order->getStoreId();
        $currency = (string) $order->getOrderCurrencyCode();

        $apiUrl = $config->getRefundStatusUrl($storeId);
        $data = [
            'ServiceID' => $config->getServiceId($currency, $storeId),
            'MessageID' => $transaction->getMessageId(),
            'Method' => self::METHOD_REFUND,
        ];

        $hashSeparator = $config->getHashSeparator($storeId) ?? '|';
        $hashMethod = $config->getHashAlgorithm($storeId) ?? 'sha256';
        $sharedKey = $config->getSharedKey($currency, $storeId);

        $data['Hash'] = hash(
            $hashMethod,
            implode($hashSeparator, array_merge(array_values($data), [$sharedKey]))
        );

        return $this->apiClient->call($apiUrl, $data);
    }

    /**
     * @param  array  $response
     * @param  TransactionInterface  $parent
     * @param  Order  $order
     *
     * @return int|false
     */
    protected function saveTransaction(
        array $response,
        TransactionInterface $parent,
        OrderInterface $order
    ) {
        /** @var Order\Payment|null */
        $payment = $order->getPayment();

        if ($payment !== null) {
            $payment->setLastTransId($response['remoteOutID']);
            $payment->setTransactionId($response['remoteOutID']);
            $payment->setAdditionalInformation([
                Transaction::RAW_DETAILS => (array)$response
            ]);
            $payment->setParentTransactionId($parent->getRemoteId());

            // Prepare transaction
            /** @var Transaction $transaction */
            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($response['remoteOutID'])
                ->setAdditionalInformation([
                    Transaction::RAW_DETAILS => (array)$response
                ])
                ->setFailSafe(true)
                ->build(TransactionInterfaceAlias::TYPE_REFUND);

            // Save payment, transaction and order
            $this->orderPaymentRepository->save($payment);
            $this->magentoTransactionRepository->save($transaction);
            $this->orderRepository->save($order);

            return $transaction->getTransactionId();
        }

        return false;
    }

    /**
     * @param  array  $response
     * @param  float  $amount
     * @param  TransactionInterface  $transaction
     * @param  Order  $order
     *
     * @return void
     * @throws \Exception
     */
    protected function updateOrderOnRefund(
        array $response,
        float $amount,
        TransactionInterface $transaction,
        OrderInterface $order
    ): void {
        $storeId = (int) $order->getStoreId();
        $config = $this->configFactory->create();

        $status = ($amount < $transaction->getAmount())
            ? $config->getStatusPartialRefund($storeId)
            : $config->getStatusFullRefund($storeId);

        $historyStatusComment = __(
            'Refunded %1. Transaction ID: "%2"',
            $this->formatAmount($amount) . ' ' . $transaction->getCurrency(),
            $response['remoteOutID']
        );

        if ($status) {
            $order->setStatus($status);
            $order->addStatusToHistory($status, $historyStatusComment, true);
        } else {
            $order->addCommentToStatusHistory($historyStatusComment);
        }

        $this->orderRepository->save($order);
    }

    /**
     * Get stored order.
     *
     * @param string $incrementId
     * @return ?OrderInterface
     */
    protected function getOrderByIncrementId(string $incrementId): ?OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::INCREMENT_ID, $incrementId)
            ->create();

        $orders = $this->orderRepository
            ->getList($searchCriteria)
            ->getItems();

        /** @var OrderInterface $order */
        return array_pop($orders);
    }

    /**
     * @param  float  $amount
     *
     * @return string
     */
    protected function formatAmount(float $amount): string
    {
        return sprintf('%.2f', $amount);
    }

    /**
     * Add admin notification for unsuccessful refund.
     *
     * @param  string  $title
     * @param  string  $description
     * @param  ?string  $url
     */
    private function addAdminNotification(string $title, string $description, ?string $url = null): void
    {
        try {
            $this->notifier->addMajor(
                $title,
                $description,
                $url
            );

            $this->logger->info('RefundStatusUpdaterService:' . __LINE__ . ' Admin notification added', [
                'title' => $title,
                'description' => $description
            ]);
        } catch (\Exception $e) {
            $this->logger->info('RefundStatusUpdaterService:' . __LINE__ . ' Admin notification failed', [
                'title' => $title,
                'description' => $description
            ]);
        }
    }

}
