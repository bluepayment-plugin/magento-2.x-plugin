<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
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
    const METHOD_CODE = 'bluepayment';
    const IFRAME_GATEWAY_ID = 'IFRAME';
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
    const TRANSACTION_CONFIRMED = "CONFIRMED";
    const TRANSACTION_NOTCONFIRMED = "NOTCONFIRMED";

    private $orderParams = ['ServiceID', 'OrderID', 'Amount', 'GatewayID', 'Currency',
        'CustomerEmail', 'CustomerIP', 'RecurringAcceptanceState', 'RecurringAction', 'ClientHash', 'ScreenType'];

    /**
     * @var array
     */
    private $checkHashArray = [];

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

    /** @var OrderFactory */
    private $orderFactory;

    /** @var CardFactory */
    private $cardFactory;

    /** @var CardCollectionFactory */
    private $cardCollectionFactory;

    /** @var CardResource */
    private $cardResource;

    /** @var EncryptorInterface */
    private $encryptor;

    /**
     * @var \BlueMedia\BluePayment\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $sender;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @var \BlueMedia\BluePayment\Model\TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var \BlueMedia\BluePayment\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * Payment constructor.
     *
     * @param CollectionFactory                 $statusCollectionFactory
     * @param OrderSender                       $orderSender
     * @param Data                              $helper
     * @param UrlInterface                      $url
     * @param OrderFactory                      $orderFactory
     * @param Context                           $context
     * @param Registry                          $registry
     * @param ExtensionAttributesFactory        $extensionFactory
     * @param AttributeValueFactory             $customAttributeFactory
     * @param PaymentData                       $paymentData
     * @param ScopeConfigInterface              $scopeConfig
     * @param Logger                            $logger
     * @param TransactionFactory                $transactionFactory
     * @param TransactionRepositoryInterface    $transactionRepository
     * @param CardFactory                                                       $cardFactory
     * @param CardCollectionFactory                                             $cardCollectionFactory
     * @param CardResource
     * @param AbstractResource|null             $resource
     * @param AbstractDb|null                   $resourceCollection
     * @param array                             $data
     */
    public function __construct(
        CollectionFactory $statusCollectionFactory,
        OrderSender $orderSender,
        Data $helper,
        UrlInterface $url,
        OrderFactory $orderFactory,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentData $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        TransactionFactory $transactionFactory,
        TransactionRepositoryInterface $transactionRepository,
        CardFactory $cardFactory,
        CardCollectionFactory $cardCollectionFactory,
        CardResource $cardResource,
        EncryptorInterface $encryptor,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->sender = $orderSender;
        $this->url = $url;
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardResource = $cardResource;
        $this->encryptor = $encryptor;

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

        $this->transactionFactory = $transactionFactory;
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
     * @param Order $order
     * @param int    $gatewayId
     * @param bool   $automatic
     * @param string|int $authorizationCode
     * @param string $paymentToken
     *
     * @return array
     */
    public function getFormRedirectFields(
        $order,
        $gatewayId = 0,
        $automatic = false,
        $authorizationCode = 0,
        $paymentToken = '',
        $cardIndex = -1
    ) {
        $orderId       = $order->getRealOrderId();
        $amount        = number_format(round($order->getGrandTotal(), 2), 2, '.', '');
        $currency      = $order->getOrderCurrencyCode();

        // Config
        $serviceId     = $this->_scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
        $sharedKey     = $this->_scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");
        $cardGateway   = $this->_scopeConfig->getValue('payment/bluepayment/card_gateway');
        $blikGateway   = $this->_scopeConfig->getValue('payment/bluepayment/blik_gateway');
        $gpayGateway   = $this->_scopeConfig->getValue('payment/bluepayment/gpay_gateway');
        $autopayGateway = $this->_scopeConfig->getValue('payment/bluepayment/autopay_gateway');

        $customerEmail = $order->getCustomerEmail();
        $validityTime = $this->getTransactionLifeHours();

        if ($gatewayId === 0) {
            if ($validityTime) {
                $hashData = [$serviceId, $orderId, $amount, $currency, $customerEmail, $validityTime, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params = [
                    'ServiceID' => $serviceId,
                    'OrderID' => $orderId,
                    'Amount' => $amount,
                    'Currency' => $currency,
                    'CustomerEmail' => $customerEmail,
                    'ValidityTime' => $validityTime,
                    'Hash' => $hashLocal,
                ];
            } else {
                $hashData = [$serviceId, $orderId, $amount, $currency, $customerEmail, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params = [
                    'ServiceID' => $serviceId,
                    'OrderID' => $orderId,
                    'Amount' => $amount,
                    'Currency' => $currency,
                    'CustomerEmail' => $customerEmail,
                    'Hash' => $hashLocal,
                ];
            }
        } else {
            if ($validityTime) {
                $hashData = [
                    $serviceId,
                    $orderId,
                    $amount,
                    $gatewayId,
                    $currency,
                    $customerEmail,
                    $validityTime,
                    $sharedKey,
                ];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params = [
                    'ServiceID' => $serviceId,
                    'OrderID' => $orderId,
                    'Amount' => $amount,
                    'GatewayID' => $gatewayId,
                    'Currency' => $currency,
                    'CustomerEmail' => $customerEmail,
                    'ValidityTime' => $validityTime,
                    'Hash' => $hashLocal,
                ];
            } else {
                if ($automatic === true && $cardGateway == $gatewayId) {
                    $hashData = [
                        $serviceId,
                        $orderId,
                        $amount,
                        $gatewayId,
                        $currency,
                        $customerEmail,
                        self::IFRAME_GATEWAY_ID,
                        $sharedKey,
                    ];
                    $hashLocal = $this->helper->generateAndReturnHash($hashData);

                    return [
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Amount' => $amount,
                        'GatewayID' => $gatewayId,
                        'Currency' => $currency,
                        'CustomerEmail' => $customerEmail,
                        'ScreenType' => self::IFRAME_GATEWAY_ID,
                        'Hash' => $hashLocal,
                    ];
                }

                /* Automatic payment */
                if ($autopayGateway == $gatewayId) {
                    $array = [
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Amount' => $amount,
                        'GatewayID' => $gatewayId,
                        'Currency' => $currency,
                        'CustomerEmail' => $customerEmail,
                    ];

                    /** @var Card $card */
                    $card = $this->cardCollectionFactory
                        ->create()
                        ->addFieldToFilter('card_index', $cardIndex)
                        ->addFieldToFilter('customer_id', $order->getCustomerId())
                        ->getFirstItem();

                    if ($cardIndex == -1 || $card == null) {
                        $array['RecurringAcceptanceState'] = 'ACCEPTED';
                        $array['RecurringAction'] = 'INIT_WITH_PAYMENT';
                    } else {
                        $array['RecurringAction'] = 'MANUAL';
                        $array['ClientHash'] = $card->getClientHash();
                    }

                    if ($automatic === true) {
                        $array['ScreenType'] = self::IFRAME_GATEWAY_ID;
                    }

                    $array = $this->sortParams($array);

                    $hashArray = array_values($array);
                    $hashArray[] = $sharedKey;

                    $array['Hash'] = $this->helper->generateAndReturnHash($hashArray);

                    return $array;
                }

                if ($automatic === true && $blikGateway == $gatewayId) {
                    $hashData = [
                        $serviceId,
                        $orderId,
                        $amount,
                        $gatewayId,
                        $currency,
                        $customerEmail,
                        $authorizationCode,
                        $sharedKey,
                    ];
                    $hashLocal = $this->helper->generateAndReturnHash($hashData);

                    return [
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Amount' => $amount,
                        'GatewayID' => $gatewayId,
                        'Currency' => $currency,
                        'CustomerEmail' => $customerEmail,
                        'AuthorizationCode' => $authorizationCode,
                        'Hash' => $hashLocal,
                    ];
                }

                if ($automatic === true && $gpayGateway == $gatewayId) {
                    $paymentToken = base64_encode($paymentToken);
                    $desc = '';
                    $hashData = [
                        $serviceId,
                        $orderId,
                        $amount,
                        $desc,
                        $gatewayId,
                        $currency,
                        $customerEmail,
                        $paymentToken,
                        $sharedKey,
                    ];
                    $hashLocal = $this->helper->generateAndReturnHash($hashData);

                    return [
                        'ServiceID' => $serviceId,
                        'OrderID' => $orderId,
                        'Amount' => $amount,
                        'Description' => $desc,
                        'GatewayID' => $gatewayId,
                        'Currency' => $currency,
                        'CustomerEmail' => $customerEmail,
                        'PaymentToken' => $paymentToken,
                        'Hash' => $hashLocal,
                    ];
                }

                $hashData = [$serviceId, $orderId, $amount, $gatewayId, $currency, $customerEmail, $sharedKey];
                $hashLocal = $this->helper->generateAndReturnHash($hashData);
                $params = [
                    'ServiceID' => $serviceId,
                    'OrderID' => $orderId,
                    'Amount' => $amount,
                    'GatewayID' => $gatewayId,
                    'Currency' => $currency,
                    'CustomerEmail' => $customerEmail,
                    'Hash' => $hashLocal,
                ];
            }
        }

        return $params;
    }

    public function sortParams(array $params)
    {
        $ordered = [];
        foreach ($this->orderParams as $value) {
            if (array_key_exists($value, $params)) {
                $ordered[$value] = $params[$value];
                unset($params[$value]);
            }
        }
        return $ordered + $params;
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
     *
     * @return string|null
     */
    public function processStatusPayment($response)
    {
        if ($this->_validAllTransaction($response)) {
            $transaction_xml = $response->transactions->transaction;
            return $this->updateStatusTransactionAndOrder($transaction_xml);
        }

        return null;
    }

    /**
     * Procesuje zapis/usunięcie automatycznej płatności
     *
     * @param \SimpleXMLElement $response
     */
    public function processRecurring($response)
    {
        $currency = $response->transaction->currency;

        if ($this->_validAllTransaction($response, $currency)) {
            switch ($response->getName()) {
                case 'recurringActivation':
                    return $this->saveCardData($response);
                case 'recurringDeactivation':
                    return $this->deleteCardData($response);
                default:
                    break;
            }
        }
    }

    private function saveCardData($data)
    {
        $orderId = $data->transaction->orderID;

        /** @var Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $customerId = $order->getCustomerId();

        $status = self::TRANSACTION_NOTCONFIRMED;
        $clientHash = (string)$data->recurringData->clientHash;

        if ($customerId) {
            $cardData = $data->cardData;

            $cardCollection = $this->cardCollectionFactory->create();
            $card = $cardCollection->getItemByColumnValue('card_index', (int)$cardData->index);

            if ($card === null) {
                $card = $this->cardFactory->create();
                $card->setData('card_index', (int)$cardData->index);
            }

            $card->setData('customer_id', $customerId);
            $card->setData('validity_year', $cardData->validityYear);
            $card->setData('validity_month', $cardData->validityMonth);
            $card->setData('issuer', $cardData->issuer);
            $card->setData('mask', $cardData->mask);
            $card->setData('client_hash', $clientHash);

            $this->cardResource->save($card);
            $status = self::TRANSACTION_CONFIRMED;
        }

        return $this->recurringResponse($clientHash, $status);
    }

    private function deleteCardData($data)
    {
        $clientHash = (string)$data->recurringData->clientHash;

        $cardCollection = $this->cardCollectionFactory->create();
        $card = $cardCollection->getItemByColumnValue('client_hash', $clientHash);

        if ($card !== null) {
            $this->cardResource->delete($card);
        }

        $this->recurringResponse($clientHash, self::TRANSACTION_CONFIRMED);
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     *
     * @param $response
     * @param string $currency
     *
     * @return bool
     */
    public function _validAllTransaction($response, $currency = null)
    {
        if ($currency === null) {
            $currency = $response->transactions->transaction->currency;
        }

        $serviceId      = $this->_scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
        $sharedKey      = $this->_scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");
        $hashSeparator  = $this->_scopeConfig->getValue("payment/bluepayment/hash_separator");
        $hashAlgorithm  = $this->_scopeConfig->getValue("payment/bluepayment/hash_algorithm");

        if ($serviceId != $response->serviceID) {
            return false;
        }

        $this->checkHashArray = [];
        $hash = (string)$response->hash;
        $response->hash = null;

        $this->_checkInList($response);
        $this->checkHashArray[] = $sharedKey;

        return hash($hashAlgorithm, implode($hashSeparator, $this->checkHashArray)) == $hash;
    }

    /**
     * Sprawdza czy można zmienić status zamówienia oraz czy nie zostało już opłacone.
     *
     * @param object $order
     *
     * @return boolean
     */
    public function isOrderChangable($order)
    {
        $status = $order->getStatus();
        $unchangeableStatuses = explode(
            ',',
            $this->_scopeConfig->getValue("payment/bluepayment/unchangeable_statuses")
        );

        if (in_array($status, $unchangeableStatuses)) {
            return false;
        }

        $statusAcceptPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_accept_payment');
        if ($statusAcceptPayment == '') {
            $statusAcceptPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
        }

        $alreadyPaidBefore = false;
        $orderStatusHistory = $order->getAllStatusHistory();
        foreach ($orderStatusHistory as $historyStatus) {
            if ($historyStatus->getStatus() == $statusAcceptPayment) {
                if ($order->getTotalDue() == 0) {
                    $alreadyPaidBefore = true;
                }
            }
        }

        return $alreadyPaidBefore == false;
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     *
     * @return string
     * @throws \Exception
     */
    protected function updateStatusTransactionAndOrder($transaction)
    {
        $paymentStatus = (string)$transaction->paymentStatus;

        $remoteId = $transaction->remoteID;
        $orderId = $transaction->orderID;
        $transactionAmount = number_format(round($transaction->amount, 2), 2, '.', '');
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $currency = $transaction->currency;

        $this->saveTransactionResponse($transaction);

        /**
         * @var \Magento\Sales\Model\Order\Payment $orderPayment
         */
        $orderPayment = $order->getPayment();
        $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');
        $amount = number_format(round($order->getGrandTotal(), 2), 2, '.', '');
        $transactionAmount = $amount;

        $orderStatusWaitingState = Order::STATE_PENDING_PAYMENT;

        $statusWaitingPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_waiting_payment');
        if ($statusWaitingPayment != '') {
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
        $statusAcceptPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_accept_payment');
        if ($statusAcceptPayment != '') {
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
        $statusErrorPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_error_payment');
        if ($statusErrorPayment != '') {
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
            if ($this->isOrderChangable($order) && $orderPaymentState != $paymentStatus) {
                $orderComment =
                    '[BM] Transaction ID: ' . (string)$remoteId
                    . ' | Amount: ' . $transactionAmount
                    . ' | Status: ' . $paymentStatus;
                switch ($paymentStatus) {
                    case self::PAYMENT_STATUS_PENDING:
                        if ($paymentStatus != $orderPaymentState) {
                            $transaction = $orderPayment->setTransactionId((string)$remoteId);
                            $transaction->prependMessage('[' . self::PAYMENT_STATUS_PENDING . ']');
                            $transaction->setIsTransactionPending(true);
                            $transaction->save();

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
                            $order
                                ->setState($orderStatusErrorState)
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
                $orderComment =
                    '[BM] Transaction ID: ' . (string)$remoteId
                    . ' | Amount: ' . $transactionAmount
                    . ' | Status: ' . $paymentStatus . ' [IGNORED]';

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
            return $this->returnConfirmation($order, self::TRANSACTION_CONFIRMED);
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
                $this->checkHashArray[] = $row;
            }
        }
    }

    /**
     * Potwierdzenie w postaci xml o prawidłowej/nieprawidłowej transakcji
     *
     * @param Order $order
     * @param string $confirmation
     *
     * @return string
     */
    public function returnConfirmation($order, $confirmation)
    {
        $currency = $order->getOrderCurrencyCode();

        $serviceId = $this->_scopeConfig->getValue("payment/bluepayment/" . strtolower($currency) . "/service_id");
        $sharedKey = $this->_scopeConfig->getValue("payment/bluepayment/" . strtolower($currency) . "/shared_key");
        $hashData = [$serviceId, $order->getId(), $confirmation, $sharedKey];
        $hashConfirmation = $this->helper->generateAndReturnHash($hashData);

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $confirmationList = $dom->createElement('confirmationList');

        $domServiceID = $dom->createElement('serviceID', $serviceId);
        $confirmationList->appendChild($domServiceID);

        $transactionsConfirmations = $dom->createElement('transactionsConfirmations');
        $confirmationList->appendChild($transactionsConfirmations);

        $domTransactionConfirmed = $dom->createElement('transactionConfirmed');
        $transactionsConfirmations->appendChild($domTransactionConfirmed);

        $domOrderID = $dom->createElement('orderID', $order->getId());
        $domTransactionConfirmed->appendChild($domOrderID);

        $domConfirmation = $dom->createElement('confirmation', $confirmation);
        $domTransactionConfirmed->appendChild($domConfirmation);

        $domHash = $dom->createElement('hash', $hashConfirmation);
        $confirmationList->appendChild($domHash);

        $dom->appendChild($confirmationList);

        return $dom->saveXML();
    }

    private function recurringResponse($clientHash, $status)
    {
        $serviceId        = $this->_scopeConfig->getValue("payment/bluepayment/pln/service_id");
        $sharedKey        = $this->_scopeConfig->getValue("payment/bluepayment/pln/shared_key");
        $hashData = [$serviceId, $clientHash, $status, $sharedKey];
        $hashConfirmation = $this->helper->generateAndReturnHash($hashData);

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $confirmationList = $dom->createElement('confirmationList');

        $domServiceID = $dom->createElement('serviceID', $serviceId);
        $confirmationList->appendChild($domServiceID);

        $recurringConfirmations = $dom->createElement('recurringConfirmations');
        $confirmationList->appendChild($recurringConfirmations);

        $recurringConfirmed = $dom->createElement('recurringConfirmed');
        $recurringConfirmations->appendChild($recurringConfirmed);

        $clientHash = $dom->createElement('clientHash', $clientHash);
        $recurringConfirmed->appendChild($clientHash);

        $domConfirmation = $dom->createElement('confirmation', $status);
        $recurringConfirmed->appendChild($domConfirmation);

        $domHash = $dom->createElement('hash', $hashConfirmation);
        $confirmationList->appendChild($domHash);

        $dom->appendChild($confirmationList);

        return $dom->saveXML();
    }

    /**
     * @param $transactionResponse
     */
    private function saveTransactionResponse($transactionResponse)
    {
        /** @var Transaction $transaction */
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
