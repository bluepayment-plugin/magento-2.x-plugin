<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Payment;
use BlueMedia\BluePayment\Model\PaymentFactory;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;

/**
 * Create payment (BM transaction) controller
 */
class Create extends Action
{
    const IFRAME_GATEWAY_ID = 'IFRAME';
    const BLIK_CODE_LENGTH = 6;

    /** @var PaymentFactory */
    public $paymentFactory;

    /** @var OrderFactory */
    public $orderFactory;

    /** @var Session */
    public $session;

    /** @var Logger */
    public $logger;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var OrderSender */
    public $orderSender;

    /** @var Data */
    public $helper;

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var Collection  */
    public $collection;

    /** @var Curl */
    public $curl;

    /**
     * Create constructor.
     *
     * @param Context $context
     * @param OrderSender $orderSender
     * @param PaymentFactory $paymentFactory
     * @param OrderFactory $orderFactory
     * @param Session $session
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param JsonFactory $resultJsonFactory
     * @param Collection $collection
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        OrderSender $orderSender,
        PaymentFactory $paymentFactory,
        OrderFactory $orderFactory,
        Session $session,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        JsonFactory $resultJsonFactory,
        Collection $collection,
        Curl $curl
    ) {
        $this->paymentFactory    = $paymentFactory;
        $this->scopeConfig       = $scopeConfig;
        $this->logger            = $logger;
        $this->session           = $session;
        $this->orderFactory      = $orderFactory;
        $this->orderSender       = $orderSender;
        $this->helper            = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->collection        = $collection;
        $this->curl              = $curl;

        parent::__construct($context);
    }

    /**
     * Rozpoczęcie procesu płatności
     *
     * @return ResponseInterface|Json
     */
    public function execute()
    {
        try {
            /** @var Payment $payment */
            $payment       = $this->paymentFactory->create();

            $session       = $this->getCheckout();
            $quoteModuleId = $session->getBluePaymentQuoteId();
            $this->logger->info('CREATE:' . __LINE__, ['quoteModuleId' => $quoteModuleId]);
            $session->setQuoteId($quoteModuleId);
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();

            $this->logger->info('CREATE:' . __LINE__, [
                'sessionLastRealOrderSessionId' => $sessionLastRealOrderSessionId
            ]);

            $cardGateway = ConfigProvider::IFRAME_GATEWAY_ID;
            $blikGateway = ConfigProvider::BLIK_GATEWAY_ID;
            $gpayGateway = ConfigProvider::GPAY_GATEWAY_ID;
            $autopayGateway = $this->scopeConfig->getValue('payment/bluepayment/autopay_gateway');

            $order = $this->orderFactory->create()->loadByIncrementId($sessionLastRealOrderSessionId);

            $currency       = $order->getOrderCurrencyCode();
            $serviceId      = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
            $sharedKey      = $this->scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");
            $orderId        = $order->getRealOrderId();

            if (!$order->getId()) {
                $this->logger->info('CREATE:' . __LINE__, ['Zamówienie bez identyfikatora']);
            }
            $gatewayId = (int)$this->getRequest()->getParam('gateway_id', 0);
            $automatic = (boolean) $this->getRequest()->getParam('automatic', false);
            $cardIndex = (int)$this->getRequest()->getParam('card_index', 0);

            /** @var Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();

            $unchangeableStatuses = explode(
                ',',
                $this->scopeConfig->getValue("payment/bluepayment/unchangeable_statuses")
            );
            $statusWaitingPayment = $this->scopeConfig->getValue("payment/bluepayment/status_waiting_payment");

            if ($statusWaitingPayment != '') {
                /**
                 * @var Collection $statusCollection
                 */
                $statusCollection  = $this->collection;
                $orderStatusWaitingState = Order::STATE_NEW;
                foreach ($statusCollection->joinStates() as $status) {
                    /** @var \Magento\Sales\Model\Order\Status $status */
                    if ($status->getStatus() == $statusWaitingPayment) {
                        $orderStatusWaitingState = $status->getState();
                    }
                }
            } else {
                $orderStatusWaitingState = Order::STATE_PENDING_PAYMENT;
                $statusWaitingPayment = Order::STATE_PENDING_PAYMENT;
            }

            if (!in_array($order->getStatus(), $unchangeableStatuses)) {
                $this->logger->info('CREATE:' . __LINE__, ['orderStatusWaitingState' => $orderStatusWaitingState]);
                $this->logger->info('CREATE:' . __LINE__, ['statusWaitingPayment' => $statusWaitingPayment]);

                $order->setState($orderStatusWaitingState)->setStatus($statusWaitingPayment)->save();
            }

            if ($order->getCanSendNewEmailFlag()) {
                $this->logger->info('CREATE:' . __LINE__, ['getCanSendNewEmailFlag']);
                try {
                    $this->orderSender->send($order);
                } catch (Exception $e) {
                    $this->logger->critical($e);
                }
            }

            if ($cardGateway == $gatewayId && $automatic === true) {
                $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic);

                $hashData  = [$serviceId, $orderId, $sharedKey];
                $redirectHash = $this->helper->generateAndReturnHash($hashData);

                $result = $this->prepareIframeJsonResponse($payment->getUrlGateway(), $redirectHash, $params);

                $resultJson->setData($result);
                return $resultJson;
            }

