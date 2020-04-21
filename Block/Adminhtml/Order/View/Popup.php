<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Order\View;

use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;

class Popup extends Template
{
    /** @var Registry */
    private $coreRegistry;

    /** @var TransactionRepositoryInterface */
    private $transactionRepository;

    /** @var RefundTransactionRepositoryInterface */
    private $refundTransactionRepository;

    /**
     * Popup constructor.
     *
     * @param Context                               $context
     * @param Registry                              $coreRegistry
     * @param TransactionRepositoryInterface        $transactionRepository
     * @param RefundTransactionRepositoryInterface  $refundTransactionRepository
     * @param array                                 $data
     */
    public function __construct(
        Context $context,
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
     * @return bool|int
     */
    public function getOrderId()
    {
        $order = $this->getCurrentOrder();

        return $order instanceof OrderInterface ? $order->getEntityId() : false;
    }

    /**
     * @return null|OrderInterface
     */
    public function getCurrentOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return TransactionInterface|null
     */
    public function getCurrentOrderTransaction()
    {
        $order       = $this->getCurrentOrder();
        return $this->transactionRepository->getSuccessTransactionFromOrder($order);
    }
}
