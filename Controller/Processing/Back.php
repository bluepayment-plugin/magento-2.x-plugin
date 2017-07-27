<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Back
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Back extends Action
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \BlueMedia\BluePayment\Helper\Data
     */
    protected $helper;

    /**
     *
     * @var\Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * Back constructor.
     *
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \BlueMedia\BluePayment\Helper\Data                 $helper
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        OrderFactory $orderFactory
    ) {
        $this->helper       = $helper;
        $this->scopeConfig  = $scopeConfig;
        $this->logger       = $logger;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    /**
     * Sprawdzenie danych po powrocie z bramki płatniczej
     *
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams();
            if (array_key_exists('Hash', $params)) {
                $serviceId = $this->scopeConfig->getValue("payment/bluepayment/service_id");
                $orderId   = $params['OrderID'];
                $hash      = $params['Hash'];
                $sharedKey = $this->scopeConfig->getValue("payment/bluepayment/shared_key");
                $hashData  = [$serviceId, $orderId, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                if ($hash == $hashLocal && $this->isOrderPaid($orderId)) {
                    $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                } else {
                    $this->logger->info(__('Invalid authorisation key'));
                    $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                }
            } else {
                $this->logger->info(__('Authorisation key does not exists'));
                $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->critical($e);
            $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
        }
    }

    /**
     * @param $orderId
     *
     * @return boolean
     */
    protected function isOrderPaid($orderId)
    {

        try {
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            return false;
        }
        $orderStatus  = $order->getStatus();
        $acceptStatus = $this->scopeConfig->getValue('payment/bluepayment/status_accept_payment');

        return $orderStatus == $acceptStatus;
    }

}