            if ($blikGateway == $gatewayId && $automatic === true) {
                $authorizationCode = $this->getRequest()->getParam('code', 0);
                $this->logger->info('CREATE:' . __LINE__, ['authorizationCode' => $authorizationCode]);

                if ($this->validateBlikCode($authorizationCode)) {
                    $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic, $authorizationCode);
                    $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);

                    $responseParams = $this->sendRequestBlik($payment->getUrlGateway(), $params);
                    $this->logger->info('CREATE:' . __LINE__, ['responseParams' => $responseParams]);

                    if ($responseParams === false) {
                        $resultJson->setData([
                            'error' => true
                        ]);
                        return $resultJson;
                    }

                    $hashData  = [$serviceId, $orderId, $sharedKey];
                    $this->logger->info('CREATE:' . __LINE__, ['hashData' => $hashData]);

                    $hash = $this->helper->generateAndReturnHash($hashData);
                    $this->logger->info('CREATE:' . __LINE__, ['hash' => $hash]);

                    $params = [
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'GatewayID' => $gatewayId,
                        'hash' => $hash,
                        'confirmation' => $responseParams['confirmation'],
                        'paymentStatus' => $responseParams['paymentStatus']
                    ];
                    $result = $this->prepareBlikJsonResponse($payment->getUrlGateway(), $authorizationCode, $params);

