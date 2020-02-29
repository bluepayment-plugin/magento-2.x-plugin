<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Create
 *
 * @package BlueMedia\BluePayment\Controller\Processing
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
        OrderFactory $orderFactory
    ) {
        $this->orderSender = $orderSender;
        $this->session = $session;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;

        parent::__construct($context);
    }

    /**
     * Rozpoczęcie procesu płatności
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $orderId = $this->getRequest()->getParam('OrderID', false);

        if ($orderId) {
            // Check status after back.

            /** @var Order $order */
            $order      = $this->orderFactory->create()->loadByIncrementId($orderId);
            $hash       = $this->getRequest()->getParam('Hash');

            $currency   = strtolower($order->getOrderCurrencyCode());
            $serviceId = $this->scopeConfig->getValue("payment/bluepayment/".$currency."/service_id");
            $sharedKey = $this->scopeConfig->getValue("payment/bluepayment/".$currency."/shared_key");

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
            $payment = $order->getPayment();
            $status = $payment->getAdditionalInformation('bluepayment_state');

            // Get ServiceID and SharedKey for order currency
            $serviceId = $this->scopeConfig->getValue("payment/bluepayment/" . strtolower($currency) . "/service_id");
            $sharedKey = $this->scopeConfig->getValue("payment/bluepayment/" . strtolower($currency) . "/shared_key");

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
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        parent::_redirect('checkout/cart');
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
