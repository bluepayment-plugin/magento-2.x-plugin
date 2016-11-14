<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use Magento\Framework\App\Action\Context;

class Back extends \Magento\Framework\App\Action\Action
{
    protected $logger;

    protected $scopeConfig;

    protected $helper;

    public function __construct(
        Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \BlueMedia\BluePayment\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Sprawdzenie danych po powrocie z bramki płatniczej
     *
     * @throws \Exception
     */
    public function execute() {
        try {
            // Parametry z request
            $params = $this->getRequest()->getParams();

            if (array_key_exists('Hash', $params)) {
                // Id serwisu partnera
                $serviceId = $this->scopeConfig->getValue("payment/bluepayment/service_id");

                // Id zamówienia
                $orderId = $params['OrderID'];

                // Hash
                $hash = $params['Hash'];

                // Klucz współdzielony
                $sharedKey = $this->scopeConfig->getValue("payment/bluepayment/shared_key");

                // Tablica danych z których wygenerować hash
                $hashData = array($serviceId, $orderId, $sharedKey);

                // Klucz hash
                $hashLocal = $this->helper->generateAndReturnHash($hashData);

                // Sprawdzenie zgodności hash-y oraz reszty parametrów
                if ($hash == $hashLocal) {
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                } else {
                    $this->logger->info('Klucz autoryzacji transakcji jest nieprawidłowy');
                    $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                }
            } else {
                $this->logger->info('Klucz autoryzacji transakcji nie istnieje');
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->critical($e);
            parent::_redirect('checkout/cart');
        }
    }
}
