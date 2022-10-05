<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\GetStateForStatus;
use BlueMedia\BluePayment\Model\Payment;
use BlueMedia\BluePayment\Model\PaymentFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Store\Model\ScopeInterface;
use SimpleXMLElement;

/**
 * Create payment (BM transaction) controller
 */
class Create extends Action
{
    public const IFRAME_GATEWAY_ID = 'IFRAME';
    public const BLIK_CODE_LENGTH = 6;

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

    /** @var CollectionFactory */
    public $gatewayFactory;

    /** @var Payment */
    public $bluepayment;

    /** @var GetStateForStatus */
    public $getStateForStatus;

    /** @var ConfigProvider */
    public $configProvider;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /**
     * Create constructor.
     *
     * @param  Context  $context
     * @param  OrderSender  $orderSender
     * @param  PaymentFactory  $paymentFactory
     * @param  OrderFactory  $orderFactory
     * @param  Session  $session
     * @param  Logger  $logger
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  Data  $helper
     * @param  JsonFactory  $resultJsonFactory
     * @param  Collection  $collection
     * @param  CollectionFactory  $gatewayFactory
     * @param  GetStateForStatus  $getStateForStatus
     * @param  ConfigProvider  $configProvider
     * @param  OrderRepositoryInterface  $orderRepository
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
        CollectionFactory $gatewayFactory,
        GetStateForStatus $getStateForStatus,
        ConfigProvider $configProvider,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->logger            = $logger;
        $this->session           = $session;
        $this->orderFactory      = $orderFactory;
        $this->orderSender       = $orderSender;
        $this->helper            = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->collection        = $collection;
        $this->gatewayFactory    = $gatewayFactory;
        $this->getStateForStatus = $getStateForStatus;
        $this->configProvider    = $configProvider;
        $this->orderRepository = $orderRepository;

        $this->bluepayment = $paymentFactory->create();

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
            $session       = $this->session;
            $quoteModuleId = $session->getBluePaymentQuoteId();
            $this->logger->info('CREATE:' . __LINE__, ['quoteModuleId' => $quoteModuleId]);
            $session->setQuoteId($quoteModuleId);
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();

            $this->logger->info('CREATE:' . __LINE__, [
                'sessionLastRealOrderSessionId' => $sessionLastRealOrderSessionId
            ]);

            $order = $this->orderFactory->create()->loadByIncrementId($sessionLastRealOrderSessionId);

            $currency       = $order->getOrderCurrencyCode();
            $serviceId      = $this->scopeConfig->getValue(
                'payment/bluepayment/'.strtolower($currency).'/service_id',
                ScopeInterface::SCOPE_STORE
            );
            $sharedKey      = $this->scopeConfig->getValue(
                'payment/bluepayment/'.strtolower($currency).'/shared_key',
                ScopeInterface::SCOPE_STORE
            );
            $orderId        = $order->getRealOrderId();
            $agreementsIds  = $order->getPayment()->hasAdditionalInformation('agreements_ids')
                ? array_unique(explode(',', $order->getPayment()->getAdditionalInformation('agreements_ids') ?: ''))
                : [];

            if (!$order->getId()) {
                $this->logger->info('CREATE:' . __LINE__, ['Zamówienie bez identyfikatora']);
            }

            $gatewayId = (int) $this->getRequest()->getParam('gateway_id', 0);
            $automatic = (boolean) $this->getRequest()->getParam('automatic', false);
            $cardIndex = (int) $this->getRequest()->getParam('card_index', 0);

            $resultJson = $this->resultJsonFactory->create();

            $unchangeableStatuses = $this->configProvider->getUnchangableStatuses();
            $status = $this->configProvider->getStatusWaitingPayment();
            $state = $this->getStateForStatus->execute($status, Order::STATE_PENDING_PAYMENT);

            if (!in_array($order->getStatus(), $unchangeableStatuses)) {
                $this->logger->info('CREATE:' . __LINE__, ['state' => $state]);
                $this->logger->info('CREATE:' . __LINE__, ['status' => $status]);

                $order->setState($state)
                    ->setStatus($status);
            }

            // Set Payment Channel to Order
            $gateway = $this->gatewayFactory->create()
                ->addFieldToFilter('gateway_service_id', $serviceId)
                ->addFieldToFilter('gateway_id', $gatewayId)
                ->getFirstItem();

            $order->setBlueGatewayId($gatewayId);
            $order->setPaymentChannel($gateway->getData('gateway_name'));
            $this->orderRepository->save($order);

            if ($order->getCanSendNewEmailFlag()) {
                $this->logger->info('CREATE:' . __LINE__, ['getCanSendNewEmailFlag']);
                try {
                    $this->orderSender->send($order);
                } catch (Exception $e) {
                    $this->logger->critical($e);
                }
            }

            if (ConfigProvider::CARD_GATEWAY_ID == $gatewayId && $automatic === true) {
                $params = $this->bluepayment->getFormRedirectFields(
                    $order,
                    $gatewayId,
                    $agreementsIds,
                    true
                );

                $hashData  = [$serviceId, $orderId, $sharedKey];
                $redirectHash = $this->helper->generateAndReturnHash($hashData);

                $result = $this->prepareIframeJsonResponse($this->bluepayment->getUrlGateway(), $redirectHash, $params);

                $resultJson->setData($result);
                return $resultJson;
            }

            if (ConfigProvider::BLIK_GATEWAY_ID == $gatewayId && $automatic === true) {
                $authorizationCode = $this->getRequest()->getParam('code', 0);
                $this->logger->info('CREATE:' . __LINE__, ['authorizationCode' => $authorizationCode]);

                if ($this->validateBlikCode($authorizationCode)) {
                    $params = $this->bluepayment->getFormRedirectFields(
                        $order,
                        $gatewayId,
                        $agreementsIds,
                        true,
                        $authorizationCode
                    );

                    $xml = $this->sendRequest($params);

                    if ($xml === false) {
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
                        'confirmation' => property_exists($xml, 'confirmation') ? (string)$xml->confirmation : null,
                        'paymentStatus' => property_exists($xml, 'paymentStatus') ? (string)$xml->paymentStatus : null,
                    ];
                    $result = $this->prepareBlikJsonResponse($authorizationCode, $params);

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

            if (ConfigProvider::GPAY_GATEWAY_ID == $gatewayId && $automatic === true) {
                $token = $this->getRequest()->getParam('token');

                $this->logger->info('CREATE:' . __LINE__, ['token' => $token]);

                $params = $this->bluepayment->getFormRedirectFields(
                    $order,
                    $gatewayId,
                    $agreementsIds,
                    true,
                    '',
                    $token
                );

                $xml = $this->sendRequest($params);

                if ($xml === false) {
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
                    'paymentStatus' => property_exists($xml, 'paymentStatus') ? (string) $xml->paymentStatus : null,
                    'redirectUrl' => property_exists($xml, 'redirectUrl') ? (string) $xml->redirectUrl : null,
                ];
                $result = $this->prepareGPayJsonResponse($this->bluepayment->getUrlGateway(), $params);

                $resultJson->setData($result);
                return $resultJson;
            }

            if (ConfigProvider::ONECLICK_GATEWAY_ID == $gatewayId) {
                $params = $this->bluepayment->getFormRedirectFields(
                    $order,
                    $gatewayId,
                    $agreementsIds,
                    $automatic,
                    '',
                    '',
                    $cardIndex
                );

                if ($automatic === true) {
                    $hashData  = [$serviceId, $orderId, $sharedKey];
                    $redirectHash = $this->helper->generateAndReturnHash($hashData);

                    $result = $this->prepareIframeJsonResponse($this->bluepayment->getUrlGateway(), $redirectHash, $params);

                    $resultJson->setData($result);
                    return $resultJson;
                }

                $xml = $this->sendRequest($params);

                if ($xml === false) {
                    $resultJson->setData([
                        'error' => true
                    ]);
                    return $resultJson;
                }

                $redirectUrl = property_exists($xml, 'redirecturl') ? (string) $xml->redirecturl : null;
                if ($redirectUrl) {
                    // 3DS
                    $this->logger->info('CREATE:' . __LINE__, ['redirectUrl' => $redirectUrl]);

                    /** @var Http $response */
                    $response = $this->getResponse();
                    return $response->setRedirect($redirectUrl);
                }

                $paymentStatus = property_exists($xml, 'paymentStatus') ? (string) $xml->paymentStatus : null;
                if ($paymentStatus == Payment::PAYMENT_STATUS_SUCCESS) {
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

            $params = $this->bluepayment->getFormRedirectFields(
                $order,
                $gatewayId,
                $agreementsIds
            );
            $xml = $this->sendRequest($params);

            $redirectUrl = property_exists($xml, 'redirecturl') ? (string)$xml->redirecturl : null;

            if ($redirectUrl !== null) {
                $this->logger->info('CREATE:' . __LINE__, ['redirectUrl' => $redirectUrl]);

                $waitingPage = $this->scopeConfig->getValue(
                    'payment/bluepayment/waiting_page',
                    ScopeInterface::SCOPE_STORE
                );

                if ($waitingPage) {
                    $waitingPageSeconds = $this->scopeConfig->getValue(
                        'payment/bluepayment/waiting_page_seconds',
                        ScopeInterface::SCOPE_STORE
                    );

                    // Redirect only if set in settings
                    $session->setRedirectUrl($redirectUrl);
                    $session->setWaitingPageSeconds($waitingPageSeconds);

                    $response = $this->getResponse();
                    return $response->setRedirect('/bluepayment/processing/redirect');
                }

                /** @var Http $response */
                $response = $this->getResponse();
                return $response->setRedirect($redirectUrl);
            }

            // Otherwise - redirect to "back" page
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

        return $this->_redirect('checkout/cart');
    }

    /**
     * @param  string  $code
     *
     * @return bool
     */
    private function validateBlikCode(string $code): bool
    {
        return false === empty($code) && self::BLIK_CODE_LENGTH === strlen($code);
    }

    /**
     * @param  string  $gatewayUrl
     * @param  string  $redirectHash
     * @param  array  $params
     *
     * @return array
     */
    private function prepareIframeJsonResponse(string $gatewayUrl, string $redirectHash, array $params): array
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
     * @param  string  $authorizationCode
     * @param  array  $params
     *
     * @return array
     */
    private function prepareBlikJsonResponse(string $authorizationCode, array $params): array
    {
        $params['GatewayID'] = (string) $params['GatewayID'];
        $params['AuthorizationCode'] = $authorizationCode;

        return [
            'gateway_url' => $this->bluepayment->getUrlGateway(),
            'params' => $params,
        ];
    }

    /**
     * @param  string  $gatewayUrl
     * @param  array  $params
     *
     * @return array
     */
    private function prepareGPayJsonResponse(string $gatewayUrl, array $params): array
    {
        $params['GatewayID'] = (string) $params['GatewayID'];

        return [
            'gateway_url' => $gatewayUrl,
            'params' => $params,
        ];
    }

    /**
     * @param  array  $params
     *
     * @return SimpleXMLElement|false
     */
    private function sendRequest(array $params)
    {
        $this->logger->info('CREATE:' . __LINE__, ['params' => $params]);
        $response = $this->bluepayment->sendRequest($params);
        $this->logger->info('CREATE:' . __LINE__, ['response' => (array) $response]);

        return $response;
    }
}
