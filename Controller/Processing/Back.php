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
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
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
     * @var OrderFactory
     */
    public $orderFactory;

    /**
     * @var Onepage
     */
    public $onepage;

    /** @var CollectionFactory */
    public $orderCollectionFactory;

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
        Onepage $onepage,
        CollectionFactory $orderCollectionFactory
    )
    {
        $this->helper = $helper;
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->onepage = $onepage;
        $this->orderCollectionFactory = $orderCollectionFactory;

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
            $isMultishipping = false;

            // Multishipping
            if (substr($orderId, 0, strlen(Payment::QUOTE_PREFIX)) === Payment::QUOTE_PREFIX) {
                $quoteId = substr($orderId, strlen(Payment::QUOTE_PREFIX));
                $isMultishipping = true;

                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('quote_id', $quoteId)
                    ->load();

                $order = $orders->getFirstItem();
            } else {
                $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            }

            /** @var Order $order */
            $currency = strtolower($order->getOrderCurrencyCode() ?? '');

            /** @var Order\Payment $payment */
            $payment = $order->getPayment();

            if (array_key_exists('Hash', $params)) {
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

                $hashData = [$serviceId, $orderId, $sharedKey];

                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $this->logger->info('BACK:' . __LINE__, [
                    'serviceId' => $serviceId,
                    'orderId' => $orderId,
                    'sharedKey' => $sharedKey,
                    'hashLocal' => $hashLocal
                ]);
                if ($hash == $hashLocal) {
                    $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji poprawny');
                    $status = $this->getBluePaymentState($payment);

                    $block->addData([
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Hash' => $hash,
                        'order' => $order,
                        'status' => $status
                    ]);

                    if ($isMultishipping) {
                        $block->setData('orders', $orders);

                        if ($status == Payment::PAYMENT_STATUS_SUCCESS) {
                            return $this->_redirect('multishipping/checkout/success', ['_secure' => true]);
                        } elseif ($status == Payment::PAYMENT_STATUS_FAILURE) {
                            return $this->_redirect('multishipping/checkout/results', ['_secure' => true]);
                        }
                    } else {
                        /** @var Session $session */
                        $session = $this->onepage->getCheckout();
                        $session
                            ->setLastRealOrderId($order->getRealOrderId())
                            ->setLastOrderId($order->getId())
                            ->setLastQuoteId($order->getQuoteId())
                            ->setLastSuccessQuoteId($order->getQuoteId())
                            ->setQuoteId($order->getQuoteId());

                        if ($status == Payment::PAYMENT_STATUS_SUCCESS) {
                            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                        } elseif ($status == Payment::PAYMENT_STATUS_FAILURE) {
                            return $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                        }
                    }
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
