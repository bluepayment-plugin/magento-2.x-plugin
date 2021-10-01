<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\Payment;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Create
 */
class Blik extends Action
{
    /** @var Session */
    public $session;

    /** @var LoggerInterface */
    public $logger;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var OrderSender */
    public $orderSender;

    /** @var Data */
    public $helper;

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var OrderFactory */
    public $orderFactory;

    /** @var CollectionFactory */
    public $orderCollectionFactory;

    /**
     * Create constructor.
     *
     * @param Context                           $context
     * @param OrderSender                       $orderSender
     * @param Session                           $session
     * @param Logger                            $logger
     * @param ScopeConfigInterface              $scopeConfig
     * @param Data                              $helper
     * @param JsonFactory                       $resultJsonFactory
     * @param OrderFactory                      $orderFactory
     */
    public function __construct(
        Context $context,
        OrderSender $orderSender,
        Session $session,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        JsonFactory $resultJsonFactory,
        OrderFactory $orderFactory,
        CollectionFactory $orderCollectionFactory
    ) {
        $this->orderSender = $orderSender;
        $this->session = $session;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct($context);
    }

    /**
     * Rozpoczęcie procesu płatności
     *
     * @return Json|ResponseInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $orderId = $this->getRequest()->getParam('OrderID', false);

        if ($orderId) {
            if (substr($orderId, 0, strlen(Payment::QUOTE_PREFIX)) === Payment::QUOTE_PREFIX) {
                $quoteId = substr($orderId, strlen(Payment::QUOTE_PREFIX));

                $order = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('quote_id', $quoteId)
                    ->getFirstItem();
            } else {
                // Check status after back.
                /** @var Order $order */
                $order      = $this->orderFactory->create()->loadByIncrementId($orderId);
            }

            $hash       = $this->getRequest()->getParam('Hash');

            $currency   = strtolower($order->getOrderCurrencyCode());
            $serviceId = $this->scopeConfig->getValue(
                'payment/bluepayment/' . $currency . '/service_id',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );
            $sharedKey = $this->scopeConfig->getValue(
                'payment/bluepayment/' . $currency . '/shared_key',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            $hashData  = [$serviceId, $orderId, $sharedKey];
            $hashLocal = $this->helper->generateAndReturnHash($hashData);

            if ($hash !== $hashLocal) {
                // Check hash for being sure.

                $resultJson->setData([
                    'error' => true,
                    'message' => 'Invalid hash.',
                    'local' => $hashLocal,
                    'data' => $hashData
                ]);

                return $resultJson;
            }
        } else {
            // Check status on cart.
            $order = $this->session->getLastRealOrder();
            $orderId = $order->getIncrementId();
        }

        if (!$order) {
            $resultJson->setData([
                'error' => true,
                'message' => 'Nie znaleziono takiego zamówienia.',
            ]);

            return $resultJson;
        }

        try {
            // Get last order data.
            $currency = $order->getOrderCurrencyCode();

            // Get payment info
            /** @var Order\Payment $payment */
            $payment = $order->getPayment();
            $status = $payment->getAdditionalInformation('bluepayment_state');

            // Get ServiceID and SharedKey for order currency
            $serviceId = $this->scopeConfig->getValue(
                'payment/bluepayment/' . strtolower($currency) . '/service_id',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );
            $sharedKey = $this->scopeConfig->getValue(
                'payment/bluepayment/' . strtolower($currency) . '/shared_key',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            // Generate hash
            $hashData = [$serviceId, $orderId, $sharedKey];
            $hash = $this->helper->generateAndReturnHash($hashData);

            $resultJson->setData([
                'Status' => $status,
                'ServiceID' => $serviceId,
                'OrderID' => $orderId,
                'hash' => $hash,
            ]);

            return $resultJson;
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return parent::_redirect('checkout/cart');
    }

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckout()
    {
        return $this->session;
    }
}
