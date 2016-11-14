<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use Magento\Framework\App\Action\Context;

class Create extends \Magento\Framework\App\Action\Action
{
    protected $paymentFactory;

    protected $orderFactory;

    protected $session;

    protected $logger;

    protected $scopeConfig;

    protected $orderSender;

    public function __construct(
        Context $context,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \BlueMedia\BluePayment\Model\PaymentFactory $paymentFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $session,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->session = $session;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        parent::__construct($context);
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

    /**
     * Rozpoczęcie procesu płatności
     */
    public function execute()
    {
        try {
            // Sesja
            $payment =  $this->paymentFactory->create();
            $session = $this->_getCheckout();

            // Id kolejki modułu w sesji
            $quoteModuleId = $session->getBluePaymentQuoteId();

            // Zapis do sesji
            $session->setQuoteId($quoteModuleId);

            // Id ostatniego zamówienia z sesji
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();

            // Obiekt zamówienia
            $order = $this->orderFactory->create()->loadByIncrementId($sessionLastRealOrderSessionId);

            // Jeśli zamówienie nie posiada numeru id, wyświetl wyjątek
            if (!$order->getId()) {
                $this->logger->info('Zamówienie bez identyfikatora');
            }

            $statusWaitingPayment = $this->scopeConfig->getValue("payment/bluepayment/status_waiting_payment");

            // Jeśli ustawiono własny status
            if ($statusWaitingPayment != '') {
                /**
                 * @var \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollection
                 */
                $statusCollection = $this->_objectManager->create('Magento\Sales\Model\ResourceModel\Order\Status\Collection');
                $orderStatusWaitingState = \Magento\Sales\Model\Order::STATE_NEW;
                foreach($statusCollection->joinStates() as $status) {
                    /** @var \Magento\Sales\Model\Order\Status $status */
                    if ($status->getStatus() == $statusWaitingPayment) {
                        $orderStatusWaitingState = $status->getState();
                    }
                }

                $order->setState($orderStatusWaitingState)
                    ->setStatus($statusWaitingPayment)
                    ->save();
            } else {
                $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                    ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                    ->save();
            }

            if ($order->getCanSendNewEmailFlag()) {
                try {
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }

            $url = $this->_url->getUrl(
                $payment->getUrlGateway() . '?' . http_build_query($payment->getFormRedirectFields($order))
            );

            $this->getResponse()->setRedirect($url);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            parent::_redirect('checkout/cart');
        }
    }
}
