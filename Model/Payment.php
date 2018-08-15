<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Helper\Data;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

/**
 * Class Payment
 *
 * @package BlueMedia\BluePayment\Model
 */
class Payment extends AbstractMethod
{
    const METHOD_CODE                    = 'bluepayment';
    const IFRAME_GATEWAY_ID              = 'IFRAME';
    const DEFAULT_TRANSACTION_LIFE_HOURS = false;

    /**
     * Stałe statusów płatności
     */
    const PAYMENT_STATUS_PENDING = 'PENDING';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILURE = 'FAILURE';


    /**
     * Stałe potwierdzenia autentyczności transakcji
     */
    const TRANSACTION_CONFIRMED    = "CONFIRMED";
    const TRANSACTION_NOTCONFIRMED = "NOTCONFIRMED";

    /**
     * @var array
     */
    private $_checkHashArray = [];

    /**
     * Unikatowy wewnętrzy identyfikator metody płatności
     *
     * @var string [a-z0-9_]
     */
    protected $_code = 'bluepayment';

    /**
     * Blok z formularza płatności
     *
     * @var string
     */
    protected $_formBlockType = 'BlueMedia\BluePayment\Block\Form';

    /**
     * Czy ta opcja płatności może być pokazywana na stronie
     * płatności w zakupach typu 'checkout' ?
     *
     * @var boolean
     */
    protected $_canUseCheckout = true;

    /**
     * Czy stosować tą metodę płatności dla opcji multi-dostaw ?
     *
     * @var boolean
     */
    protected $_canUseForMultishipping = false;

    /**
     * Czy ta metoda płatności jest bramką (online auth/charge) ?
     *
     * @var boolean
     */
    protected $_isGateway = false;

    /**
     * Możliwość użycia formy płatności z panelu administracyjnego
     *
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * Czy wymagana jest inicjalizacja ?
     *
     * @var boolean
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \BlueMedia\BluePayment\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $sender;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var \BlueMedia\BluePayment\Model\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \BlueMedia\BluePayment\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Payment constructor.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender               $orderSender
     * @param \BlueMedia\BluePayment\Helper\Data                                $helper
     * @param \Magento\Framework\UrlInterface                                   $url
     * @param \Magento\Sales\Model\OrderFactory                                 $orderFactory
     * @param \Magento\Framework\Model\Context                                  $context
     * @param \Magento\Framework\Registry                                       $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory                 $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                      $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                      $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                              $logger
     * @param \BlueMedia\BluePayment\Model\TransactionFactory                   $transactionFactory
     * @param \BlueMedia\BluePayment\Api\TransactionRepositoryInterface         $transactionRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null      $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null                $resourceCollection
     * @param array                                                             $data
     */
    public function __construct(
        CollectionFactory              $statusCollectionFactory,
        OrderSender                    $orderSender,
        Data                           $helper,
        UrlInterface                   $url,
        OrderFactory                   $orderFactory,
        Context                        $context,
        Registry                       $registry,
        ExtensionAttributesFactory     $extensionFactory,
        AttributeValueFactory          $customAttributeFactory,
        PaymentData                    $paymentData,
        ScopeConfigInterface           $scopeConfig,
        Logger                         $logger,
        TransactionFactory             $transactionFactory,
        TransactionRepositoryInterface $transactionRepository,
        AbstractResource               $resource = null,
        AbstractDb                     $resourceCollection = null,
        array                          $data = []
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->sender                  = $orderSender;
        $this->url                     = $url;
        $this->helper                  = $helper;
        $this->orderFactory            = $orderFactory;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->transactionFactory    = $transactionFactory;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Zwraca adres url kontrolera do przekierowania po potwierdzeniu zamówienia
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->url->getUrl('bluepayment/processing/create', ['_secure' => true]);
    }

    /**
     * Zwraca adres bramki
     *
     * @return string
     */
    public function getUrlGateway()
    {
        if ($this->getConfigData('test_mode')) {
            return $this->getConfigData("test_address_url");
        }

        return $this->getConfigData("prod_address_url");
    }

