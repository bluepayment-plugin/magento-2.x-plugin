<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use Magento\Framework\App\Action\Context;

class Status extends \Magento\Framework\App\Action\Action
{
    protected $paymentFactory;

    protected $logger;

    public function __construct(
        Context $context,
        \Psr\Log\LoggerInterface $logger,
        \BlueMedia\BluePayment\Model\PaymentFactory $paymentFactory
    ) {
        $this->logger = $logger;
        $this->paymentFactory = $paymentFactory;
        parent::__construct($context);
    }

    /**
     * ITN - sprawdzenie statusu natychmiastowego powiadomienia o transakcji
     *
     * @throws \Exception
     */
    public function execute()
    {
        try {
            // Parametry z request
            $params = $this->getRequest()->getParams();

            // Jeśli parametr 'transactions' istnieje w tablicy $params,
            // wykonaj operacje zmiany statusu płatności zamówienia
            if (array_key_exists('transactions', $params)) {
                // Zakodowany parametr transakcje
                $paramTransactions = $params['transactions'];

                // Odkodowanie parametru transakcji
                $base64transactions = base64_decode($paramTransactions);
                // Odczytanie parametrów z xml-a
                $simpleXml = simplexml_load_string($base64transactions);

                $this->paymentFactory->create()->processStatusPayment($simpleXml);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