                    $resultJson->setData($result);
                    return $resultJson;
                }

                $this->logger->info('CREATE: ' . __LINE__ . 'Invalid BLIK code');

                $resultJson->setData([
                    'status' => false,
                    'code' => $authorizationCode
                ]);
                return $resultJson;
            }

            if ($gpayGateway == $gatewayId && $automatic === true) {
                $token = $this->getRequest()->getParam('token', null);

                $this->logger->info('CREATE:' . __LINE__, ['token' => $token]);

                $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic, '', $token);
                $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);

                $responseParams = $this->sendRequestGPay($payment->getUrlGateway(), $params);
                $this->logger->info('CREATE:' . __LINE__, ['responseParams' => $responseParams]);

                if ($responseParams === false) {
                    $resultJson->setData([
                        'error' => true
                    ]);
                    return $resultJson;
                }

                $hashData  = [$serviceId, $orderId, $sharedKey];
                $this->logger->info('CREATE:' . __LINE__, ['hashData' => $hashData]);

                $hash = $this->helper->generateAndReturnHash($hashData);
                $this->logger->info('CREATE:' . __LINE__, ['hash' => $hash]);

                $params = [
                    'ServiceID' => $serviceId,
                    'OrderID' => $orderId,
                    'GatewayID' => $gatewayId,
                    'hash' => $hash,
                    'paymentStatus' => $responseParams['paymentStatus'],
                    'redirectUrl' => $responseParams['redirectUrl']
                ];
                $result = $this->prepareGPayJsonResponse($payment->getUrlGateway(), $params);

                $resultJson->setData($result);
                return $resultJson;
            }

            if ($autopayGateway == $gatewayId) {
                $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic, '', '', $cardIndex);

                if ($automatic === true) {
                    $hashData  = [$serviceId, $orderId, $sharedKey];
                    $redirectHash = $this->helper->generateAndReturnHash($hashData);

                    $result = $this->prepareIframeJsonResponse($payment->getUrlGateway(), $redirectHash, $params);

                    $resultJson->setData($result);
                    return $resultJson;
                }

                $result = $this->sendAutopayRequest($payment->getUrlGateway(), $params);

                if ($result === false) {
                    $resultJson->setData([
                        'error' => true
                    ]);
                    return $resultJson;
                }

                if ($result['redirectUrl'] !== null) {
                    // 3DS

                    $this->logger->info('CREATE:' . __LINE__, ['redirectUrl' => $result['redirectUrl']]);

                    /** @var Http $response */
                    $response = $this->getResponse();
                    return $response->setRedirect($result['redirectUrl']);
                }

                if ($result['paymentStatus'] == Payment::PAYMENT_STATUS_SUCCESS) {
                    // Got success status

                    return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                }

                // Otherwise - redirect to "waiting" page
                $hashData  = [$serviceId, $orderId, $sharedKey];
                $redirectHash = $this->helper->generateAndReturnHash($hashData);

                return $this->_redirect('bluepayment/processing/back', [
                    '_secure' => true,
                    '_query' => [
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Hash' => $redirectHash
                    ]
                ]);
            }

            $params = $payment->getFormRedirectFields($order, $gatewayId);
            $xml = $this->sendRequest($params, $payment->getUrlGateway());

            $redirectUrl = property_exists($xml, 'redirecturl') ? (string)$xml->redirecturl : null;

            if ($redirectUrl !== null) {
                // 3DS
                $this->logger->info('CREATE:' . __LINE__, ['redirectUrl' => $redirectUrl]);

                /** @var Http $response */
                $response = $this->getResponse();
                return $response->setRedirect($redirectUrl);
            }

            // Otherwise - redirect to "waiting" page
            $hashData  = [$serviceId, $orderId, $sharedKey];
            $redirectHash = $this->helper->generateAndReturnHash($hashData);

            return $this->_redirect('bluepayment/processing/back', [
                '_secure' => true,
                '_query' => [
                    'ServiceID' => $serviceId,
                    'OrderID' => $orderId,
                    'Hash' => $redirectHash
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return parent::_redirect('checkout/cart');
    }

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckout()
    {
        return $this->session;
    }

    /**
     * @param string $gatewayUrl
     * @param string $redirectHash
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
     * @param string $gatewayUrl
     * @param array  $params
     * @return array
     */
    private function prepareGPayJsonResponse($gatewayUrl, $params)
    {
        $params['GatewayID'] = (string) $params['GatewayID'];

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
        return false === empty($code) && self::BLIK_CODE_LENGTH === strlen($code);
    }

    /**
     * @param string $urlGateway
     * @param array $params
     *
     * @return array|false
     */
    private function sendRequestBlik($urlGateway, $params)
    {
        $xml = $this->sendRequest($params, $urlGateway);

        if ($xml !== false) {
            return [
                'orderID' => (string)$xml->orderID,
                'remoteID' => (string)$xml->remoteID,
                'confirmation' => (string)$xml->confirmation,
                'paymentStatus' => (string)$xml->paymentStatus
            ];
        }

        return false;
    }

    /**
     * @param string $urlGateway
     * @param array $params
     *
     * @return array|false
     */
    private function sendRequestGPay($urlGateway, $params)
    {
        $xml = $this->sendRequest($params, $urlGateway);

        if ($xml !== false) {
            return [
                'orderID' => (string)$xml->orderID,
                'remoteID' => (string)$xml->remoteID,
                'paymentStatus' => (string)$xml->status,
                'redirectUrl' => property_exists($xml, 'redirecturl') ? (string)$xml->redirecturl : null
            ];
        }

        return false;
    }

    /**
     * @param string $urlGateway
     * @param array $params
     *
     * @return array|false
     */
    private function sendAutopayRequest($urlGateway, $params)
    {
        $xml = $this->sendRequest($params, $urlGateway);

        if ($xml !== false) {
            return [
                'orderID' => (string)$xml->orderID,
                'remoteID' => (string)$xml->remoteID,
                'paymentStatus' => (string)$xml->status,
                'confirmation' => property_exists($xml, 'confirmation') ? (string)$xml->confirmation : null,
                'redirectUrl' => property_exists($xml, 'redirecturl') ? (string)$xml->redirecturl : null
            ];
        }

        return false;
    }

    /**
     * @param string $urlGateway
     * @param array $params
     *
     * @return \SimpleXMLElement|false
     */
    private function sendRequest($params, $urlGateway)
    {
        if (array_key_exists('ClientHash', $params)) {
            $this->curl->addHeader('BmHeader', 'pay-bm');
        } else {
            $this->curl->addHeader('BmHeader', 'pay-bm-continue-transaction-url');
        }

        $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);

        $this->curl->post($urlGateway, $params);
        $response = $this->curl->getBody();

        $this->logger->info('CREATE:' . __LINE__, ['response' => $response]);
        $xml = simplexml_load_string($response);

        return $xml;
    }
}