    /**
     * Tablica z parametrami do wysłania metodą GET do bramki
     *
     * @param object $order
     * @param int    $gatewayId
     * @param string $authorizationCode
     * @return array
     */
    public function getFormRedirectFields($order, $gatewayId = 0, $authorizationCode = 0)
    {
        $orderId       = $order->getRealOrderId();
        $amount        = number_format(round($order->getGrandTotal(), 2), 2, '.', '');
        $serviceId     = $this->getConfigData('service_id');
        $sharedKey     = $this->getConfigData('shared_key');
        $customerEmail = $order->getCustomerEmail();
        $validityTime  = $this->getTransactionLifeHours();
        $cardGateway   = $this->getConfigData('card_gateway');
        $blikGateway   = $this->getConfigData('blik_gateway');

        if ($gatewayId === 0) {
            if ($validityTime) {
                $hashData  = [$serviceId, $orderId, $amount, $customerEmail, $validityTime, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params    = [
                    'ServiceID'     => $serviceId,
                    'OrderID'       => $orderId,
                    'Amount'        => $amount,
                    'CustomerEmail' => $customerEmail,
                    'ValidityTime'  => $validityTime,
                    'Hash'          => $hashLocal,
                ];
            } else {
                $hashData  = [$serviceId, $orderId, $amount, $customerEmail, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params    = [
                    'ServiceID'     => $serviceId,
                    'OrderID'       => $orderId,
                    'Amount'        => $amount,
                    'CustomerEmail' => $customerEmail,
                    'Hash'          => $hashLocal,
                ];
            }
        } else {
            if ($validityTime) {
                $hashData  = [$serviceId, $orderId, $amount, $gatewayId, $customerEmail, $validityTime, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params    = [
                    'ServiceID'     => $serviceId,
                    'OrderID'       => $orderId,
                    'Amount'        => $amount,
                    'GatewayID'     => $gatewayId,
                    'CustomerEmail' => $customerEmail,
                    'ValidityTime'  => $validityTime,
                    'Hash'          => $hashLocal,
                ];
            } else {
                $hashData  = [$serviceId, $orderId, $amount, $gatewayId, $customerEmail, $sharedKey];

                if ($cardGateway == $gatewayId) {
                    $hashData  = [$serviceId, $orderId, $amount, $gatewayId, $customerEmail, self::IFRAME_GATEWAY_ID, $sharedKey];
                    $hashLocal = $this->helper->generateAndReturnHash($hashData);

                    return [
                        'ServiceID'         => $serviceId,
                        'OrderID'           => $orderId,
                        'Amount'            => $amount,
                        'GatewayID'         => $gatewayId,
                        'CustomerEmail'     => $customerEmail,
                        'ScreenType'        => self::IFRAME_GATEWAY_ID,
                        'Hash'              => $hashLocal,
                    ];
                }

                if ($blikGateway == $gatewayId) {
                    $hashData  = [$serviceId, $orderId, $amount, $gatewayId, $customerEmail, $authorizationCode, $sharedKey];
                    $hashLocal = $this->helper->generateAndReturnHash($hashData);

                    return [
                        'ServiceID'         => $serviceId,
                        'OrderID'           => $orderId,
                        'Amount'            => $amount,
                        'GatewayID'         => $gatewayId,
                        'CustomerEmail'     => $customerEmail,
                        'AuthorizationCode' => $authorizationCode,
                        'Hash'              => $hashLocal,
                    ];
                }

                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params    = [
                    'ServiceID'     => $serviceId,
                    'OrderID'       => $orderId,
                    'Amount'        => $amount,
                    'GatewayID'     => $gatewayId,
                    'CustomerEmail' => $customerEmail,
                    'Hash'          => $hashLocal,
                ];
            }
        }


        return $params;
    }

    /**
     * Transaction lifetime
     *
     * @return mixed
     */
    private function getTransactionLifeHours()
    {
        $hours = $this->getConfigData('transaction_life_hours');
        if ($hours && is_int($hours) && $hours >= 1 && $hours <= 720) {
            date_default_timezone_set('Europe/Warsaw');
            $expirationTime = date('Y-m-d H:i:s', strtotime("+" . $hours . " hour"));

            return $expirationTime;
        }

        return self::DEFAULT_TRANSACTION_LIFE_HOURS;
    }

    /**
     * Ustawia odpowiedni status transakcji/płatności zgodnie z uzyskaną informacją
     * z akcji 'statusAction'
     *
     * @param $response
     */
    public function processStatusPayment($response)
    {
        if ($this->_validAllTransaction($response)) {
            $transaction_xml = $response->transactions->transaction;
            $this->updateStatusTransactionAndOrder($transaction_xml);
        }
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     *
     * @param $response
     *
     * @return bool
     */
    public function _validAllTransaction($response)
    {
        if ($this->getConfigData('service_id') != $response->serviceID) {
            return false;
        }
        $this->_checkHashArray   = [];
        $hash                    = (string)$response->hash;
        $this->_checkHashArray[] = (string)$response->serviceID;

        foreach ($response->transactions->transaction as $trans) {
            $this->_checkInList($trans);
        }
        $this->_checkHashArray[] = $this->getConfigData('shared_key');
        $connectedFields = implode($this->getConfigData('hash_separator'), $this->_checkHashArray);


        return hash($this->getConfigData('hash_algorithm'), $connectedFields) == $hash;
    }

    /**
     * Sprawdza czy zamówienie zostało zakończone, zamknięte, lub anulowane
     *
     * @param object $order
     *
     * @return boolean
     */
    public function isOrderCompleted($order)
    {
        $status        = $order->getStatus();
        $stateOrderTab = array(
            \Magento\Sales\Model\Order::STATE_CLOSED,
            \Magento\Sales\Model\Order::STATE_CANCELED,
            \Magento\Sales\Model\Order::STATE_COMPLETE,
        );

        if ($this->getConfigData('status_accept_payment') != '') {
            $statusAcceptPayment = $this->getConfigData('status_accept_payment');
        } else {
            $statusAcceptPayment = $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        }

        $alreadyPaidBefore  = false;
        $orderStatusHistory = $order->getAllStatusHistory();
        foreach ($orderStatusHistory as $historyStatus) {
            if ($historyStatus->getStatus() == $statusAcceptPayment) {
                if ($order->getTotalDue() == 0) {
                    $alreadyPaidBefore = true;
                }
            }
        }

        return in_array($status, $stateOrderTab) || $alreadyPaidBefore;
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     *
     * @return void
     * @throws \Exception
     */
    protected function updateStatusTransactionAndOrder($transaction)
    {
        $paymentStatus     = (string)$transaction->paymentStatus;
        $remoteId          = $transaction->remoteID;
        $orderId           = $transaction->orderID;
        $transactionAmount = number_format(round($transaction->amount, 2), 2, '.', '');
        $order             = $this->orderFactory->create()->loadByIncrementId($orderId);

        $this->saveTransactionResponse($transaction);

        /**
         * @var \Magento\Sales\Model\Order\Payment $orderPayment
         */
        $orderPayment      = $order->getPayment();
        $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');
        $amount            = number_format(round($order->getGrandTotal(), 2), 2, '.', '');
        $transactionAmount = $amount;

        $orderStatusWaitingState = Order::STATE_PENDING_PAYMENT;
        if ($this->getConfigData('status_waiting_payment') != '') {
            $statusWaitingPayment = $this->getConfigData('status_waiting_payment');
            foreach ($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var \Magento\Sales\Model\Order\Status $status */
                if ($status->getStatus() == $statusWaitingPayment) {
                    $orderStatusWaitingState = $status->getState();
                }
            }
        } else {
            $statusWaitingPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT);
        }

        $orderStatusAcceptState = Order::STATE_PROCESSING;
        if ($this->getConfigData('status_accept_payment') != '') {
            $statusAcceptPayment = $this->getConfigData('status_accept_payment');
            foreach ($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var \Magento\Sales\Model\Order\Status $status */
                if ($status->getStatus() == $statusAcceptPayment) {
                    $orderStatusAcceptState = $status->getState();
                }
            }
        } else {
            $statusAcceptPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
        }

        $orderStatusErrorState = Order::STATE_PENDING_PAYMENT;
        if ($this->getConfigData('status_error_payment') != '') {
            $statusErrorPayment = $this->getConfigData('status_error_payment');
            foreach ($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var \Magento\Sales\Model\Order\Status $status */
                if ($status->getStatus() == $statusErrorPayment) {
                    $orderStatusErrorState = $status->getState();
                }
            }
        } else {
            $statusErrorPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW);
        }

        try {
            if (!$this->isOrderCompleted($order) && $orderPaymentState != $paymentStatus) {
                $orderComment = '[BM] Transaction ID: ' . (string)$remoteId . ' | Amount: ' . $transactionAmount . ' | Status: ' . $paymentStatus;
                switch ($paymentStatus) {
                    case self::PAYMENT_STATUS_PENDING:
                        if ($paymentStatus != $orderPaymentState) {
                            $order->setState($orderStatusWaitingState)
                                ->setStatus($statusWaitingPayment)
                                ->addStatusToHistory(
                                    $statusWaitingPayment,
                                    $orderComment,
                                    false
                                )
                                ->save();
                        }
                        break;
                    case self::PAYMENT_STATUS_SUCCESS:
                        $transaction = $orderPayment->setTransactionId((string)$remoteId);
                        $transaction->prependMessage('[' . self::PAYMENT_STATUS_SUCCESS . ']');
                        $transaction->registerCaptureNotification($transactionAmount)
                            ->setIsTransactionApproved(true)
                            ->setIsTransactionClosed(true)
                            ->save();
                        $order->setState($orderStatusAcceptState)
                            ->setStatus($statusAcceptPayment)
                            ->addStatusToHistory(
                                $statusAcceptPayment,
                                $orderComment,
                                false
                            )
                            ->save();
                        break;
                    case self::PAYMENT_STATUS_FAILURE:
                        if ($orderPaymentState != $paymentStatus) {
                            $order->setState($orderStatusErrorState)
                                ->setStatus($statusErrorPayment)
                                ->addStatusToHistory(
                                    $orderStatusErrorState,
                                    $orderComment,
                                    false
                                )
                                ->save();
                        }
                        break;
                    default:
                        break;
                }
            } else {
                $orderComment = '[BM] Transaction ID: ' . (string)$remoteId . ' | Amount: ' . $transactionAmount . ' | Status: ' . $paymentStatus . ' [IGNORED]';
                $order->addStatusToHistory(
                    $order->getStatus(),
                    $orderComment,
                    false
                )
                    ->save();
            }
            if (!$order->getEmailSent()) {
                $this->sender->send($order);
            }
            $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
            $orderPayment->save();
            $this->returnConfirmation($orderId, self::TRANSACTION_CONFIRMED);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $list
     */
    private function _checkInList($list)
    {
        foreach ((array)$list as $row) {
            if (is_object($row)) {
                $this->_checkInList($row);
            } else {
                $this->_checkHashArray[] = $row;
            }
        }
    }

    /**
     * Potwierdzenie w postaci xml o prawidłowej/nieprawidłowej transakcji
     *
     * @param string $orderId
     * @param string $confirmation
     *
     * @return XML
     */
    protected function returnConfirmation($orderId, $confirmation)
    {
        $serviceId        = $this->getConfigData('service_id');
        $sharedKey        = $this->getConfigData('shared_key');
        $hashData         = [$serviceId, $orderId, $confirmation, $sharedKey];
        $hashConfirmation = $this->helper->generateAndReturnHash($hashData);

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $confirmationList = $dom->createElement('confirmationList');

        $domServiceID = $dom->createElement('serviceID', $serviceId);
        $confirmationList->appendChild($domServiceID);

        $transactionsConfirmations = $dom->createElement('transactionsConfirmations');
        $confirmationList->appendChild($transactionsConfirmations);

        $domTransactionConfirmed = $dom->createElement('transactionConfirmed');
        $transactionsConfirmations->appendChild($domTransactionConfirmed);

        $domOrderID = $dom->createElement('orderID', $orderId);
        $domTransactionConfirmed->appendChild($domOrderID);

        $domConfirmation = $dom->createElement('confirmation', $confirmation);
        $domTransactionConfirmed->appendChild($domConfirmation);

        $domHash = $dom->createElement('hash', $hashConfirmation);
        $confirmationList->appendChild($domHash);

        $dom->appendChild($confirmationList);

        echo $dom->saveXML();
    }

    /**
     * @param $transactionResponse
     */
    private function saveTransactionResponse($transactionResponse)
    {
        /** @var \BlueMedia\BluePayment\Model\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->setOrderId($transactionResponse->orderID)
            ->setRemoteId($transactionResponse->remoteID)
            ->setAmount((float)$transactionResponse->amount)
            ->setCurrency($transactionResponse->currency)
            ->setGatewayId((int)$transactionResponse->gatewayID)
            ->setPaymentDate($transactionResponse->paymentDate)
            ->setPaymentStatus($transactionResponse->paymentStatus)
            ->setPaymentStatusDetails($transactionResponse->paymentStatusDetails);

        try {
            $this->transactionRepository->save($transaction);
        } catch (CouldNotSaveException $e) {
            $this->_logger->error(__('Could not save BluePayment Transaction entity: ') . $transaction->toJson());
        }
    }

}
