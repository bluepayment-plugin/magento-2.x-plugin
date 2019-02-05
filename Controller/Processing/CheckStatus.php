<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\PaymentFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use BlueMedia\BluePayment\Logger\Logger;

/**
 * Class Create
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class CheckStatus extends Action
{
    const BLIK_CODE_LENGTH = 6;

    /**
     * @var \BlueMedia\BluePayment\Model\PaymentFactory
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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /** @var Data */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Create constructor.
     *
     * @param Context              $context
     * @param OrderSender          $orderSender
     * @param PaymentFactory       $paymentFactory
     * @param OrderFactory         $orderFactory
     * @param Session              $session
     * @param Logger               $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data                 $helper
     */
    public function __construct(
        Context              $context,
        OrderSender          $orderSender,
        PaymentFactory       $paymentFactory,
        OrderFactory         $orderFactory,
        Session              $session,
        Logger               $logger,
        ScopeConfigInterface $scopeConfig,
        Data                 $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->paymentFactory    = $paymentFactory;
        $this->scopeConfig       = $scopeConfig;
        $this->logger            = $logger;
        $this->session           = $session;
        $this->orderFactory      = $orderFactory;
        $this->orderSender       = $orderSender;
        $this->helper            = $helper;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Rozpoczęcie procesu płatności
     */
    public function execute()
    {
        try {
            $payment       = $this->paymentFactory->create();
            $session       = $this->_getCheckout();
            $quoteModuleId = $session->getBluePaymentQuoteId();

            $this->logger->info('CREATE:' . __LINE__, ['quoteModuleId' => $quoteModuleId]);
            $session->setQuoteId($quoteModuleId);
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();

            $this->logger->info('CREATE:' . __LINE__, [
                'sessionLastRealOrderSessionId' => $sessionLastRealOrderSessionId
            ]);

            $blikGateway = $this->scopeConfig->getValue('payment/bluepayment/blik_gateway');

            $order = $this->orderFactory->create()->loadByIncrementId($sessionLastRealOrderSessionId);

            $currency       = $order->getOrderCurrencyCode();
            $serviceId      = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
            $sharedKey      = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");
            $orderId        = $order->getRealOrderId();

            if (!$order->getId()) {
                $this->logger->info('CREATE:' . __LINE__, ['Zamówienie bez identyfikatora']);
            }

            $authorizationCode = (int)$this->getRequest()->getParam('code', 0);
            $this->logger->info('CREATE:' . __LINE__, ['authorizationCode' => $authorizationCode]);

            if ($this->validateBlikCode($authorizationCode)) {
                $params = $payment->getFormRedirectFields($order, $blikGateway, true, $authorizationCode);
                $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);

                $responseParams = $this->sendRequestBlik($payment->getUrlGateway(), $params);
                $this->logger->info('CREATE:' . __LINE__, ['responseParams' => $responseParams]);

                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData($responseParams);

                return $resultJson;

//                $hashData  = [$serviceId, $orderId, $sharedKey];
//                $this->logger->info('CREATE:' . __LINE__, ['hashData' => $hashData]);
//
//                $hash = $this->helper->generateAndReturnHash($hashData);
//                $this->logger->info('CREATE:' . __LINE__, ['hash' => $hash]);
//
//                $params = [
//                    'ServiceID' => $serviceId,
//                    'OrderID' => $orderId,
//                    'GatewayID' => $blikGateway,
//                    'hash' => $hash,
//                    'confirmation' => $responseParams['confirmation'],
//                    'paymentStatus' => $responseParams['paymentStatus']
//                ];
//                $result = $this->prepareBlikJsonResponse($payment->getUrlGateway(), $authorizationCode, $params);
//
//                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
//                $resultJson = $this->resultJsonFactory->create();
//                $resultJson->setData($result);
//                return $resultJson;
            }

            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData([
                'status' => false
            ]);
            return $resultJson;

        } catch (\Exception $e) {
            $this->logger->critical($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->session;
    }

    /**
     * @param string $gatewayUrl
     * @param array  $params
     * @return array
     */
    private function prepareIframeJsonResponse($gatewayUrl, $redirectHash, $params)
    {
        $params['ScreenType'] = self::IFRAME_GATEWAY_ID;
        $params['GatewayID'] = (string) $params['GatewayID'];

        return [
            'gateway_url' => $gatewayUrl,
            'redirectHash' => $redirectHash,
            'params' => $params,
        ];
    }

    /**
     * @param string $gatewayUrl
     * @param string $authorizationCode
     * @param array  $params
     * @return array
     */
    private function prepareBlikJsonResponse($gatewayUrl, $authorizationCode, $params)
    {
        $params['GatewayID'] = (string) $params['GatewayID'];
        $params['AuthorizationCode'] = $authorizationCode;

        return [
            'gateway_url' => $gatewayUrl,
            'params' => $params,
        ];
    }

    /**
     * @param string $code
     * @return bool
     */
    private function validateBlikCode($code)
    {
        return (false === empty($code) && self::BLIK_CODE_LENGTH === strlen($code))? true : false;
    }

    /**
     * @param $urlGateway
     * @param $params
     */
    private function sendRequestBlik($urlGateway, $params)
    {
        $fields = (is_array($params)) ? http_build_query($params) : $params;
        $curl = curl_init($urlGateway);
        if (array_key_exists('ClientHash', $params)){
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('BmHeader: pay-bm'));
        } else{
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('BmHeader: pay-bm-continue-transaction-url'));
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $curlResponse = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = curl_getinfo($curl);
        curl_close($curl);

        $xml = simplexml_load_string($curlResponse);
        $paymentStatus = (string) $xml->paymentStatus;
        $orderID = (string) $xml->orderID;
        $remoteID = (string) $xml->remoteID;
        $confirmation = (string) $xml->confirmation;
        $hash = (string) $xml->hash;

        $responseParams = [
            'orderID' => $orderID,
            'remoteID' => $remoteID,
            'confirmation' => $confirmation,
            'paymentStatus' => $paymentStatus
        ];

        return $responseParams;
    }

}
