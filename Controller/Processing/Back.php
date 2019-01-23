<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use BlueMedia\BluePayment\Logger\Logger;

/**
 * Class Back
 *
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
     * @param Logger|\Psr\Log\LoggerInterface                    $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \BlueMedia\BluePayment\Helper\Data                 $helper
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     */
    public function __construct(
        Context              $context,
        Logger               $logger,
        ScopeConfigInterface $scopeConfig,
        Data                 $helper,
        OrderFactory         $orderFactory
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
        $this->logger->info('BACK:' . __LINE__, ['params' => $this->getRequest()->getParams()]);
        try {
            $params     = $this->getRequest()->getParams();
            $orderId    = $params['OrderID'];
            $hash       = $params['Hash'];
            $status     = isset($params['Status']) ? $params['Status'] : null;
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);

            $currency = $order->getOrderCurrencyCode();
            $payment = $order->getPayment();

            if (array_key_exists('Hash', $params)) {
                $serviceId = $this->scopeConfig->getValue("payment/bluepayment_".strtolower($currency)."/service_id");
                $sharedKey = $this->scopeConfig->getValue("payment/bluepayment_".strtolower($currency)."/shared_key");

                $hashData  = [$serviceId, $orderId, $sharedKey];

                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $this->logger->info('BACK:' . __LINE__, [
                    'serviceId' => $serviceId,
                    'orderId' => $orderId,
                    'sharedKey' => $sharedKey,
                    'hashLocal' => $hashLocal
                ]);

                // @ToDo
                /** @var \Magento\Checkout\Model\Session $session */
                $session = $this->_objectManager->get(\Magento\Checkout\Model\Type\Onepage::class)->getCheckout();
                $session->setQuoteId($orderId);
                $session->setLastSuccessQuoteId($orderId);

                if (
                    $hash == $hashLocal
                    && ($status == 'SUCCESS' || $this->getBluePaymentState($payment) == Payment::PAYMENT_STATUS_SUCCESS)
                ) {
                    $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji poprawny');
                    $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                } else {
                    $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji jest nieprawidłowy');
                    $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                }
            } else {
                $this->logger->info('BACK:' . __LINE__ . ' Klucz autoryzacji transakcji nie istnieje');
                $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e);
            $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
        }
    }

    protected function getBluePaymentState($payment)
    {
        return $payment->getAdditionalInformation('bluepayment_state');
    }
}
