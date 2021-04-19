<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\Payment;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Controller for returning user
 */
class Back extends Action
{
    /**
     * @var PageFactory
     */
    public $pageFactory;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var\Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var Onepage
     */
    public $onepage;

    /**
     * Back constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param OrderFactory $orderFactory
     * @param Onepage $onepage
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        OrderFactory $orderFactory,
        Onepage $onepage
    )
    {
        $this->helper = $helper;
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->onepage = $onepage;

        parent::__construct($context);
    }

    /**
     * Sprawdzenie danych po powrocie z bramki płatniczej
     *
     * @return Page|ResponseInterface
     * @throws Exception
     */
    public function execute()
    {
        $page = $this->pageFactory->create();

        /** @var \BlueMedia\BluePayment\Block\Processing\Back $block */
        $block = $page->getLayout()->getBlock('bluepayment.processing.back');

        /** @var array $params */
        $params = $this->getRequest()->getParams();

        $this->logger->info('BACK:' . __LINE__, ['params' => $params]);
        try {
            $orderId = $params['OrderID'];
            $hash = $params['Hash'];

            /** @var Order $order */
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $currency = strtolower($order->getOrderCurrencyCode());

            /** @var Order\Payment $payment */
            $payment = $order->getPayment();

            if (array_key_exists('Hash', $params)) {
                $serviceId = $this->scopeConfig->getValue(
                    'payment/bluepayment/' . $currency . '/service_id',
                    ScopeInterface::SCOPE_STORE
                );
                $sharedKey = $this->scopeConfig->getValue(
                    'payment/bluepayment/' . $currency . '/shared_key',
                    ScopeInterface::SCOPE_STORE
                );

                $hashData = [$serviceId, $orderId, $sharedKey];

                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $this->logger->info('BACK:' . __LINE__, [
                    'serviceId' => $serviceId,
                    'orderId' => $orderId,
                    'sharedKey' => $sharedKey,
                    'hashLocal' => $hashLocal
                ]);

                /** @var Session $session */
                $session = $this->onepage->getCheckout();
                $session
                    ->setLastRealOrderId($order->getRealOrderId())
                    ->setLastOrderId($order->getId())
                    ->setLastQuoteId($order->getQuoteId())
                    ->setLastSuccessQuoteId($order->getQuoteId())
                    ->setQuoteId($order->getQuoteId());

                if ($hash == $hashLocal) {
                    $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji poprawny');
                    $status = $this->getBluePaymentState($payment);

                    if ($status == Payment::PAYMENT_STATUS_SUCCESS) {
                        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                    } elseif ($status == Payment::PAYMENT_STATUS_FAILURE) {
                        return $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                    }

                    $block->addData([
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Hash' => $hash,

                        'order' => $order,
                        'status' => $this->getBluePaymentState($payment)
                    ]);
                } else {
                    $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji jest nieprawidłowy');

                    $block->addData([
                        'error' => true,
                        'message' => 'Klucz autoryzacji jest nieprawidłowy.'
                    ]);
                }
            } else {
                $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji nie istnieje');

                $block->addData([
                    'error' => true,
                    'message' => 'Klucz autoryzacji nie istnieje.'
                ]);
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e);

            $block->addData([
                'error' => true,
                'message' => 'Wystąpił błąd.'
            ]);
        }

        return $page;
    }

    /**
     * @param Order\Payment $payment
     *
     * @return string
     */
    public function getBluePaymentState($payment)
    {
        return $payment->getAdditionalInformation('bluepayment_state');
    }
}
