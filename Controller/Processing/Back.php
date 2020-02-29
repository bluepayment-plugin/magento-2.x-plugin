<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Back
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Back extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $pageFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \BlueMedia\BluePayment\Helper\Data
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
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Framework\View\Result\PageFactory         $pageFactory
     * @param Logger|\Psr\Log\LoggerInterface                    $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \BlueMedia\BluePayment\Helper\Data                 $helper
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        OrderFactory $orderFactory,
        Onepage $onepage
    ) {
        $this->helper       = $helper;
        $this->pageFactory  = $pageFactory;
        $this->scopeConfig  = $scopeConfig;
        $this->logger       = $logger;
        $this->orderFactory = $orderFactory;
        $this->onepage = $onepage;

        parent::__construct($context);
    }

    /**
     * Sprawdzenie danych po powrocie z bramki płatniczej
     *
     * @throws \Exception
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        /** @var \BlueMedia\BluePayment\Block\Processing\Back $block */
        $block = $page->getLayout()->getBlock('bluepayment.processing.back');

        $params = $this->getRequest()->getParams();

        $this->logger->info('BACK:' . __LINE__, ['params' => $params]);
        try {
            $params     = $this->getRequest()->getParams();
            $orderId    = $params['OrderID'];
            $hash       = $params['Hash'];
            $order      = $this->orderFactory->create()->loadByIncrementId($orderId);
            $currency   = strtolower($order->getOrderCurrencyCode());
            $payment    = $order->getPayment();

            if (array_key_exists('Hash', $params)) {
                $serviceId = $this->scopeConfig->getValue("payment/bluepayment/".$currency."/service_id");
                $sharedKey = $this->scopeConfig->getValue("payment/bluepayment/".$currency."/shared_key");

                $hashData  = [$serviceId, $orderId, $sharedKey];

                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $this->logger->info('BACK:' . __LINE__, [
                    'serviceId' => $serviceId,
                    'orderId' => $orderId,
                    'sharedKey' => $sharedKey,
                    'hashLocal' => $hashLocal
                ]);

                /** @var \Magento\Checkout\Model\Session $session */
                $session = $this->onepage->getCheckout();
                $session->setQuoteId($orderId);
                $session->setLastSuccessQuoteId($orderId);

                if ($hash == $hashLocal) {
                    $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji poprawny');

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
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e);

            $block->addData([
                'error' => true,
                'message' => 'Wystąpił błąd.'
            ]);
        }

        return $page;
    }

    public function getBluePaymentState($payment)
    {
        return $payment->getAdditionalInformation('bluepayment_state');
    }
}
