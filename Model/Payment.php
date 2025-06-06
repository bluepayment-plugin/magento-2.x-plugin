<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\GatewayRepositoryInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Block\Form;
use BlueMedia\BluePayment\Block\Info;
use BlueMedia\BluePayment\Exception\EmptyRemoteIdException;
use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Helper\Gateways;
use BlueMedia\BluePayment\Helper\Refunds;
use BlueMedia\BluePayment\Helper\Webapi;
use BlueMedia\BluePayment\Logger\Logger as BMLogger;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayFactory;
use DateTimeZone;
use DOMDocument;
use DOMException;
use Exception;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use SimpleXMLElement;

/**
 * BluePayment class
 */
class Payment extends AbstractMethod
{
    public const PLATFORM_NAME = 'Magento';

    public const METHOD_CODE = 'bluepayment';
    public const IFRAME_SCREEN_TYPE = 'IFRAME';
    public const SEPARATED_PREFIX_CODE = 'bluepayment_';

    /**
     * Stałe statusów płatności
     */
    public const PAYMENT_STATUS_PENDING = 'PENDING';
    public const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    public const PAYMENT_STATUS_FAILURE = 'FAILURE';

    /**
     * Stałe potwierdzenia autentyczności transakcji
     */
    public const TRANSACTION_CONFIRMED = 'CONFIRMED';
    public const TRANSACTION_NOTCONFIRMED = 'NOTCONFIRMED';

    public const QUOTE_PREFIX = 'QUOTE_';

