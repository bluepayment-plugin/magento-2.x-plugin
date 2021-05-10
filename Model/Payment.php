<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Block\Info;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Helper\Refunds;
use BlueMedia\BluePayment\Logger\Logger as BMLogger;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory as GatewayFactory;
use DOMDocument;
use Exception;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
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
    const TRANSACTION_CONFIRMED = 'CONFIRMED';
    const TRANSACTION_NOTCONFIRMED = 'NOTCONFIRMED';

    const QUOTE_PREFIX = 'QUOTE_';

    /** @var string[] */
    private static $orderParams = [
        'ServiceID',
        'OrderID',
        'Amount',
        'GatewayID',
        'Currency',
        'CustomerEmail',
        'Language',
        'CustomerIP',
        'Title',
        'Products',
        'ValidityTime',
        'LinkValidityTime',
        'ReturnURL',
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
     * Unikatowy wewnętrzny identyfikator metody płatności
     *
     * @var string [a-z0-9_]
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Blok z formularza płatności
     *
     * @var string
     */
    protected $_formBlockType = Form::class;

    /** @var string */
    protected $_infoBlockType = Info::class;

    /**
     * Czy ta opcja płatności może być pokazywana na stronie
     * płatności w zakupach typu 'checkout' ?
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Czy ta metoda płatności jest bramką (online auth/charge) ?
     *
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Możliwość użycia formy płatności z panelu administracyjnego
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Możliwość zwrotu on-line
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Możliwość częściowego zwrotu
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Czy wymagana jest inicjalizacja ?
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    protected $_canOrder = true;
    protected $_canAuthorize = false;
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

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var OrderPaymentRepositoryInterface */
    private $paymentRepository;

    /** @var Config */
    private $orderConfig;

    /** @var GatewayFactory */
    private $gatewayFactory;

    /** @var Refunds */
    private $refunds;

    /** @var OrderCollectionFactory */
    private $orderCollectionFactory;

    /** @var string */
    private $title;

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
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param Config $orderConfig
     * @param GatewayFactory $gatewayFactory
     * @param Refunds $refunds
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
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
        OrderCollectionFactory $orderCollectionFactory,
        EncryptorInterface $encryptor,
        Curl $curl,
        BMLogger $bmLogger,
        Collection $collection,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        Config $orderConfig,
        GatewayFactory $gatewayFactory,
        Refunds $refunds,
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
        $this->orderCollectionFactory = $orderCollectionFactory;

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
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderConfig = $orderConfig;
        $this->gatewayFactory = $gatewayFactory;
        $this->refunds = $refunds;
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
        $testMode = $this->_scopeConfig->getValue(
            'payment/bluepayment/test_mode',
            ScopeInterface::SCOPE_STORE
        );

        if ($testMode) {
            return $this->_scopeConfig->getValue(
                'payment/bluepayment/test_address_url',
                ScopeInterface::SCOPE_STORE
            );
        }

        return $this->_scopeConfig->getValue(
            'payment/bluepayment/prod_address_url',
            ScopeInterface::SCOPE_STORE
        );
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
     * @param bool $backUrl
     * @return string[]
     */
    public function getFormRedirectFields(
        $order,
        $gatewayId = 0,
        $automatic = false,
        $authorizationCode = '',
        $paymentToken = '',
        $cardIndex = -1,
        $backUrl = false
    ) {
        $orderId       = $order->getRealOrderId();
        $amount        = number_format(round($order->getGrandTotal(), 2), 2, '.', '');
        $currency      = $order->getOrderCurrencyCode();

        // Config
        $serviceId     = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );
        $sharedKey     = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE
        );

        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();
        $validityTime = $this->getTransactionLifeHours();
        $language = $this->getLanguage($order);

        $params = [
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
            'Language' => $language,
        ];

        /* Ustawiona ważność linku */
        if ($validityTime) {
            $params['LinkValidityTime'] = $validityTime;
            $params['ValidityTime'] = $validityTime;
        }

        /* Wybrana bramka płatnicza */
        if ($gatewayId !== 0) {
            $params['GatewayID'] = $gatewayId;
        }

        if ($backUrl !== false) {
            $params['ReturnURL'] = $backUrl;
        }

        if ($automatic === true) {
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

        /* Płatność automatyczna kartowa */
        if (ConfigProvider::AUTOPAY_GATEWAY_ID == $gatewayId) {
            $params = $this->autopayGateway($params, $automatic, $customerId, $cardIndex);
        }

        $hashArray = array_values(self::sortParams($params));
        $hashArray[] = $sharedKey;

        $params['Hash'] = $this->helper->generateAndReturnHash($hashArray);

        return $params;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public static function sortParams(array $params)
    {
        $ordered = [];
        foreach (self::$orderParams as $value) {
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
        $hours = (int) $this->getConfigData('transaction_life_hours');

        if ($hours && $hours >= 1 && $hours <= 720) {
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
            return $this->updateStatusTransactionAndOrder($transaction_xml, $response->serviceID);
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
                    if ($this->_scopeConfig->getValue(
                        'payment/bluepayment/' . strtolower($c) . '/service_id',
                            ScopeInterface::SCOPE_STORE
                    ) == $response->serviceID) {
                        $currency = $c;
                        break;
                    }
                }
            }
        }

        $serviceId      = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );
        $sharedKey      = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE
        );
        $hashSeparator  = $this->_scopeConfig->getValue(
            'payment/bluepayment/hash_separator',
            ScopeInterface::SCOPE_STORE
        );
        $hashAlgorithm  = $this->_scopeConfig->getValue(
            'payment/bluepayment/hash_algorithm',
            ScopeInterface::SCOPE_STORE
        );

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
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param SimpleXMLElement $payment
     *
     * @param int $serviceId
     * @return string
     */
    protected function updateStatusTransactionAndOrder(SimpleXMLElement $payment, $serviceId = 0)
    {
        $paymentStatus = (string)$payment->paymentStatus;

        $remoteId = $payment->remoteID;
        $orderId = $payment->orderID;
        $gatewayId = $payment->gatewayID;

        $gateway = $this->gatewayFactory->create()
            ->addFieldToFilter('gateway_service_id', $serviceId)
            ->addFieldToFilter('gateway_id', $gatewayId)
            ->getFirstItem();

        $this->saveTransactionResponse($payment);

        $unchangeableStatuses = explode(',', $this->_scopeConfig->getValue(
            'payment/bluepayment/unchangeable_statuses',
            ScopeInterface::SCOPE_STORE
        ));

        $statusAcceptPayment = $this->_scopeConfig->getValue(
            'payment/bluepayment/status_accept_payment',
            ScopeInterface::SCOPE_STORE
        );
        if ($statusAcceptPayment == '') {
            $statusAcceptPayment = $this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING);
        }

        switch ($paymentStatus) {
            case self::PAYMENT_STATUS_SUCCESS:
                $state = Order::STATE_PROCESSING;
                $statusKey = 'status_accept_payment';
                break;
            case self::PAYMENT_STATUS_FAILURE:
                $state = Order::STATE_CANCELED;
                $statusKey = 'status_error_payment';
                break;
            case self::PAYMENT_STATUS_PENDING:
            default:
                $state = Order::STATE_PENDING_PAYMENT;
                $statusKey = 'status_waiting_payment';
                break;
        }

        $status = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . $statusKey,
            ScopeInterface::SCOPE_STORE
        );

        if ($status != '') {
            foreach ($this->statusCollectionFactory->create()->joinStates() as $s) {
                /** @var Status $s */
                if ($s->getStatus() == $status) {
                    $state = $s->getState();
                }
            }
        } else {
            $status = $this->orderConfig->getStateDefaultStatus($state);
        }

        // Multishipping
        if (substr($orderId, 0, strlen(Payment::QUOTE_PREFIX)) === Payment::QUOTE_PREFIX) {
            $quoteId = substr($orderId, strlen(Payment::QUOTE_PREFIX));

            /** @var DataObject|Order[] $orders */
            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('quote_id', $quoteId)
                ->load();

            $orderIds = [];
            foreach ($orders as $order) {
                $orderIds[] = $order->getIncrementId();
            }

            $this->bmLooger->info('PAYMENT:' . __LINE__, [
                'quoteId' => (string)$quoteId,
                'orderIds' => $orderIds
            ]);
        } else {
            /** @var Order[] $orders */
            $orders = [$this->orderFactory->create()->loadByIncrementId($orderId)];
        }

        $time1 = microtime(true);
        $orderPaymentState = null;
        $confirmed = true;

        foreach ($orders as $order) {
            /** @var Order\Payment|OrderPaymentInterface|null $orderPayment */
            $orderPayment = $order->getPayment();

            if ($orderPayment === null || $orderPayment->getMethod() != self::METHOD_CODE) {
                continue;
            }

            /** @var string $orderPaymentState */
            $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');
            $amount = $order->getGrandTotal();
            $formattedAmount = number_format(round($amount, 2), 2, '.', '');

            $changable = true;
            if (in_array($order->getStatus(), $unchangeableStatuses)) {
                $changable = false;
            }
            foreach ($order->getAllStatusHistory() as $historyStatus) {
                if ($historyStatus->getStatus() == $statusAcceptPayment && $order->getTotalDue() == 0) {
                    $changable = false;
                }
            }

            try {
                if ($changable && $orderPaymentState != $paymentStatus) {
                    $orderComment =
                        '[BM] Transaction ID: ' . (string)$remoteId
                        . ' | Amount: ' . $formattedAmount
                        . ' | Status: ' . $paymentStatus;

                    $order->setState($state);
                    $order->addStatusToHistory($status, $orderComment, false);
                    $order->setBlueGatewayId((int) $gatewayId);
                    $order->setPaymentChannel($gateway->getData('gateway_name'));

                    $orderPayment->setTransactionId((string)$remoteId);
                    $orderPayment->prependMessage('[' . $paymentStatus . ']');
                    $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
                    $orderPayment->setAdditionalInformation('bluepayment_gateway', (int)$gatewayId);

                    switch ($paymentStatus) {
                        case self::PAYMENT_STATUS_PENDING:
                            $orderPayment->setIsTransactionPending(true);
                            break;
                        case self::PAYMENT_STATUS_SUCCESS:
                            $orderPayment->registerCaptureNotification($amount);
                            $orderPayment->setIsTransactionApproved(true);
                            $orderPayment->setIsTransactionClosed(true);
                            break;
                        default:
                            break;
                    }

                    $this->paymentRepository->save($orderPayment);
                    $this->orderRepository->save($order);
                } else {
                    $orderComment =
                        '[BM] Transaction ID: ' . (string)$remoteId
                        . ' | Amount: ' . $amount
                        . ' | Status: ' . $paymentStatus . ' [IGNORED]';

                    $order->addStatusToHistory($order->getStatus(), $orderComment, false);
                    $this->orderRepository->save($order);
                }

                if (!$order->getEmailSent()) {
                    $this->sender->send($order);
                }
            } catch (Exception $e) {
                $this->bmLooger->critical($e);
                $confirmed = false;
            }
        }

        $time2 = microtime(true);

        $this->bmLooger->info('PAYMENT:' . __LINE__, [
            'orderID' => (string)$orderId,
            'paymentStatus' => $paymentStatus,
            'orderPaymentState' => $orderPaymentState,
            'time' => round(($time2 - $time1) * 1000, 2) . ' ms'
        ]);

        if ($orderPaymentState === null) {
            $confirmed = false;
        }

        return $this->returnConfirmation($order, ($confirmed) ? self::TRANSACTION_CONFIRMED : self::TRANSACTION_NOTCONFIRMED);
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

        $serviceId = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );
        $sharedKey = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE
        );
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
        $serviceId        = $this->_scopeConfig->getValue(
            'payment/bluepayment/pln/service_id',
            ScopeInterface::SCOPE_STORE
        );
        $sharedKey        = $this->_scopeConfig->getValue(
            'payment/bluepayment/pln/shared_key',
            ScopeInterface::SCOPE_STORE
        );
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
        $transaction->setOrderId((string)$transactionResponse->orderID)
            ->setRemoteId((string)$transactionResponse->remoteID)
            ->setAmount((float)$transactionResponse->amount)
            ->setCurrency((string)$transactionResponse->currency)
            ->setGatewayId((int)$transactionResponse->gatewayID)
            ->setPaymentDate((string)$transactionResponse->paymentDate)
            ->setPaymentStatus((string)$transactionResponse->paymentStatus)
            ->setPaymentStatusDetails((string)$transactionResponse->paymentStatusDetails);

        try {
            $this->transactionRepository->save($transaction);
        } catch (CouldNotSaveException $e) {
            $this->bmLooger->error(__('Could not save BluePayment Transaction entity: ') . $transaction->toJson());
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

    public function order(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        $ip = $order->getRemoteIp();

        $this->bmLooger->info('PAYMENT:' . __LINE__, [
            'ip' => $ip,
            'incrementId' => $order->getIncrementId()
        ]);

        $createOrder = $payment->getAdditionalInformation('create_payment') === true || $order->getRemoteIp() === null;

        /** Orders from admin panel has empty remote ip */
        if ($createOrder) {
            $backUrl = $payment->getAdditionalInformation('back_url');

            $params = $this->getFormRedirectFields(
                $order,
                0,
                false,
                '',
                '',
                -1,
                $backUrl
            );
            $url = $this->getUrlGateway();

            $response = $this->sendRequest($params, $url);
            $remoteId = $response->traansactionId;
            $redirectUrl = $response->redirecturl;
            $orderStatus = $response->status;

            $order->getPayment()
                ->setAdditionalInformation('bluepayment_redirect_url', (string) $redirectUrl);

            $unchangeableStatuses = explode(
                ',',
                $this->_scopeConfig->getValue(
                    'payment/bluepayment/unchangeable_statuses',
                    ScopeInterface::SCOPE_STORE
                )
            );
            $statusWaitingPayment = $this->_scopeConfig->getValue(
                'payment/bluepayment/status_waiting_payment',
                ScopeInterface::SCOPE_STORE
            );

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
                    . ' | URL: ' . $redirectUrl;

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
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        try {
            $order = $payment->getOrder();
            $transaction = $this->transactionRepository->getSuccessTransactionFromOrder($order);
            $result = $this->refunds->makeRefund($order, $transaction, $amount, false);

            if (isset($result['error']) && $result['error'] === true) {
                $payment->setIsTransactionDenied(true);

                throw new \Magento\Framework\Exception\LocalizedException(
                    __($result['message'])
                );
            } else {
                $payment->setIsTransactionApproved(true);
            }
        } catch (InputException $e) {
            $payment->setIsTransactionDenied(true);

            throw new \Magento\Framework\Exception\LocalizedException(
                __('Order ID is mandatory.')
            );
        } catch (EmptyRemoteIdException $e) {
            $payment->setIsTransactionDenied(true);

            throw new \Magento\Framework\Exception\LocalizedException(
                __('There is no succeeded payment transaction.')
            );
        } catch (NoSuchEntityException $e) {
            $payment->setIsTransactionDenied(true);

            throw new \Magento\Framework\Exception\LocalizedException(
                __('There is no such order.')
            );
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

    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     * @deprecated 100.2.0
     */
    public function getConfigData($field, $storeId = null)
    {
        $code = $this->getCode();

        if (false === strpos($code, 'bluepayment_')) {
            return parent::getConfigData($field);
        }

        if ($field === 'order_place_redirect_url') {
            return $this->getOrderPlaceRedirectUrl();
        }

        if ($field === 'sort_order') {
            return parent::getConfigData('sort_order');
        }

        $path = 'payment/bluepayment/' . $field;
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     * @deprecated 100.2.0
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    public function getTitle()
    {
        if (false !== strpos($this->getCode(), 'bluepayment_')) {
            return $this->title;
        }

        return parent::getTitle();
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    private function getLanguage(Order $order)
    {
        $code = $this->_scopeConfig
            ->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

        $locales = [
            'pl_' => 'PL', // polski
            'en_' => 'EN', // angielski
            'de_' => 'DE', // niemiecki
            'cs_' => 'CS', // czeski
            'fr_' => 'FR', // francuski
            'it_' => 'IT', // włoski
            'es_' => 'ES', // hiszpański
            'sk_' => 'SK', // słowacki
            'ro_' => 'RO', // rumuński
            'uk_' => 'UK', // ukraiński
            'hu_' => 'HU', // węgierski
        ];

        $prefix = substr($code, 0, 3);

        if (isset($locales[$prefix])) {
            return $locales[$prefix];
        }

        return 'PL';
    }
}
