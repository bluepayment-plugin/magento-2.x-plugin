<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\GetTransactionLifetime;
use BlueMedia\BluePayment\Model\Payment;
use BlueMedia\BluePayment\Model\PaymentFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Store\Model\ScopeInterface;

/**
 * Create payment (BM transaction) controller
 */
class ContinuePayment extends Action
{
    /** @var PaymentFactory */
    public $paymentFactory;

    /** @var OrderFactory */
    public $orderFactory;

    /** @var Session */
    public $session;

    /** @var Logger */
    public $logger;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var OrderSender */
    public $orderSender;

    /** @var Data */
    public $helper;

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var Collection  */
    public $collection;

    /** @var Curl */
    public $curl;

    /** @var CollectionFactory */
    public $gatewayFactory;

    /** @var GetTransactionLifetime */
    public $getTransactionLifetime;

    /**
     * Create constructor.
     *
     * @param Context $context
     * @param OrderSender $orderSender
     * @param PaymentFactory $paymentFactory
     * @param OrderFactory $orderFactory
     * @param Session $session
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param JsonFactory $resultJsonFactory
     * @param Collection $collection
     * @param Curl $curl
     * @param CollectionFactory $gatewayFactory
     * @param GetTransactionLifetime $getTransactionLifetime
     */
    public function __construct(
        Context $context,
        OrderSender $orderSender,
        PaymentFactory $paymentFactory,
        OrderFactory $orderFactory,
        Session $session,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        JsonFactory $resultJsonFactory,
        Collection $collection,
        Curl $curl,
        CollectionFactory $gatewayFactory,
        GetTransactionLifetime $getTransactionLifetime
    ) {
        $this->paymentFactory    = $paymentFactory;
        $this->scopeConfig       = $scopeConfig;
        $this->logger            = $logger;
        $this->session           = $session;
        $this->orderFactory      = $orderFactory;
        $this->orderSender       = $orderSender;
        $this->helper            = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->collection        = $collection;
        $this->curl              = $curl;
        $this->gatewayFactory    = $gatewayFactory;
        $this->getTransactionLifetime = $getTransactionLifetime;

        parent::__construct($context);
    }

    /**
     * Kontynuacja płatności
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        try {
            $payment = $this->paymentFactory->create();

            $orderId = $this->getRequest()->getParam('order_id', 0);
            $hash = $this->getRequest()->getParam('hash');

            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $orderPayment = $order->getPayment();

            $currency = strtolower($order->getOrderCurrencyCode());
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

            $hashLocal = $this->helper->generateAndReturnHash([
                $serviceId,
                $order->getIncrementId(),
                $sharedKey
            ]);

            $this->logger->info('CONTINUE: ' . __LINE__, [
                'serviceId' => $serviceId,
                'orderId' => $order->getIncrementId(),
                'sharedKey' => $sharedKey,
                'hash' => $hash,
                'hashLocal' => $hashLocal,
            ]);

            if ($hash !== $hashLocal) {
                $this->messageManager->addErrorMessage(__('Invalid hash for order.'));
                return $this->_redirect('/');
            }

            $lifetime = $this->getTransactionLifetime->getForOrder($order);

            if ($lifetime === false) {
                $this->messageManager->addErrorMessage(__('Transaction is expired. Place order again.'));
                return $this->_redirect('/');
            }

            $existingUrl = (string) $orderPayment->getAdditionalInformation('bluepayment_redirect_url');
            if ($existingUrl) {
                // Already generated
                return $this->_redirect($existingUrl);
            }

            $backUrl = $payment->getAdditionalInformation('back_url');
            $this->logger->info('CONTINUE: ' . __LINE__, [
                'orderId' => $orderId,
                'backUrl' => $backUrl,
            ]);
            $params = $payment->getFormRedirectFields($order);

            $this->logger->info('CONTINUE: ' . __LINE__, $params);
            $returnUrl = $payment->createPaymentLink($order, $params);

            return $this->_redirect($returnUrl);
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        $this->messageManager->addErrorMessage(__('An error occurred while generating the transaction.'));
        return $this->_redirect('/');
    }
}
