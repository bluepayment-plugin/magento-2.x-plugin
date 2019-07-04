<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class BackBlik
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class BackBlik extends Action
{
    /** @var LoggerInterface */
    public $logger;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var Data */
    public $helper;

    /** @var OrderFactory */
    public $orderFactory;

    /**
     * @var Onepage
     */
    public $onepage;

    /**
     * Back constructor.
     *
     * @param Context              $context
     * @param Logger|LoggerInterface                    $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data                 $helper
     * @param OrderFactory                  $orderFactory
     * @param Onepage $onepage
     */
    public function __construct(
        Context $context,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        OrderFactory $orderFactory,
        Onepage $onepage
    ) {
        $this->helper       = $helper;
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
        $this->logger->info('BackBlik:' . __LINE__, ['params' => $this->getRequest()->getParams()]);
        try {
            $params = $this->getRequest()->getParams();
            $orderId    = $params['OrderID'];
            $hash       = $params['Hash'];

            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $currency = $order->getOrderCurrencyCode();

            if (array_key_exists('Hash', $params)) {
                $serviceId = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
                $sharedKey = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");

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
                    $this->logger->info('BackBlik:' . __LINE__ . ' Klucz autoryzacji transakcji poprawny');

                    if ($params['paymentStatus'] == 'FAILURE') {
                        $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                    }
                    $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                } else {
                    $this->logger->info('BackBlik:' . __LINE__ . ' Klucz autoryzacji transakcji jest nieprawidłowy');
                    $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                }
            } else {
                $this->logger->info('BackBlik:' . __LINE__ . ' Klucz autoryzacji transakcji nie istnieje');
                $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e);
            $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
        }
    }
}