    /** @var string[] */
    private static $orderParams = [
        'ServiceID', // 1
        'OrderID', // 2
        'Amount', // 3
        'GatewayID', // 5
        'Currency', // 6
        'CustomerEmail', // 7
        'Language', // 7
        'CustomerIP', // 13
        'Title', // 14
        'Products', // 16
        'CustomerPhone', // 17
        'ValidityTime', // 19
        'Nip', // 23
        'VerificationFName', // 25
        'VerificationLName', // 26
        'LinkValidityTime', // 34
        'RecurringAcceptanceState', // 35
        'RecurringAction', // 36
        'ClientHash', // 37
        'AuthorizationCode', // 40
        'ScreenType', // 41
        'ReturnURL', // 45
        'PaymentToken', // 47
        'DefaultRegulationAcceptanceState', // 51
        'DefaultRegulationAcceptanceID', // 52
        'DefaultRegulationAcceptanceTime', // 53
        'AccountHolderName', // 59
        'PlatformName', // 170
        'PlatformVersion', // 171
        'PlatformPluginVersion', // 172
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

    /**
     * @var bool
     */
    protected $_canOrder = true;

    protected $_canAuthorize = false;
    protected $_canCapture = false;

    /**
     * Is separated method?
     *
     * Currently used only for GraphQL integration.
     *
     * @var bool
     */
    private $isSeparated = false;

    /**
     * Related gateway (channel) model.
     *
     * Currently used only for GraphQL integration.
     *
     * @var null|Gateway
     */
    private $gatewayModel = null;

    /** @var OrderFactory */
    private $orderFactory;

    /** @var CardFactory */
    private $cardFactory;

    /** @var CardCollectionFactory */
    private $cardCollectionFactory;

    /** @var CardResource */
    private $cardResource;

    /** @var Curl */
    private $curl;

    /** @var BMLogger */
    private $bmLooger;

    /** @var Data */
    private $helper;

    /** @var UrlInterface */
    private $url;

    /** @var TransactionRepositoryInterface */
    private $transactionRepository;

    /** @var Refunds */
    private $refunds;

    /** @var string */
    private $title;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var GetStateForStatus */
    private $getStateForStatus;

    /** @var GetStoreByServiceId */
    private $getStoreByServiceId;

    /** @var GetTransactionLifetime */
    private $getTransactionLifetime;

    /** @var Metadata */
    private $metadata;

    /** @var GetPhoneForOrder */
    private $getPhoneForOrder;

    /** @var ProcessNotification */
    private $processNotification;

    /** @var CustomFieldResolver */
    private $customFieldResolver;

    /**
     * Payment constructor.
     *
     * @param  Data  $helper
     * @param  UrlInterface  $url
     * @param  OrderFactory  $orderFactory
     * @param  Context  $context
     * @param  Registry  $registry
     * @param  ExtensionAttributesFactory  $extensionFactory
     * @param  AttributeValueFactory  $customAttributeFactory
     * @param  PaymentData  $paymentData
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  Logger  $logger
     * @param  TransactionFactory  $transactionFactory
     * @param  TransactionRepositoryInterface  $transactionRepository
     * @param  CardFactory  $cardFactory
     * @param  CardCollectionFactory  $cardCollectionFactory
     * @param  CardResource  $cardResource
     * @param  OrderCollectionFactory  $orderCollectionFactory
     * @param  Curl  $curl
     * @param  BMLogger  $bmLogger
     * @param  OrderRepositoryInterface  $orderRepository
     * @param  GatewayFactory  $gatewayFactory
     * @param  Refunds  $refunds
     * @param  ConfigProvider  $configProvider
     * @param  Webapi  $webapi
     * @param  GetStateForStatus  $getStateForStatus
     * @param  GetStoreByServiceId  $getStoreByServiceId
     * @param  GetTransactionLifetime  $getTransactionLifetime
     * @param  Metadata  $metadata
     * @param  GetPhoneForOrder  $getPhoneForOrder
     * @param  SendConfirmationEmail  $sendConfirmationEmail
     * @param  ProcessNotification  $processNotification
     * @param  CustomFieldResolver  $customFieldResolver
     * @param  AbstractResource|null  $resource
     * @param  AbstractDb|null  $resourceCollection
     * @param  array  $data
     */
    public function __construct(
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
        Curl $curl,
        BMLogger $bmLogger,
        OrderRepositoryInterface $orderRepository,
        GatewayFactory $gatewayFactory,
        Refunds $refunds,
        ConfigProvider $configProvider,
        Webapi $webapi,
        GetStateForStatus $getStateForStatus,
        GetStoreByServiceId $getStoreByServiceId,
        GetTransactionLifetime $getTransactionLifetime,
        Metadata $metadata,
        GetPhoneForOrder $getPhoneForOrder,
        SendConfirmationEmail $sendConfirmationEmail,
        ProcessNotification $processNotification,
        CustomFieldResolver $customFieldResolver,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->url = $url;
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardResource = $cardResource;
        $this->curl = $curl;
        $this->bmLooger = $bmLogger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->gatewayFactory = $gatewayFactory;
        $this->refunds = $refunds;
        $this->configProvider = $configProvider;
        $this->webapi = $webapi;
        $this->getStateForStatus = $getStateForStatus;
        $this->getStoreByServiceId = $getStoreByServiceId;
        $this->getTransactionLifetime = $getTransactionLifetime;
        $this->metadata = $metadata;
        $this->getPhoneForOrder = $getPhoneForOrder;
        $this->sendConfirmationEmail = $sendConfirmationEmail;
        $this->processNotification = $processNotification;
        $this->customFieldResolver = $customFieldResolver;

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
    }

    /**
     * Zwraca adres url kontrolera do przekierowania po potwierdzeniu zamówienia
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->url->getUrl('/bluepayment/processing/create', ['_secure' => true]);
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
     * @param array $agreementsIds
     * @param bool $automatic
     * @param string $authorizationCode
     * @param string $paymentToken
     * @param int $cardIndex
     * @param ?string $backUrl
     *
     * @return string[]
     * @throws LocalizedException
     */
    public function getFormRedirectFields(
        Order $order,
        int $gatewayId = 0,
        array $agreementsIds = [],
        bool $automatic = false,
        string $authorizationCode = '',
        string $paymentToken = '',
        int $cardIndex = -1,
        string $backUrl = null
    ): array {
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
        $validityTime = $this->getTransactionLifetime($order);

        $locale = $this->_scopeConfig
            ->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE
            );
        $language = $this->helper->getLanguageFromLocale($locale);

        $params = [
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'Currency' => $currency,
            'CustomerEmail' => $order->getCustomerEmail(),
            'VerificationFName' => $order->getCustomerFirstname(),
            'VerificationLName' => $order->getCustomerLastname(),
            'Language' => $language,
            'PlatformName' => self::PLATFORM_NAME . ' ' . $this->metadata->getMagentoEdition(),
            'PlatformVersion' => $this->metadata->getMagentoVersion(),
            'PlatformPluginVersion' => $this->metadata->getModuleVersion(),
        ];

        /* Ustawiona ważność linku */
        if ($validityTime !== null) {
            $params['LinkValidityTime'] = $validityTime;
            $params['ValidityTime'] = $validityTime;
        }

        /* Wybrana bramka płatnicza */
        if ($gatewayId !== 0) {
            $params['GatewayID'] = $gatewayId;

            $params = $this->customFieldResolver->resolve(
                $gatewayId,
                $order,
                $params
            );
        }

        if ($backUrl !== null) {
            $params['ReturnURL'] = $backUrl;
        }

        if ($this->configProvider->isWithPhoneEnabled() && $phone = $this->getPhoneForOrder->execute($order)) {
            $params['CustomerPhone'] = $phone;
        }

        if ($automatic === true) {
            switch ($gatewayId) {
                case ConfigProvider::CARD_GATEWAY_ID:
                    $params['ScreenType'] = self::IFRAME_SCREEN_TYPE;
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
        if (ConfigProvider::ONECLICK_GATEWAY_ID == $gatewayId) {
            $params = $this->autopayGateway($params, $automatic, $customerId, $cardIndex);
        } else {
            $agreementId = reset($agreementsIds);

            if ($agreementId) {
                $params['DefaultRegulationAcceptanceState'] = 'ACCEPTED';
                $params['DefaultRegulationAcceptanceID'] = $agreementId;
                $params['DefaultRegulationAcceptanceTime'] = date('Y-m-d H:i:s');
            }
        }

        $hashArray = array_values(self::sortParams($params));
        $hashArray[] = $sharedKey;

        $params['Hash'] = $this->helper->generateAndReturnHash($hashArray);

        return $params;
    }

    /**
     * @param array $params
     * @return array
     */
    public static function sortParams(array $params): array
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
     * @param Order $order
     * @return ?string
     * @throws LocalizedException
     */
    private function getTransactionLifetime(Order $order): ?string
    {
        $lifetime = $this->getTransactionLifetime->getForOrder($order);

        if ($lifetime === true) {
            return null;
        }

        if ($lifetime === false) {
            throw new LocalizedException(__('Transaction is expired. Place order again.'));
        }

        $lifetime->setTimezone(new DateTimeZone('Europe/Warsaw'));
        return $lifetime->format('Y-m-d H:i:s');
    }

    /**
     * Ustawia odpowiedni status transakcji/płatności zgodnie z uzyskaną informacją z akcji 'statusAction'
     *
     * @param  SimpleXMLElement  $response
     *
     * @return string|null
     * @throws DOMException
     */
    public function processStatusPayment(SimpleXMLElement $response)
    {
        $serviceId = (string) $response->serviceID;
        [$store, $currency] = $this->getStoreByServiceId->execute($serviceId);

        if (! $store) {
            $this->bmLooger->error('PAYMENT: ' . __LINE__ . ' - Cannot find ServiceID', [
                'serviceID' => $serviceId,
            ]);

            return false;
        }

        if ($this->validAllTransaction($response, $store, $currency)) {
            $transaction = $response->transactions->transaction;
            return $this->updateStatusTransactionAndOrder(
                $transaction,
                $serviceId,
                (int) $store->getId()
            );
        }

        return null;
    }

    /**
     * Procesuje zapis/usunięcie automatycznej płatności
     *
     * @param  SimpleXMLElement  $response
     *
     * @return string|null
     */
    public function processRecurring(SimpleXMLElement $response)
    {
        $serviceId = (string) $response->serviceID;
        [$store, $currency] = $this->getStoreByServiceId->execute($serviceId);

        if (! $store) {
            $this->bmLooger->error('PAYMENT: ' . __LINE__ . ' - Cannot find ServiceID', [
                'serviceID' => $serviceId,
            ]);

            return false;
        }

        try {
            if ($this->validAllTransaction($response, $store, $currency)) {
                switch ($response->getName()) {
                    case 'recurringActivation':
                        return $this->saveCardData($response, $store);
                    case 'recurringDeactivation':
                        return $this->deleteCardData($response, $store);
                    default:
                        break;
                }
            }
        } catch (Exception $e) {
            $this->bmLooger->err('PAYMENT: ' . __LINE__, [
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        return false;
    }

    /**
     * @param SimpleXMLElement $data
     * @param StoreInterface $store
     * @return string
     * @throws AlreadyExistsException
     * @throws DOMException
     */
    private function saveCardData(SimpleXMLElement $data, StoreInterface $store): string
    {
        $orderId = $data->transaction->orderID;

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

        return $this->recurringResponse($clientHash, $status, $store);
    }

    /**
     * @param SimpleXMLElement $data
     * @param StoreInterface $store
     *
     * @return string
     * @throws DOMException
     */
    private function deleteCardData(SimpleXMLElement $data, StoreInterface $store): string
    {
        $clientHash = (string)$data->recurringData->clientHash;

        /** @var CardResource\Collection $cardCollection */
        $cardCollection = $this->cardCollectionFactory->create();

        /** @var Card $card */
        $card = $cardCollection->getItemByColumnValue('client_hash', $clientHash);

        if ($card !== null) {
            $this->cardResource->delete($card);
        }

        return $this->recurringResponse($clientHash, self::TRANSACTION_CONFIRMED, $store);
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     *
     * @param SimpleXMLElement $response
     * @param StoreInterface $store
     * @param string|null $currency
     *
     * @return bool
     */
    public function validAllTransaction(SimpleXMLElement $response, StoreInterface $store, $currency = null)
    {
        if ($currency === null) {
            if (property_exists($response, 'transactions')) {
                // If we have transactions element
                $currency = $response->transactions->transaction->currency;
            } else {
                // Otherwise - find correct currency
                $currencies = Gateways::$currencies;

                foreach ($currencies as $c) {
                    if ($this->_scopeConfig->getValue(
                            'payment/bluepayment/' . strtolower($c) . '/service_id',
                            ScopeInterface::SCOPE_STORE,
                            $store
                        ) == $response->serviceID) {
                        $currency = $c;
                        break;
                    }
                }
            }
        }

        $serviceId      = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $sharedKey      = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $hashSeparator  = $this->_scopeConfig->getValue(
            'payment/bluepayment/hash_separator',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $hashAlgorithm  = $this->_scopeConfig->getValue(
            'payment/bluepayment/hash_algorithm',
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $remoteServiceId = (string) $response->serviceID;
        $this->bmLooger->info('PAYMENT:' . __LINE__, [
            'local_service_id' => $serviceId,
            'remote_service_id' => $remoteServiceId,
        ]);

        if ($serviceId != $remoteServiceId) {
            return false;
        }

        $this->checkHashArray = [];
        $remoteHash = (string)$response->hash;
        $response->hash = '';

        $this->checkInList($response);
        $this->checkHashArray[] = $sharedKey;

        $localHash = hash($hashAlgorithm, implode($hashSeparator, $this->checkHashArray));

        $this->bmLooger->info('PAYMENT:' . __LINE__, [
            'local_hash' => $localHash,
            'remote_hash' => $localHash,
            'serviceId' => $serviceId,
        ]);

        return $localHash == $remoteHash;
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param  SimpleXMLElement  $payment
     * @param  string  $serviceId
     * @param  StoreInterface  $store
     *
     * @return string
     * @throws DOMException
     */
    protected function updateStatusTransactionAndOrder(
        SimpleXMLElement $payment,
        string $serviceId,
        int $storeId
    ): string {
        $orderId = (string) $payment->orderID;
        $currency = (string) $payment->currency;

        $this->processNotification
            ->asyncExecute(
                $payment,
                $serviceId,
                $storeId
            );

        return $this->returnConfirmation(
            $orderId,
            $currency,
            self::TRANSACTION_CONFIRMED,
            $storeId
        );
    }

    /**
     * @param  array|object  $list
     *
     * @return void
     */
    private function checkInList($list)
    {
        foreach ((array)$list as $row) {
            if (is_object($row)) {
                $this->checkInList($row);
            } else {
                $this->checkHashArray[] = $row;
            }
        }
    }

    /**
     * Potwierdzenie w postaci xml o prawidłowej/nieprawidłowej transakcji
     *
     * @param  string  $orderId
     * @param  string  $currency
     * @param  string  $confirmation
     * @param  StoreInterface  $store
     *
     * @return string
     * @throws DOMException
     */
    public function returnConfirmation(
        string $orderId,
        string $currency,
        string $confirmation,
        int $storeId
    ): string {
        $serviceId = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $sharedKey = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $hashData = [$serviceId, $orderId, $confirmation, $sharedKey];
        $hashConfirmation = $this->helper->generateAndReturnHash($hashData);

        $dom = new DOMDocument('1.0', 'UTF-8');

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

        $xml = $dom->saveXML();

        return $xml ?: '';
    }

    /**
     * @param  string  $clientHash
     * @param  string  $status
     * @param  StoreInterface  $store
     *
     * @return string
     * @throws DOMException
     */
    private function recurringResponse(string $clientHash, string $status, StoreInterface $store): string
    {
        $serviceId        = $this->_scopeConfig->getValue(
            'payment/bluepayment/pln/service_id',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $sharedKey        = $this->_scopeConfig->getValue(
            'payment/bluepayment/pln/shared_key',
            ScopeInterface::SCOPE_STORE,
            $store
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

        return $xml ?: '';
    }

    /**
     * @param array $params
     * @param  bool  $automatic
     * @param  int  $customerId
     * @param  int  $cardIndex
     *
     * @return array
     */
    private function autopayGateway(array $params, bool $automatic, int $customerId, int $cardIndex): array
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
            $params['ScreenType'] = self::IFRAME_SCREEN_TYPE;
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
            'incrementId' => $order->getIncrementId(),
            'additionalInformation' => $payment->getAdditionalInformation(),
        ]);

        $createOrder = $payment->getAdditionalInformation('create_payment') === true || $order->getRemoteIp() === null;

        /** Manually create order (multishipping / GraphQL) */
        if ($createOrder) {
            $backUrl = $payment->getAdditionalInformation('back_url');
            $gatewayId = $payment->hasAdditionalInformation('gateway_id')
                ? $payment->getAdditionalInformation('gateway_id')
                : 0;
            $agreementsIds  = $payment->hasAdditionalInformation('agreements_ids')
                ? explode(',', $payment->getAdditionalInformation('agreements_ids') ?? '')
                : [];

            $this->bmLooger->info('PAYMENT:' . __LINE__, [
                'backUrl' => $backUrl,
                'gatewayId' => $gatewayId,
                'agreementsIds' => $agreementsIds,
            ]);

            $params = $this->getFormRedirectFields(
                $order,
                $gatewayId,
                $agreementsIds,
                false,
                '',
                '',
                -1,
                $backUrl
            );

            $this->createPaymentLink($order, $params);
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return Payment
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        try {
            $order = $payment->getOrder();
            $transaction = $this->transactionRepository->getSuccessTransactionFromOrder($order);
            $result = $this->refunds->makeRefund($transaction, $amount);

            if (isset($result['error']) && $result['error'] === true) {
                $payment->setIsTransactionDenied(true);

                throw new LocalizedException(
                    __($result['message'])
                );
            } else {
                $payment->setIsTransactionApproved(true);
            }
        } catch (InputException $e) {
            $payment->setIsTransactionDenied(true);

            throw new LocalizedException(
                __('Order ID is mandatory.')
            );
        } catch (EmptyRemoteIdException $e) {
            $payment->setIsTransactionDenied(true);

            throw new LocalizedException(
                __('There is no succeeded payment transaction.')
            );
        } catch (NoSuchEntityException $e) {
            $payment->setIsTransactionDenied(true);

            throw new LocalizedException(
                __('There is no such order.')
            );
        }

        return $this;
    }

    /**
     * @param array $params
     *
     * @return SimpleXMLElement|false
     */
    public function sendRequest($params)
    {
        if (array_key_exists('ClientHash', $params)) {
            $this->curl->addHeader('BmHeader', 'pay-bm');
        } else {
            $this->curl->addHeader('BmHeader', 'pay-bm-continue-transaction-url');
        }

        $params = (array) $params;
        $url = $this->getUrlGateway();

        $this->bmLooger->info('PAYMENT:' . __LINE__, [
            'url' => $url,
            'params' => $params,
        ]);

        $this->curl->post($url, $params);
        $response = $this->curl->getBody();

        $this->bmLooger->info('PAYMENT:' . __LINE__, ['response' => $response]);
        return simplexml_load_string($response);
    }

    public function setCode(string $code): self
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param  string  $field
     * @param  int|string|null|Store  $storeId
     *
     * @return mixed
     * @throws LocalizedException
     * @deprecated 100.2.0
     */
    public function getConfigData($field, $storeId = null)
    {
        if (false === strpos($this->_code, self::SEPARATED_PREFIX_CODE)) {
            return parent::getConfigData($field);
        }

        if ($field === 'order_place_redirect_url') {
            return $this->getOrderPlaceRedirectUrl();
        }

        if ($field === 'sort_order') {
            if ($this->getGatewayModel()) {
                return $this->getGatewayModel()->getSortOrder();
            }

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
     * @throws LocalizedException
     * @deprecated 100.2.0
     */
    public function isActive($storeId = null): bool
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * Get payment method title
     *
     * @return string
     * @throws LocalizedException
     */
    public function getTitle(): string
    {
        if (false !== strpos($this->getCode(), self::SEPARATED_PREFIX_CODE)) {
            return $this->title;
        }

        return parent::getTitle();
    }

    /**
     * Set payment method title
     *
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set is payment method separated.
     *
     * @param bool $isSeparated
     * @return void
     */
    public function setIsSeparated(bool $isSeparated = true): self
    {
        $this->isSeparated = $isSeparated;
        return $this;
    }

    /**
     * Returns whether payment method is separated channel.
     *
     * @return bool
     */
    public function getIsSeparated(): bool
    {
        return $this->isSeparated;
    }

    /**
     * Set Gateway (channel) model to payment method.
     *
     * @param Gateway $gatewayModel
     * @return $this
     */
    public function setGatewayModel(Gateway $gatewayModel): self
    {
        $this->gatewayModel = $gatewayModel;
        $this->title = $gatewayModel->getName();
        $this->isSeparated = $gatewayModel->isSeparatedMethod();

        return $this;
    }

    /**
     * Returns Gateway (channel) model.
     *
     * @return Gateway|null
     */
    public function getGatewayModel()
    {
        return $this->gatewayModel;
    }

    /**
     * Create link to payment for order.
     *
     * @param Order $order
     * @param array $params
     * @return false|SimpleXMLElement
     * @throws Exception
     */
    public function createPaymentLink(Order $order, array $params)
    {
        $payment = $order->getPayment();

        $response = $this->sendRequest($params);
        $remoteId = $response->transactionId;
        $redirectUrl = $response->redirecturl;
        $orderStatus = $response->status;
        $confirmation = $response->confirmation;

        if ($confirmation == 'NOTCONFIRMED') {
            $orderComment = 'Unable to create transaction. Reason: '.$response->reason;
            $order->addCommentToStatusHistory($orderComment);
        } else {
            $payment->setAdditionalInformation('bluepayment_redirect_url', (string) $redirectUrl);

            $unchangeableStatuses = $this->configProvider->getUnchangableStatuses();
            $status = $this->configProvider->getStatusWaitingPayment();
            $state = $this->getStateForStatus->execute($status, Order::STATE_PENDING_PAYMENT);

            if (!in_array($order->getStatus(), $unchangeableStatuses, false)) {
                $amount = $order->getGrandTotal();
                $formattedAmount = number_format(round($amount, 2), 2, '.', '');

                $orderComment = '[BM] Transaction ID: '. $remoteId
                    .' | Amount: '.$formattedAmount
                    .' | Status: '.$orderStatus
                    .' | URL: '.$redirectUrl;

                $order->setState($state)
                    ->setStatus($status)
                    ->addStatusToHistory($status, $orderComment, false)
                    ->save();
            }

            return $redirectUrl;
        }

        return false;
    }
}
