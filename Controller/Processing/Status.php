<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Model\PaymentFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use BlueMedia\BluePayment\Logger\Logger;

/**
 * Class Status
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Status extends Action
{
    /**
     * @var \BlueMedia\BluePayment\Model\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Status constructor.
     *
     * @param \Magento\Framework\App\Action\Context       $context
     * @param \BlueMedia\BluePayment\Logger\Logger        $logger
     * @param \BlueMedia\BluePayment\Model\PaymentFactory $paymentFactory
     */
    public function __construct(
        Context        $context,
        Logger         $logger,
        PaymentFactory $paymentFactory
    ) {
        $this->logger         = $logger;
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
            $params = $this->getRequest()->getParams();
            $this->logger->info('STATUS:' . __LINE__, ['params' => $params]);
            if (array_key_exists('transactions', $params)) {
                $paramTransactions  = $params['transactions'];
                $base64transactions = base64_decode($paramTransactions);
                $simpleXml          = simplexml_load_string($base64transactions);
                $this->logger->info('STATUS:' . __LINE__, ['simpleXmlTransactions' => json_encode($simpleXml)]);
                $this->paymentFactory->create()->processStatusPayment($simpleXml);
            }
        } catch (\Exception $e) {
            $this->logger->critical('BlueMedia: ' . $e->getMessage());
        }
    }
}
