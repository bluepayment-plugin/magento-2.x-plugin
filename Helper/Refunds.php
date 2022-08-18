<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\RefundTransactionFactory;
use Exception;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Gateways
 */
class Refunds extends Data
{
    const DEFAULT_HASH_SEPARATOR = '|';

    /** @var RefundTransactionFactory */
    private $refundTransactionFactory;

    /** @var RefundTransactionRepositoryInterface */
    private $refundTransactionRepository;

    /** @var OrderFactory */
    private $orderFactory;

    /** @var TransactionBuilder */
    private $transactionBuilder;

    /**
     * Gateways constructor.
     *
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param Client $apiClient
     * @param Logger $logger
     * @param OrderFactory $orderFactory
     * @param RefundTransactionFactory $refundTransactionFactory
     * @param RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param TransactionBuilder $transactionBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Client $apiClient,
        Logger $logger,
        OrderFactory $orderFactory,
        RefundTransactionFactory $refundTransactionFactory,
        RefundTransactionRepositoryInterface $refundTransactionRepository,
        TransactionBuilder $transactionBuilder,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig,
            $apiClient,
            $logger,
            $storeManager
        );
        $this->refundTransactionFactory = $refundTransactionFactory;
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->orderFactory = $orderFactory;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * @param TransactionInterface|null $transaction
     * @param null $amount
     * @param bool $addTransaction
     * @return array
     * @throws EmptyRemoteIdException
     */
    public function makeRefund($transaction, $amount = null, $addTransaction = true)
    {
        if (null === $transaction || empty($transaction->getRemoteId())) {
            throw new EmptyRemoteIdException();
        }

        /** @var Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($transaction->getOrderId());
        $storeId = $order->getStoreId();

        $result = [
            'error' => true,
            'message' => __('Something went wrong. Please try again later.'),
        ];

        $refundAPIUrl = $this->getRefundUrl($storeId);

        $hashMethod = $this->getConfigValue('hash_algorithm', $storeId);
        $serviceId = $this->getConfigValue('service_id', $storeId, $order->getOrderCurrencyCode());
        $sharedKey = $this->getConfigValue('shared_key', $storeId, $order->getOrderCurrencyCode());
        $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);

        if ($amount === null) {
            // Full refund
            $amount = $order->getGrandTotal();
        }

        $refundAmount = $this->refundTransactionRepository->getTotalRefundAmountOnTransaction($transaction);
        $availableRefundAmount = $transaction->getAmount() - $refundAmount;
        if ($amount > $availableRefundAmount) {
            return [
                'error' => true,
                'message' => __(
                    'Passed refund amount is to high! Maximum amount of refund is %1.',
                    $this->formatAmount($availableRefundAmount)
                ),
            ];
        }

        $loadResult = $this->callRefundAPI(
            $hashMethod,
            $storeId,
            $serviceId,
            $messageId,
            $transaction->getRemoteId(),
            (float) $amount,
            $sharedKey,
            $refundAPIUrl
        );

        if (is_array($loadResult) && !isset($loadResult['statusCode']) && !empty($loadResult)) {
            $valuesForHash = [
                $loadResult['serviceID'],
                $loadResult['messageID'],
                $loadResult['remoteOutID'],
                $sharedKey,
            ];
            $hashSeparator = $this->getConfigValue('hash_separator', $storeId) ?: self::DEFAULT_HASH_SEPARATOR;

            if ($loadResult['hash'] != hash($hashMethod, implode($hashSeparator, $valuesForHash))) {
                $result = [
                    'error' => true,
                    'message' => __('Invalid response hash!'),
                ];
            } else {
                if ($addTransaction) {
                    $this->processResponse($loadResult, $amount, $transaction, $order);
                }

                $result = [
                    'success' => true,
                ];
            }
        } elseif (is_array($loadResult) && isset($loadResult['statusCode'])) {
            // For error
            $errorText = $loadResult['description'];

            if (strpos($errorText, " - ") !== false) {
                [$errorCode, $errorText] = explode(" - ", $loadResult['description'] ?: '');
            }

            $result = [
                'error' => true,
                'message' => $errorText,
            ];
        }

        return $result;
    }

    /**
     * @param string $name
     * @param int $storeId
     * @param string $currency
     *
     * @return mixed
     */
    public function getConfigValue($name, $storeId, $currency = null)
    {
        if ($currency) {
            return $this->scopeConfig->getValue(
                'payment/bluepayment/' . strtolower($currency) . '/' . $name,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->scopeConfig->getValue(
            'payment/bluepayment/' . $name,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param  int  $storeId
     *
     * @return mixed
     */
    public function getRefundUrl(int $storeId)
    {
        if ($this->getConfigValue('test_mode', $storeId)) {
            return $this->getConfigValue('test_address_refunds_url', $storeId);
        }

        return $this->getConfigValue('prod_address_refunds_url', $storeId);
    }

    /**
     * @param  float  $amount
     *
     * @return string
     */
    public function formatAmount(float $amount): string
    {
        return sprintf('%.2f', $amount);
    }

    /**
     * @param  string  $hashMethod
     * @param  int  $storeId
     * @param  string  $serviceId
     * @param  string  $messageId
     * @param  string  $remoteId
     * @param  float  $amount
     * @param  string  $hashKey
     * @param  string  $refundAPIUrl
     *
     * @return bool|array
     */
    public function callRefundAPI(
        string $hashMethod,
        $storeId,
        string $serviceId,
        string $messageId,
        string $remoteId,
        float $amount,
        string $hashKey,
        string $refundAPIUrl
    ) {
        $data = [
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'RemoteID' => $remoteId,
        ];
        if (!empty($amount)) {
            $data['Amount'] = number_format($amount, 2, '.', '');
        }
        $hashSeparator = $this->getConfigValue('hash_separator', $storeId) ?: self::DEFAULT_HASH_SEPARATOR;
        $data['Hash'] = hash($hashMethod, implode($hashSeparator, array_merge(array_values($data), [$hashKey])));

        $this->logger->info('REFUNDS:' . __LINE__, ['data' => $data]);

        try {
            $response = (array)$this->apiClient->call($refundAPIUrl, $data);

            $this->logger->info('REFUNDS:' . __LINE__, ['response' => $response]);

            return $response;
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());

            return false;
        }
    }

    /**
     * @param  array  $loadResult
     * @param  float  $amount
     * @param  TransactionInterface  $transaction
     * @param  Order  $order
     *
     * @return void
     * @throws Exception
     */
    public function processResponse(
        array $loadResult,
        float $amount,
        TransactionInterface $transaction,
        Order $order
    ): void {
        $this->saveRefundTransaction($loadResult, $amount, $transaction, $order);
        $this->saveTransaction($loadResult, $amount, $transaction, $order);
        $this->updateOrderOnRefund($loadResult, $amount, $transaction, $order);
    }

    /**
     * @param  array  $loadResult
     * @param  float  $amount
     * @param TransactionInterface $transaction
     * @param Order $order
     *
     * @return void
     */
    public function saveRefundTransaction(
        array $loadResult,
        float $amount,
        TransactionInterface $transaction,
        Order $order
    ): void {
        /** @var RefundTransactionInterface $refund */
        $refund = $this->refundTransactionFactory->create();

        $refund
            ->setAmount((float)$amount)
            ->setCurrency($transaction->getCurrency())
            ->setOrderId($transaction->getOrderId())
            ->setRemoteId($transaction->getRemoteId())
            ->setRemoteOutId($loadResult['remoteOutID'])
            ->setIsPartial($amount < $transaction->getAmount());

        $this->refundTransactionRepository->save($refund);
    }

    /**
     * @param  array  $loadResult
     * @param  float  $amount
     * @param  TransactionInterface  $transaction
     * @param  Order  $order
     *
     * @return int|false
     * @throws Exception
     */
    public function saveTransaction(
        array $loadResult,
        float $amount,
        TransactionInterface $transaction,
        Order $order
    ) {
        $parent = $transaction;

        /** @var Order\Payment|null */
        $payment = $order->getPayment();

        if ($payment !== null) {
            $payment->setLastTransId($loadResult['remoteOutID']);
            $payment->setTransactionId($loadResult['remoteOutID']);
            $payment->setAdditionalInformation([
                Transaction::RAW_DETAILS => (array)$loadResult
            ]);
            $payment->setParentTransactionId($parent->getRemoteId());

            // Prepare transaction
            /** @var Transaction $transaction */
            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($loadResult['remoteOutID'])
                ->setAdditionalInformation([
                    Transaction::RAW_DETAILS => (array)$loadResult
                ])
                ->setFailSafe(true)
                ->build(Transaction::TYPE_REFUND);

            // Save payment, transaction and order
            $payment->save();
            $order->save();
            $transaction->save();

            return $transaction->getTransactionId();
        }

        return false;
    }

    /**
     * @param  array  $loadResult
     * @param  float  $amount
     * @param  TransactionInterface  $transaction
     * @param  Order  $order
     *
     * @return void
     * @throws Exception
     */
    public function updateOrderOnRefund(array $loadResult, float $amount, TransactionInterface $transaction, Order $order): void
    {
        if ($amount < $transaction->getAmount()) {
            $status = $this->getConfigValue('status_partial_refund', $order->getStoreId());
        } else {
            $status = $this->getConfigValue('status_full_refund', $order->getStoreId());
        }

        if ($status) {
            $order->setStatus($status);

            $historyStatusComment = __(
                'Refunded %1. Transaction ID: "%2"',
                $this->formatAmount($amount) . ' ' . $transaction->getCurrency(),
                $loadResult['remoteOutID']
            );

            $order
                ->addStatusToHistory($status, $historyStatusComment, true)
                ->save();
        }
    }
}
