<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Order\View;

use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Order details buttons block
 */
class Buttons extends View
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var RefundTransactionRepositoryInterface
     */
    private $refundTransactionRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Buttons constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Config $salesConfig
     * @param Reorder $reorderHelper
     * @param OrderFactory $orderFactory
     * @param TransactionRepositoryInterface $transactionRepository
     * @param RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $salesConfig,
        Reorder $reorderHelper,
        OrderFactory $orderFactory,
        TransactionRepositoryInterface $transactionRepository,
        RefundTransactionRepositoryInterface $refundTransactionRepository,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
        $this->orderFactory = $orderFactory;
        $this->transactionRepository = $transactionRepository;
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Add button to Shopping Cart Management etc.
     *
     * @return $this
     */
    public function addButtons()
    {
        if ($this->isCreateButtonRequired()) {
            /** @var AbstractBlock $toolbar */
            $toolbar = $this->getToolbar();

            $toolbar->addChild('bluemedia_return', Button::class, [
                'label' => __('Refund BM'),
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
    public function isCreateButtonRequired()
    {
        /** @var Popup $parentBlock */
        $parentBlock = $this->getParentBlock();

        return $parentBlock instanceof Template
            && $parentBlock->getOrderId()
            && $this->canShowButton($parentBlock->getOrderId());
    }

    /**
     * @param int|bool $orderId
     *
     * @return bool
     */
    public function canShowButton($orderId)
    {
        /** @var Order $order */
        $order = $this->orderFactory->create()->load((int)$orderId);

        $showManualRefund = $this->scopeConfig->getValue(
            'payment/bluepayment/show_manual_refund',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        return $showManualRefund
            && null !== $order->getPayment()->getLastTransId()
            && $this->transactionRepository->orderHasSuccessTransaction($order)
            && !$this->hasOrderFullRefund($order)
            && $order->getPayment()->getMethod() === Payment::METHOD_CODE;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function hasOrderFullRefund($order)
    {
        $paymentTransaction = $this->transactionRepository->getSuccessTransactionFromOrder($order);
        $summaryRefund = $this->refundTransactionRepository->getTotalRefundAmountOnTransaction($paymentTransaction);

        if ((float)$summaryRefund == (float)$paymentTransaction->getAmount()) {
            return true;
        }

        return false;
    }
}
