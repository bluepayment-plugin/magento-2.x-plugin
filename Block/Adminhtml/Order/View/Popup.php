<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Order\View;

use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class Popup
 * @package BlueMedia\BluePayment\Block\Adminhtml\Order\View
 */
class Popup extends Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \BlueMedia\BluePayment\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var \BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface
     */
    private $refundTransactionRepository;

    /**
     * Popup constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                         $context
     * @param \Magento\Framework\Registry                                     $coreRegistry
     * @param \BlueMedia\BluePayment\Api\TransactionRepositoryInterface       $transactionRepository
     * @param \BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param array                                                           $data
     */
    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        TransactionRepositoryInterface $transactionRepository,
        RefundTransactionRepositoryInterface $refundTransactionRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry                = $coreRegistry;
        $this->transactionRepository       = $transactionRepository;
        $this->refundTransactionRepository = $refundTransactionRepository;
    }

    /**
     * @return float|null|string
     */
    public function getAmountToReturn()
    {
        $amountToReturn = 0.00;
        $transaction    = $this->getCurrentOrderTransaction();
        if ($transaction !== null) {
            $refundAmount = $this->refundTransactionRepository->getTotalRefundAmountOnTransaction($transaction);
            if ($transaction instanceof TransactionInterface) {
                $amountToReturn = (float)($transaction->getAmount() - $refundAmount);
            }
        }

        return $amountToReturn;
    }

    /**
     * @return bool
     */
    public function canShowFullRefund()
    {
        $canShow     = false;
        $transaction = $this->getCurrentOrderTransaction();
        if ($transaction !== null) {
            $canShow = (float)$transaction->getAmount() === (float)$this->getAmountToReturn();
        }

        return $canShow;
    }

    /**
     * @return int|null|string
     */
    public function getOrderId()
    {
        $order = $this->getCurrentOrder();

        return $order instanceof OrderInterface ? $order->getEntityId() : '';
    }

    /**
     * @return null|OrderInterface
     */
    protected function getCurrentOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return \BlueMedia\BluePayment\Api\Data\TransactionInterface|null
     */
    protected function getCurrentOrderTransaction()
    {
        $order       = $this->getCurrentOrder();
        return $this->transactionRepository->getSuccessTransactionFromOrder($order);
    }
}