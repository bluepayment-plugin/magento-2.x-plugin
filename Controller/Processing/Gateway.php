<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Gateway
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Gateway extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * Gateway constructor.
     *
     * @param \Magento\Framework\App\Action\Context               $context
     * @param \Magento\Framework\Controller\Result\JsonFactory    $resultJsonFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\OrderFactory                   $orderFactory
     * @param \Magento\Checkout\Model\Session                     $session
     */
    public function __construct(
        Context      $context,
        JsonFactory  $resultJsonFactory,
        OrderSender  $orderSender,
        OrderFactory $orderFactory,
        Session      $session
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session           = $session;
        $this->orderFactory      = $orderFactory;
        $this->orderSender       = $orderSender;

        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);

        parent::__construct($context);
    }

    /**
     * Used only to set in session selected gateway ID
     *
     * @return $this
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
                $this->_logger->info([__METHOD__ => __LINE__, 'error' => $e->getMessage()]);
                $response = ['success' => false, 'session_gateway_id' => 0];
            }

            return $result->setData($response);
        }

        try {
            $session->setBluepaymentGatewayId(0);
            $response = ['success' => true, 'session_gateway_id' => 0];
        } catch (\Exception $e) {
            $this->_logger->info([__METHOD__ => __LINE__, 'error' => $e->getMessage()]);
            $response = ['success' => false, 'session_gateway_id' => 0];
        }

        return $result->setData($response);
    }

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckout()
    {
        return $this->session;
    }
}
