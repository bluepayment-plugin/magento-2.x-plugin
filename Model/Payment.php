<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Logger\Logger as BMLogger;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use DOMDocument;
use Exception;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use SimpleXMLElement;
use Magento\Framework\HTTP\Client\Curl;

/**
 * BluePayment class
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

    /** @var string[] */
    private $orderParams = [
        'ServiceID',
        'OrderID',
        'Amount',
        'GatewayID',
        'Currency',
        'CustomerEmail',
        'CustomerIP',
        'Title',
        'ValidityTime',
        'RecurringAcceptanceState',
        'RecurringAction',
        'ClientHash',
        'AuthorizationCode',
        'ScreenType',
        'PaymentToken',
    ];

    /**
     * @var string[]
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
    protected $_formBlockType = Form::class;

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
    protected $_canUseInternal = true;

    /**
     * Czy wymagana jest inicjalizacja ?
     *
     * @var boolean
     */
    protected $_isInitializeNeeded = false;

    protected $_canAuthorize = true;

    protected $_canCapture = false;

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

    /** @var Curl */
    private $curl;

    /** @var BMLogger */
    private $bmLooger;

    /** @var Collection */
    private $collection;

    /** @var Data */
    private $helper;

    /** @var UrlInterface */
    private $url;

    /** @var OrderSender */
    private $sender;

    /** @var CollectionFactory */
    private $statusCollectionFactory;

    /** @var TransactionFactory */
    private $transactionFactory;

    /** @var TransactionRepositoryInterface */
    private $transactionRepository;

    /**
     * Payment constructor.
     *
     * @param CollectionFactory $statusCollectionFactory
     * @param OrderSender $orderSender
     * @param Data $helper
     * @param UrlInterface $url
     * @param OrderFactory $orderFactory
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentData $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param TransactionFactory $transactionFactory
     * @param TransactionRepositoryInterface $transactionRepository
     * @param CardFactory $cardFactory
     * @param CardCollectionFactory $cardCollectionFactory
     * @param CardResource $cardResource
     * @param EncryptorInterface $encryptor
     * @param Curl $curl
     * @param BMLogger $bmLogger
     * @param Collection $collection
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
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
        Curl $curl,
        BMLogger $bmLogger,
        Collection $collection,
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
        $this->curl = $curl;
        $this->bmLooger = $bmLogger;
        $this->collection = $collection;

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
     * @param int $gatewayId
     * @param bool $automatic
     * @param string $authorizationCode
     * @param string $paymentToken
     * @param int $cardIndex
     *
     * @return string[]
     */
    public function getFormRedirectFields(
        $order,
        $gatewayId = 0,
        $automatic = false,
        $authorizationCode = '',
        $paymentToken = '',
        $cardIndex = -1
    ) {
        $orderId       = $order->getRealOrderId();
        $amount        = number_format(round($order->getGrandTotal(), 2), 2, '.', '');
        $currency      = $order->getOrderCurrencyCode();

        // Config
        $serviceId     = $this->_scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/service_id");
        $sharedKey     = $this->_scopeConfig->getValue("payment/bluepayment/".strtolower($currency)."/shared_key");

        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();
        $validityTime = $this->getTransactionLifeHours();

        $params = [
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
        ];

        /* Ustawiona ważność linku */
        if ($validityTime) {
            $params['ValidityTime'] = $validityTime;
        }

        /* Wybrana bramka płatnicza */
        if ($gatewayId !== 0) {
            $params['GatewayID'] = $gatewayId;
        }

        /* Płatność iFrame */
        if ($automatic === true && ConfigProvider::IFRAME_GATEWAY_ID == $gatewayId) {
            switch ($gatewayId) {
                case ConfigProvider::IFRAME_GATEWAY_ID:
                    $params['ScreenType'] = self::IFRAME_GATEWAY_ID;
                    break;
                case ConfigProvider::BLIK_GATEWAY_ID == $gatewayId:
                    $params['AuthorizationCode'] = $authorizationCode;
                    break;
                case ConfigProvider::GPAY_GATEWAY_ID == $gatewayId:
                    $params['Description'] = '';
                    $params['PaymentToken'] = base64_encode($paymentToken);
                    break;
            }
        }

        /* Automatic payment */
        if (ConfigProvider::AUTOPAY_GATEWAY_ID == $gatewayId) {
            $params = $this->autopayGateway($params, $automatic, $customerId, $cardIndex);
        }

        $hashArray = array_values($this->sortParams($params));
        $hashArray[] = $sharedKey;

        $params['Hash'] = $this->helper->generateAndReturnHash($hashArray);

        return $params;
    }

    /**
     * @param array $params
     *
     * @return array
     */
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
            $time = strtotime("+" . $hours . " hour");

            if ($time) {
                return date('Y-m-d H:i:s', $time);
            }

            return date('Y-m-d H:i:s', time() + $hours*3600);
        }

        return self::DEFAULT_TRANSACTION_LIFE_HOURS;
    }

    /**
     * Ustawia odpowiedni status transakcji/płatności zgodnie z uzyskaną informacją
     * z akcji 'statusAction'
     *
     * @param SimpleXMLElement $response
     *
     * @return string|null
     */
    public function processStatusPayment(SimpleXMLElement $response)
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
     * @param SimpleXMLElement $response
     *
     * @return string|null
     */
    public function processRecurring($response)
    {
        $currency = $response->transaction->currency;

        try {
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
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * @param SimpleXMLElement $data
     *
     * @return string
     * @throws AlreadyExistsException
     */
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

    /**
     * @param SimpleXMLElement $data
     *
     * @return string
     * @throws Exception
     */
    private function deleteCardData($data)
    {
        $clientHash = (string)$data->recurringData->clientHash;

        /** @var CardResource\Collection $cardCollection */
        $cardCollection = $this->cardCollectionFactory->create();

        /** @var Card $card */
        $card = $cardCollection->getItemByColumnValue('client_hash', $clientHash);

        if ($card !== null) {
            $this->cardResource->delete($card);
        }

        return $this->recurringResponse($clientHash, self::TRANSACTION_CONFIRMED);
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     *
     * @param SimpleXMLElement $response
     * @param string $currency
     *
     * @return bool
     */
    public function _validAllTransaction(SimpleXMLElement $response, $currency = null)
    {
        if ($currency === null) {
            if (property_exists($response, 'transactions')) {
                // If we have transactions element
                $currency = $response->transactions->transaction->currency;
            } else {
                // Otherwise - find correct currency
                $currencies = \BlueMedia\BluePayment\Helper\Gateways::$currencies;

                foreach ($currencies as $c) {
                    if ($this->_scopeConfig->getValue("payment/bluepayment/".strtolower($c)."/service_id")
                        == $response->serviceID) {
                        $currency = $c;
                        break;
                    }
                }
            }
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
        $response->hash = '';

        $this->_checkInList($response);
        $this->checkHashArray[] = $sharedKey;

        return hash($hashAlgorithm, implode($hashSeparator, $this->checkHashArray)) == $hash;
    }

    /**
     * Sprawdza czy można zmienić status zamówienia oraz czy nie zostało już opłacone.
     *
     * @param Order $order
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
     * @param SimpleXMLElement $transaction
     *
     * @return string
     */
    protected function updateStatusTransactionAndOrder(SimpleXMLElement $transaction)
    {
        $paymentStatus = (string)$transaction->paymentStatus;

        $remoteId = $transaction->remoteID;
        $orderId = $transaction->orderID;
        /** @var Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        $this->saveTransactionResponse($transaction);

        /** @var Order\Payment|mixed|null $orderPayment */
        $orderPayment = $order->getPayment();

        if ($orderPayment === null) {
            return $this->returnConfirmation($order, self::TRANSACTION_NOTCONFIRMED);
        }

        /** @var string $orderPaymentState */
        $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');
        $amount = $order->getGrandTotal();
        $formaattedAmount = number_format(round($amount, 2), 2, '.', '');

        try {
            if ($this->isOrderChangable($order) && $orderPaymentState != $paymentStatus) {
                $orderComment =
                    '[BM] Transaction ID: ' . (string)$remoteId
                    . ' | Amount: ' . $formaattedAmount
                    . ' | Status: ' . $paymentStatus;

                switch ($paymentStatus) {
                    case self::PAYMENT_STATUS_PENDING:
                        $this->processPending(
                            $order,
                            $paymentStatus,
                            $orderPaymentState,
                            $orderPayment,
                            $remoteId,
                            $orderComment
                        );
                        break;
                    case self::PAYMENT_STATUS_SUCCESS:
                        $this->processSuccess(
                            $order,
                            $orderPayment,
                            $remoteId,
                            $amount,
                            $orderComment
                        );
                        break;
                    case self::PAYMENT_STATUS_FAILURE:
                        $this->processFailure(
                            $order,
                            $orderPaymentState,
                            $paymentStatus,
                            $orderComment
                        );
                        break;
                    default:
                        break;
                }
            } else {
                $orderComment =
                    '[BM] Transaction ID: ' . (string)$remoteId
                    . ' | Amount: ' . $amount
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
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }

        return $this->returnConfirmation($order, self::TRANSACTION_NOTCONFIRMED);
    }

    /**
     * @param object|array $list
     *
     * @return void
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

        $dom = new DOMDocument('1.0', 'UTF-8');

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

        $xml = $dom->saveXML();

        return $xml ? $xml : '';
    }

    /**
     * @param string $clientHash
     * @param string $status
     *
     * @return string
     */
    private function recurringResponse($clientHash, $status)
    {
        $serviceId        = $this->_scopeConfig->getValue("payment/bluepayment/pln/service_id");
        $sharedKey        = $this->_scopeConfig->getValue("payment/bluepayment/pln/shared_key");
        $hashData = [$serviceId, $clientHash, $status, $sharedKey];
        $hashConfirmation = $this->helper->generateAndReturnHash($hashData);

        $dom = new DOMDocument('1.0', 'UTF-8');

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

        $xml = $dom->saveXML();

        return $xml ? $xml : '';
    }

    /**
     * @param SimpleXMLElement $transactionResponse
     *
     * @return void
     */
    private function saveTransactionResponse(SimpleXMLElement $transactionResponse)
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

    /**
     * @param array $params
     * @param bool $automatic
     * @param int $customerId
     * @param int $cardIndex
     *
     * @return array
     */
    private function autopayGateway(array $params, $automatic, $customerId, $cardIndex)
    {
        /** @var Card $card */
        $card = $this->cardCollectionFactory
            ->create()
            ->addFieldToFilter('card_index', (string) $cardIndex)
            ->addFieldToFilter('customer_id', (string) $customerId)
            ->getFirstItem();

        if ($cardIndex == -1 || $card == null) {
            $params['RecurringAcceptanceState'] = 'ACCEPTED';
            $params['RecurringAction'] = 'INIT_WITH_PAYMENT';
        } else {
            $params['RecurringAction'] = 'MANUAL';
            $params['ClientHash'] = $card->getClientHash();
        }

        if ($automatic === true) {
            $params['ScreenType'] = self::IFRAME_GATEWAY_ID;
        }
        return $params;
    }

    /**
     * @param Order $order
     * @param string $orderPaymentState
     * @param string $paymentStatus
     * @param string $orderComment
     *
     * @return void
     * @throws Exception
     */
    protected function processFailure(
        Order $order,
        $orderPaymentState,
        $paymentStatus,
        $orderComment
    ) {
        $orderStatusErrorState = Order::STATE_PENDING_PAYMENT;
        $statusErrorPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_error_payment');
        if ($statusErrorPayment != '') {
            foreach ($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var Status $status */
                if ($status->getStatus() == $statusErrorPayment) {
                    $orderStatusErrorState = $status->getState();
                }
            }
        } else {
            $statusErrorPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW);
        }

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
    }

    /**
     * @param Order $order
     * @param Order\Payment $orderPayment
     * @param string $remoteId
     * @param float $transactionAmount
     * @param string $orderComment
     *
     * @return void
     * @throws Exception
     */
    protected function processSuccess(
        Order $order,
        Order\Payment $orderPayment,
        $remoteId,
        $transactionAmount,
        $orderComment
    ) {
        $orderStatusAcceptState = Order::STATE_PROCESSING;
        $statusAcceptPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_accept_payment');
        if ($statusAcceptPayment != '') {
            foreach ($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var Status $status */
                if ($status->getStatus() == $statusAcceptPayment) {
                    $orderStatusAcceptState = $status->getState();
                }
            }
        } else {
            $statusAcceptPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
        }

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
    }

    /**
     * @param Order $order
     * @param string $paymentStatus
     * @param string $orderPaymentState
     * @param Order\Payment $orderPayment
     * @param string $remoteId
     * @param string $orderComment
     *
     * @return void
     * @throws Exception
     */
    protected function processPending(
        Order $order,
        $paymentStatus,
        $orderPaymentState,
        Order\Payment $orderPayment,
        $remoteId,
        $orderComment
    ) {
        $orderStatusWaitingState = Order::STATE_PENDING_PAYMENT;
        $statusWaitingPayment = $this->_scopeConfig->getValue('payment/bluepayment/status_waiting_payment');
        if ($statusWaitingPayment != '') {
            foreach ($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var Status $status */
                if ($status->getStatus() == $statusWaitingPayment) {
                    $orderStatusWaitingState = $status->getState();
                }
            }
        } else {
            $statusWaitingPayment = $order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT);
        }

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
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        $ip = $order->getRemoteIp();

        $this->bmLooger->info('PAYMENT:' . __LINE__, ['ip' => $ip]);

        /** Orders from admin panel has empty remote ip */
        if ($order->getRemoteIp() === null) {
            $params = $this->getFormRedirectFields($order);
            $url = $this->getUrlGateway();

            $response = $this->sendRequest($params, $url);
            $remoteId = $response->traansactionId;
            $redirecturl = $response->redirecturl;
            $orderStatus = $response->status;

            $unchangeableStatuses = explode(
                ',',
                $this->_scopeConfig->getValue("payment/bluepayment/unchangeable_statuses")
            );
            $statusWaitingPayment = $this->_scopeConfig->getValue("payment/bluepayment/status_waiting_payment");

            if ($statusWaitingPayment != '') {
                $statusCollection = $this->collection;
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
                $amount = $order->getGrandTotal();
                $formaattedAmount = number_format(round($amount, 2), 2, '.', '');

                $orderComment =
                    '[BM] Transaction ID: ' . (string)$remoteId
                    . ' | Amount: ' . $formaattedAmount
                    . ' | Status: ' . $orderStatus
                    . ' | URL: ' . $redirecturl;

                $order->setState($orderStatusWaitingState)
                    ->setStatus($statusWaitingPayment)
                    ->addStatusToHistory(
                        $statusWaitingPayment,
                        $orderComment,
                        false
                    )
                    ->save();
            }
        }

        return $this;
    }

    /**
     * @param string $urlGateway
     * @param array $params
     *
     * @return SimpleXMLElement|false
     */
    private function sendRequest($params, $urlGateway)
    {
        if (array_key_exists('ClientHash', $params)) {
            $this->curl->addHeader('BmHeader', 'pay-bm');
        } else {
            $this->curl->addHeader('BmHeader', 'pay-bm-continue-transaction-url');
        }

        $this->bmLooger->info('PAYMENT:' . __LINE__, ['params' => $params]);

        $this->curl->post($urlGateway, $params);
        $response = $this->curl->getBody();

        $this->bmLooger->info('PAYMENT:' . __LINE__, ['response' => $response]);
        $xml = simplexml_load_string($response);

        return $xml;
    }
}
