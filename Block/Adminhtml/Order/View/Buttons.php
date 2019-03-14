<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Order\View;

use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Buttons
 * @package BlueMedia\BluePayment\Block\Adminhtml\Order\View
 */
class Buttons extends View
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \BlueMedia\BluePayment\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var \BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface
     */
    private $refundTransactionRepository;

    /**
     * Buttons constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context                           $context
     * @param \Magento\Framework\Registry                                     $registry
     * @param \Magento\Sales\Model\Config                                     $salesConfig
     * @param \Magento\Sales\Helper\Reorder                                   $reorderHelper
     * @param \Magento\Sales\Model\OrderFactory                               $orderFactory
     * @param \BlueMedia\BluePayment\Api\TransactionRepositoryInterface       $transactionRepository
     * @param \BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param array                                                           $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $salesConfig,
        Reorder $reorderHelper,
        OrderFactory $orderFactory,
        TransactionRepositoryInterface $transactionRepository,
        RefundTransactionRepositoryInterface $refundTransactionRepository,
        array $data = []
    ) {
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
        $this->orderFactory = $orderFactory;
        $this->transactionRepository = $transactionRepository;
        $this->refundTransactionRepository = $refundTransactionRepository;
    }

    /**
     * Add button to Shopping Cart Management etc.
     *
     * @return $this
     */
    public function addButtons()
    {
        if ($this->isCreateButtonRequired()) {
            $this->getToolbar()->addChild('bluemedia_return', Button::class, [
                'label' => __('Return BM'),
                'onclick' => 'BlueMedia.BluePayment.showPopup();'
            ]);
        }

        return $this;
    }

    /**
     * Check if button has to be displayed
     *
     * @return boolean
     */
    protected function isCreateButtonRequired()
    {
        $parentBlock = $this->getParentBlock();

        return $parentBlock instanceof Template
            && $parentBlock->getOrderId()
            && $this->canShowButton($parentBlock->getOrderId());
    }

    /**
     * @param $orderId
     *
     * @return bool
     */
    protected function canShowButton($orderId)
    {
        /** @var Order $order */
        $order = $this->orderFactory->create()->load((int)$orderId);

        return null !== $order->getPayment()->getLastTransId()
            && $this->transactionRepository->orderHasSuccessTransaction($order)
            && !$this->hasOrderFullRefund($order)
            && $order->getPayment()->getMethod() === Payment::METHOD_CODE;
    }

    /**
     * @param $order
     *
     * @return bool
     */
    protected function hasOrderFullRefund($order)
    {
        $paymentTransaction = $this->transactionRepository->getSuccessTransactionFromOrder($order);
        $summaryRefund = $this->refundTransactionRepository->getTotalRefundAmountOnTransaction($paymentTransaction);

        if ((float)$summaryRefund == (float)$paymentTransaction->getAmount()) {
            return true;
        }

        return false;
    }
}
