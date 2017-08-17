<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Model\PaymentFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use BlueMedia\BluePayment\Logger\Logger;

/**
 * Class Create
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Create extends Action
{
    /**
     * @var \BlueMedia\BluePayment\Model\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * Create constructor.
     *
     * @param Context              $context
     * @param OrderSender          $orderSender
     * @param PaymentFactory       $paymentFactory
     * @param OrderFactory         $orderFactory
     * @param Session              $session
     * @param Logger               $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context              $context,
        OrderSender          $orderSender,
        PaymentFactory       $paymentFactory,
        OrderFactory         $orderFactory,
        Session              $session,
        Logger               $logger,
        ScopeConfigInterface $scopeConfig

    ) {
        $this->paymentFactory = $paymentFactory;
        $this->scopeConfig    = $scopeConfig;
        $this->logger         = $logger;
        $this->session        = $session;
        $this->orderFactory   = $orderFactory;
        $this->orderSender    = $orderSender;
        parent::__construct($context);
    }

    /**
     * Rozpoczęcie procesu płatności
     */
    public function execute()
    {
        try {
            $payment       = $this->paymentFactory->create();
            $session       = $this->_getCheckout();
            $quoteModuleId = $session->getBluePaymentQuoteId();
            $this->logger->info('CREATE:' . __LINE__, ['quoteModuleId' => $quoteModuleId]);
            $session->setQuoteId($quoteModuleId);
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();
            $this->logger->info('CREATE:' . __LINE__, ['sessionLastRealOrderSessionId' => $sessionLastRealOrderSessionId]);
            $order = $this->orderFactory->create()->loadByIncrementId($sessionLastRealOrderSessionId);

            if (!$order->getId()) {
                $this->logger->info('CREATE:' . __LINE__, ['Zamówienie bez identyfikatora']);
            }
            $gatewayId = (int)$this->getRequest()->getParam('gateway_id', 0);

            $statusWaitingPayment = $this->scopeConfig->getValue("payment/bluepayment/status_waiting_payment");
            if ($statusWaitingPayment != '') {
                /**
                 * @var \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollection
                 */
                $statusCollection        = $this->_objectManager->create(Collection::class);
                $orderStatusWaitingState = Order::STATE_NEW;
                foreach ($statusCollection->joinStates() as $status) {
                    /** @var \Magento\Sales\Model\Order\Status $status */
                    if ($status->getStatus() == $statusWaitingPayment) {
                        $orderStatusWaitingState = $status->getState();
                    }
                }

                $this->logger->info('CREATE:' . __LINE__, ['orderStatusWaitingState' => $orderStatusWaitingState]);
                $order->setState($orderStatusWaitingState)->setStatus($statusWaitingPayment)->save();
                $this->logger->info('CREATE:' . __LINE__, ['statusWaitingPayment' => $statusWaitingPayment]);
            } else {
                $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT)->save();
                $this->logger->info('CREATE:' . __LINE__, ['setStatePendingPayment AND setStatusPendingPayment']);
            }

            if ($order->getCanSendNewEmailFlag()) {
                $this->logger->info('CREATE:' . __LINE__, ['getCanSendNewEmailFlag']);
                try {
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }

            $url = $this->_url->getUrl(
                $payment->getUrlGateway()
                . '?'
                . http_build_query($payment->getFormRedirectFields($order, $gatewayId))
            );

            $this->logger->info('CREATE:' . __LINE__, ['redirectUrl' => $url]);
            $this->getResponse()->setRedirect($url);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->session;
    }
}
