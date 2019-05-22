<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\PaymentFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Class Status
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Status extends Action
{
    /** @var PaymentFactory */
    public $paymentFactory;

    /** @var Logger */
    public $logger;

    /**
     * Status constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param PaymentFactory $paymentFactory
     */
    public function __construct(
        Context $context,
        Logger $logger,
        PaymentFactory $paymentFactory
    ) {
        $this->logger         = $logger;
        $this->paymentFactory = $paymentFactory;

        parent::__construct($context);

        // CsrfAwareAction Magento2.3 compatibility
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();

            if ($request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
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
            } elseif (array_key_exists('recurring', $params)) {
                $paramRecurring = $params['recurring'];
                $base64recurring = base64_decode($paramRecurring);
                $simpleXml = simplexml_load_string($base64recurring);
                $this->logger->info('STATUS:' . __LINE__, ['simpleXmlRecurring' => json_encode($simpleXml)]);
                $this->paymentFactory->create()->processRecurring($simpleXml);
            }
        } catch (\Exception $e) {
            $this->logger->critical('BlueMedia: ' . $e->getMessage());
        }
    }
}
