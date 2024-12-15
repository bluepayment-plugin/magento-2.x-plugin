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
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
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

    /**
     * @var RefundTransactionFactory
     */
    private $refundTransactionFactory;

    /**
     * @var RefundTransactionRepositoryInterface
     */
    private $refundTransactionRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Gateways constructor.
     *
     * @param  Context  $context
     * @param  LayoutFactory  $layoutFactory
     * @param  Factory  $paymentMethodFactory
     * @param  Emulation  $appEmulation
     * @param  Config  $paymentConfig
     * @param  Initial  $initialConfig
     * @param  Client  $apiClient
     * @param  Logger  $logger
     * @param  OrderFactory  $orderFactory
     * @param  RefundTransactionFactory  $refundTransactionFactory
     * @param  RefundTransactionRepositoryInterface  $refundTransactionRepository
     * @param  StoreManagerInterface  $storeManager
     * @param  OrderRepositoryInterface  $orderRepository
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
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository
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
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param TransactionInterface|null $transaction
     * @param null $amount
     * @return array
     * @throws EmptyRemoteIdException
     */
    public function makeRefund($transaction, $amount = null)
    {
        if (null === $transaction || empty($transaction->getRemoteId())) {
            throw new EmptyRemoteIdException();
        }

        /** @var Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($transaction->getOrderId());
        $storeId = (int) $order->getStoreId();

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
            $sharedKey
        );

        if (is_array($loadResult) && !isset($loadResult['statusCode']) && !empty($loadResult)) {
            $valuesForHash = [
                $loadResult['serviceID'],
                $loadResult['messageID'],
                $sharedKey,
            ];
            $hashSeparator = $this->getConfigValue('hash_separator', $storeId) ?: self::DEFAULT_HASH_SEPARATOR;

            if ($loadResult['hash'] != hash($hashMethod, implode($hashSeparator, $valuesForHash))) {
                return [
                    'error' => true,
                    'message' => __('Invalid response hash!'),
                ];
            } else {
                $this->saveRefundTransaction($loadResult, $amount, $transaction);

                $order
                    ->addCommentToStatusHistory(__(
                        'Refund requested. Message ID: "%1"',
                        $loadResult['messageID']
                    ));
                $this->orderRepository->save($order);

                return [
                    'success' => true,
                ];
            }
        } elseif (is_array($loadResult) && isset($loadResult['statusCode'])) {
            // For error
            $error = __('Error code: %1 (%2)', $loadResult['statusCode'], $loadResult['name'] ?? 'n/a');

            return [
                'error' => true,
                'message' => $error,
            ];
        }

        return [
            'error' => true,
            'message' => __('Something went wrong. Please try again later.'),
        ];
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
            return $this->getConfigValue('refund_url_test', $storeId);
        }

        return $this->getConfigValue('refund_url_prod', $storeId);
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
     * @return bool|array
     */
    public function callRefundAPI(
        string $hashMethod,
        int $storeId,
        string $serviceId,
        string $messageId,
        string $remoteId,
        float $amount,
        string $hashKey
    ) {
        $refundAPIUrl = $this->getRefundUrl($storeId);

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
        $this->saveRefundTransaction($loadResult, $amount, $transaction);
        // $this->saveTransaction($loadResult, $amount, $transaction, $order);
        // $this->updateOrderOnRefund($loadResult, $amount, $transaction, $order);
    }

    /**
     * @param  array  $loadResult
     * @param  float  $amount
     * @param TransactionInterface $transaction
     *
     * @return void
     */
    public function saveRefundTransaction(
        array $loadResult,
        float $amount,
        TransactionInterface $transaction
    ): void {
        /** @var RefundTransactionInterface $refund */
        $refund = $this->refundTransactionFactory->create();

        $refund
            ->setAmount($amount)
            ->setCurrency($transaction->getCurrency())
            ->setOrderId($transaction->getOrderId())
            ->setRemoteId($transaction->getRemoteId())
            ->setMessageId($loadResult['messageID'])
            ->setIsPartial($amount < $transaction->getAmount());

        $this->refundTransactionRepository->save($refund);
    }
}
