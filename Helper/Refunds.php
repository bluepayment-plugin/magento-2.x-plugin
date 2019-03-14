<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use BlueMedia\BluePayment\Model\RefundTransactionFactory;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\App\Emulation;

/**
 * Class Gateways
 * @package BlueMedia\BluePayment\Helper
 */
class Refunds extends Data
{
    const DEFAULT_HASH_SEPARATOR = '|';

    /**
     * @var \BlueMedia\BluePayment\Model\RefundTransactionFactory
     */
    private $refundTransactionFactory;

    /**
     * @var \BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface
     */
    private $refundTransactionRepository;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * Gateways constructor.
     *
     * @param \Magento\Framework\App\Helper\Context                           $context
     * @param \Magento\Framework\View\LayoutFactory                           $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory                           $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation                              $appEmulation
     * @param \Magento\Payment\Model\Config                                   $paymentConfig
     * @param \Magento\Framework\App\Config\Initial                           $initialConfig
     * @param \BlueMedia\BluePayment\Api\Client                               $apiClient
     * @param \Magento\Sales\Model\OrderFactory                               $orderFactory
     * @param \BlueMedia\BluePayment\Model\RefundTransactionFactory           $refundTransactionFactory
     * @param \BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface $refundTransactionRepository
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Client $apiClient,
        OrderFactory $orderFactory,
        RefundTransactionFactory $refundTransactionFactory,
        RefundTransactionRepositoryInterface $refundTransactionRepository
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig,
            $apiClient
        );
        $this->refundTransactionFactory    = $refundTransactionFactory;
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->orderFactory                = $orderFactory;
    }

    /**
     * @param \BlueMedia\BluePayment\Api\Data\TransactionInterface $transaction
     * @param null                                                 $amount
     *
     * @return array
     * @throws \BlueMedia\BluePayment\Exception\EmptyRemoteIdException
     */
    public function makeRefund($transaction, $amount = null)
    {
        if (null === $transaction || empty($transaction->getRemoteId())) {
            throw new EmptyRemoteIdException();
        }

        $result       = [
            'error' => true,
            'message' => __('Something went wrong. Please try again later.')
        ];
        $hashMethod   = $this->getConfigValue('hash_algorithm');
        $refundAPIUrl = $this->getRefundUrl();

        $order     = $this->orderFactory->create()->loadByIncrementId($transaction->getOrderId());
        $serviceId = $this->getConfigValue('service_id', $order->getOrderCurrencyCode());
        $sharedKey = $this->getConfigValue('shared_key', $order->getOrderCurrencyCode());
        $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);

        $refundAmount          = $this->refundTransactionRepository->getTotalRefundAmountOnTransaction($transaction);
        $availableRefundAmount = $transaction->getAmount() - $refundAmount;
        if ($amount > ($availableRefundAmount)) {
            return [
                'error' => true,
                'message' => __(
                    'Passed refund amount is to high! Maximum amount of refund is %1.',
                    $this->formatAmount($availableRefundAmount)
                )
            ];
        }

        $loadResult = $this->callRefundAPI(
            $hashMethod,
            $serviceId,
            $messageId,
            $transaction->getRemoteId(),
            $amount,
            $sharedKey,
            $refundAPIUrl
        );

        if (is_array($loadResult) && !isset($loadResult['statusCode']) && !empty($loadResult)) {
            $valuesForHash = [
                $loadResult['serviceID'],
                $loadResult['messageID'],
                $loadResult['remoteOutID'],
                $sharedKey
            ];
            $hashSeparator = $this->getConfigValue('hash_separator') ?? self::DEFAULT_HASH_SEPARATOR;
            if ($loadResult['hash'] != hash($hashMethod, implode($hashSeparator, $valuesForHash))) {
                $result = [
                    'error' => true,
                    'message' => __('Invalid response hash!')
                ];
            } else {
                $this->processResponse($loadResult, $amount, $transaction);
                $result = [
                    'success' => true
                ];
            }
        }

        return $result;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function getConfigValue($name, $currency = null)
    {
        if ($currency) {
            return $this->scopeConfig->getValue('payment/bluepayment/'.strtolower($currency).'/'.$name);
        }

        return $this->scopeConfig->getValue('payment/bluepayment/'.$name);
    }

    /**
     * @return mixed
     */
    protected function getRefundUrl()
    {
        if ($this->getConfigValue('test_mode')) {
            return $this->getConfigValue('test_address_refunds_url');
        }

        return $this->getConfigValue('prod_address_refunds_url');
    }

    /**
     * @param $hashMethod
     * @param $serviceId
     * @param $messageId
     * @param $remoteId
     * @param $amount
     * @param $hashKey
     * @param $refundAPIUrl
     *
     * @return bool|array
     */
    protected function callRefundAPI($hashMethod, $serviceId, $messageId, $remoteId, $amount, $hashKey, $refundAPIUrl)
    {
        $data = [
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'RemoteID' => $remoteId
        ];
        if (!empty($amount)) {
            $data['Amount'] = number_format((float)$amount, 2, '.', '');
        }
        $hashSeparator = $this->getConfigValue('hash_separator') ?? self::DEFAULT_HASH_SEPARATOR;
        $data['Hash']  = hash($hashMethod, implode($hashSeparator, array_merge(array_values($data), [$hashKey])));

        try {
            return (array)$this->apiClient->call($refundAPIUrl, $data);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());

            return false;
        }
    }

    /**
     * @param array                                                $loadResult
     * @param float                                                $amount
     * @param \BlueMedia\BluePayment\Api\Data\TransactionInterface $transaction
     *
     * @return void
     */
    protected function processResponse($loadResult, $amount, $transaction)
    {
        $this->saveRefundTransaction($loadResult, $amount, $transaction);
        $this->updateOrderOnRefund($loadResult, $amount, $transaction);
    }

    /**
     * @param $loadResult
     * @param $amount
     * @param $transaction
     */
    protected function saveRefundTransaction($loadResult, $amount, $transaction)
    {
        /** @var \BlueMedia\BluePayment\Api\Data\RefundTransactionInterface $refund */
        $refund = $this->refundTransactionFactory->create();
        $refund->setAmount((float)$amount)->setCurrency($transaction->getCurrency())->setOrderId(
            $transaction->getOrderId()
        )->setRemoteId($transaction->getRemoteId())->setRemoteOutId($loadResult['remoteOutID'])->setIsPartial(
            $amount < $transaction->getAmount()
        );

        $this->refundTransactionRepository->save($refund);
    }

    /**
     * @param $loadResult
     * @param $amount
     * @param $transaction
     */
    protected function updateOrderOnRefund($loadResult, $amount, $transaction)
    {
        if ($amount < $transaction->getAmount()) {
            $status = $this->getConfigValue('status_partial_refund');
        } else {
            $status = $this->getConfigValue('status_full_refund');
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order                = $this->orderFactory->create()->loadByIncrementId($transaction->getOrderId());
        $historyStatusComment = __(
            'Authorized amount of %1. Transaction ID: "%2"',
            $this->formatAmount($amount) . ' ' . $transaction->getCurrency(),
            $loadResult['remoteOutID']
        );
        $order->setStatus($status)->addStatusToHistory($status, $historyStatusComment, true)->save();
    }

    /**
     * @param $amount
     *
     * @return string
     */
    protected function formatAmount($amount)
    {
        return sprintf('%.2f', $amount);
    }
}