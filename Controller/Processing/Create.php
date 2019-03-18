<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\PaymentFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;

/**
 * Class Create
 *
 * @package BlueMedia\BluePayment\Controller\Processing
 */
class Create extends Action
{
    const IFRAME_GATEWAY_ID = 'IFRAME';
    const BLIK_STATUS_SUCCESS = 'SUCCESS';
    const BLIK_CODE_LENGTH = 6;

    /** @var PaymentFactory */
    public $paymentFactory;

    /** @var OrderFactory */
    public $orderFactory;

    /** @var Session */
    public $session;

    /** @var LoggerInterface */
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
     * @param JsonFactory          $resultJsonFactory
     * @param Collection           $collection
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
        Collection $collection
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

        parent::__construct($context);
    }

    /**
     * Rozpoczęcie procesu płatności
     */
    public function execute()
    {
        try {
            $payment       = $this->paymentFactory->create();
            $session       = $this->getCheckout();
            $quoteModuleId = $session->getBluePaymentQuoteId();
            $this->logger->info('CREATE:' . __LINE__, ['quoteModuleId' => $quoteModuleId]);
            $session->setQuoteId($quoteModuleId);
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();

            $this->logger->info('CREATE:' . __LINE__, [
                'sessionLastRealOrderSessionId' => $sessionLastRealOrderSessionId
            ]);

            $cardGateway = $this->scopeConfig->getValue("payment/bluepayment/card_gateway");
            $blikGateway = $this->scopeConfig->getValue('payment/bluepayment/blik_gateway');
            $gpayGateway = $this->scopeConfig->getValue('payment/bluepayment/gpay_gateway');

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
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }

            if ($cardGateway == $gatewayId && $automatic === true) {
                $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic);

                $hashData  = [$serviceId, $orderId, $sharedKey];
                $redirectHash = $this->helper->generateAndReturnHash($hashData);

                $result = $this->prepareIframeJsonResponse($payment->getUrlGateway(), $redirectHash, $params);

                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData($result);
                return $resultJson;
            }

            if ($blikGateway == $gatewayId && $automatic === true) {
                $authorizationCode = (int)$this->getRequest()->getParam('code', 0);
                $this->logger->info('CREATE:' . __LINE__, ['authorizationCode' => $authorizationCode]);

                if ($this->validateBlikCode($authorizationCode)) {
                    $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic, $authorizationCode);
                    $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);

                    $responseParams = $this->sendRequestBlik($payment->getUrlGateway(), $params);
                    $this->logger->info('CREATE:' . __LINE__, ['responseParams' => $responseParams]);

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

                    /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                    $resultJson = $this->resultJsonFactory->create();
                    $resultJson->setData($result);
                    return $resultJson;
                }

                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData([
                    'status' => false
                ]);
                return $resultJson;
            }

            if ($gpayGateway == $gatewayId && $automatic === true) {
                $token = $this->getRequest()->getParam('token', null);

                $this->logger->info('CREATE:' . __LINE__, ['token' => $token]);

                $params = $payment->getFormRedirectFields($order, $gatewayId, $automatic, 0, $token);
                $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);

                $responseParams = $this->sendRequestGPay($payment->getUrlGateway(), $params);
                $this->logger->info('CREATE:' . __LINE__, ['responseParams' => $responseParams]);

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

                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData($result);
                return $resultJson;
            }

            $url = $this->_url->getUrl(
                $payment->getUrlGateway()
                . '?'
                . http_build_query($payment->getFormRedirectFields($order, $gatewayId))
            );

            $this->logger->info('CREATE:' . __LINE__, ['redirectUrl' => $url]);
            $this->getResponse()->setRedirect($url);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        parent::_redirect('checkout/cart');
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
     * @param string $authorizationCode
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
        return (false === empty($code) && self::BLIK_CODE_LENGTH === strlen($code))? true : false;
    }

    /**
     * @param $urlGateway
     * @param $params
     *
     * @return array
     */
    private function sendRequestBlik($urlGateway, $params)
    {
        $fields = (is_array($params)) ? http_build_query($params) : $params;
        $curl = curl_init($urlGateway);
        if (array_key_exists('ClientHash', $params)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['BmHeader: pay-bm']);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['BmHeader: pay-bm-continue-transaction-url']);
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

    /**
     * @param $urlGateway
     * @param $params
     *
     * @return array
     */
    private function sendRequestGPay($urlGateway, $params)
    {
        $fields = (is_array($params)) ? http_build_query($params) : $params;
        $curl = curl_init($urlGateway);
        if (array_key_exists('ClientHash', $params)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['BmHeader: pay-bm']);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['BmHeader: pay-bm-continue-transaction-url']);
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

        $paymentStatus = (string) $xml->status;
        $orderID = (string) $xml->orderID;
        $remoteID = (string) $xml->remoteID;
        $hash = (string) $xml->hash;
        $redirectUrl = property_exists($xml, 'redirecturl') ? (string) $xml->redirecturl : null;

        $this->logger->info('CREATE:' . __LINE__, ['$curlResponse' => $curlResponse]);

        $responseParams = [
            'orderID' => $orderID,
            'remoteID' => $remoteID,
            'paymentStatus' => $paymentStatus,
            'redirectUrl' => $redirectUrl
        ];

        return $responseParams;
    }
}
