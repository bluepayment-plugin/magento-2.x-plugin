<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

/**
 * Class Gateway
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Gateway extends Action
{
    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var OrderFactory */
    public $orderFactory;

    /** @var Session */
    public $session;

    /** @var Logger */
    public $logger;

    /** @var OrderSender */
    public $orderSender;

    /**
     * Gateway constructor.
     *
     * @param Context               $context
     * @param JsonFactory    $resultJsonFactory
     * @param OrderSender $orderSender
     * @param OrderFactory                   $orderFactory
     * @param Session                     $session
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderSender $orderSender,
        OrderFactory $orderFactory,
        Session $session
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session           = $session;
        $this->orderFactory      = $orderFactory;
        $this->orderSender       = $orderSender;

        $writer = new Stream(BP . '/var/log/bluemedia.log');
        $this->logger = new Logger();
        $this->logger->addWriter($writer);

        parent::__construct($context);
    }

    /**
     * Used only to set in session selected gateway ID
     *
     * @return Json
     */
    public function execute()
    {
        $result  = $this->resultJsonFactory->create();
        $session = $this->getCheckout();
        if ($this->getRequest()->isAjax()) {
            $data = $this->getRequest()->getParams();

            if (isset($data['gateway_id'])) {
                $gatewayId = (int)$data['gateway_id'];
            } else {
                $gatewayId = 0;
            }

            try {
                $session->setBluepaymentGatewayId($gatewayId);
                $response = ['success' => true, 'session_gateway_id' => $session->getBluepaymentGatewayId()];
            } catch (\Exception $e) {
                $this->logger->info('Error', [__METHOD__ => __LINE__, 'error' => $e->getMessage()]);
                $response = ['success' => false, 'session_gateway_id' => 0];
            }

            return $result->setData($response);
        }

        try {
            $session->setBluepaymentGatewayId(0);
            $response = ['success' => true, 'session_gateway_id' => 0];
        } catch (\Exception $e) {
            $this->logger->info('Error', [__METHOD__ => __LINE__, 'error' => $e->getMessage()]);
            $response = ['success' => false, 'session_gateway_id' => 0];
        }

        return $result->setData($response);
    }

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return Session
     */
    public function getCheckout()
    {
        return $this->session;
    }
}
