<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Refunds;

use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use BlueMedia\BluePayment\Helper\Refunds as RefundsHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

/**
 * Class Edit
 */
class Place extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \BlueMedia\BluePayment\Helper\Refunds
     */
    private $refunds;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \BlueMedia\BluePayment\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * Ajax constructor.
     *
     * @param Context                       $context
     * @param \BlueMedia\BluePayment\Helper\Refunds                     $refunds
     * @param \Magento\Sales\Model\OrderRepository                      $orderRepository
     * @param \BlueMedia\BluePayment\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory          $resultJsonFactory
     */
    public function __construct(
        Context $context,
        RefundsHelper $refunds,
        OrderRepository $orderRepository,
        TransactionRepositoryInterface $transactionRepository,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->refunds           = $refunds;
        $this->orderRepository   = $orderRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $resultObject = new DataObject();

        $amount = null;
        if ($this->getRequest()->getParam('is_partial', false)) {
            $amount = $this->getRequest()->getParam('partial_amount', null);
        }

        try {
            $order = $this->orderRepository->get((int)$this->getRequest()->getParam('order_id', 0));
            $transaction = $this->transactionRepository->getSuccessTransactionFromOrder($order);
            $resultObject->setData($this->refunds->makeRefund($transaction, $amount));
        } catch (InputException $e) {
            $resultObject->setData([
                'error' => true,
                'message' => __('Order ID is mandatory.')
            ]);
        } catch (EmptyRemoteIdException $e) {
            $resultObject->setData([
                'error' => true,
                'message' => __('There is no succeeded payment transaction.')
            ]);
        } catch (NoSuchEntityException $e) {
            $resultObject->setData([
                'error' => true,
                'message' => __('There is no such order.')
            ]);
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($resultObject->getData());
    }

    /**
     * GatewaysFactory access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BlueMedia_BluePayment::refunds');
    }
}
