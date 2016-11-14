<?php

namespace BlueMedia\BluePayment\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'bluepayment';

    private $_checkHashArray = [];

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

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \BlueMedia\BluePayment\Helper\Data $helper,
        \Magento\Framework\UrlInterface $url,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->sender = $orderSender;
        $this->url = $url;
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;

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

        return $this->url->getUrl('bluepayment/processing/create', array('_secure' => true));
    }

    /**
     * Zwraca adres bramki
     * 
     * @return string
     */
    public function getUrlGateway()
    {
        // Aktywny tryb usługi
        $mode = $this->getConfigData('test_mode');

        if ($mode) {
            return $this->getConfigData("test_address_url");
        }

        return $this->getConfigData("prod_address_url");
    }

    /**
     * Tablica z parametrami do wysłania metodą GET do bramki
     * @param object $order
     *
     * @return array
     */
    public function getFormRedirectFields($order)
    {
        // Id zamówienia
        $orderId = $order->getRealOrderId();

        // Suma zamówienia
        $amount = number_format(round($order->getGrandTotal(), 2), 2, '.', '');

        // Dane serwisu partnera
        // Indywidualny numer serwisu
        $serviceId = $this->getConfigData('service_id');

        // Klucz współdzielony
        $sharedKey = $this->getConfigData('shared_key');

        // Adres email klienta
        $customerEmail = $order->getCustomerEmail();

        // Tablica danych z których wygenerować hash
        $hashData = array($serviceId, $orderId, $amount, $customerEmail, $sharedKey);

        // Klucz hash
        $hashLocal = $this->helper->generateAndReturnHash($hashData);

        // Tablica z parametrami do formularza
        $params = array(
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'CustomerEmail' => $customerEmail,
            'Hash' => $hashLocal
        );

        return $params;
    }

    /**
     * Ustawia odpowiedni status transakcji/płatności zgodnie z uzyskaną informacją
     * z akcji 'statusAction'
     *
     * @param array $transactions
     * @param string $hash
     */
    public function processStatusPayment($response)
    {
        if ($this->_validAllTransaction($response)) {
            $transaction_xml = $response->transactions->transaction;
            // Aktualizacja statusu zamówienia i transakcji
            $this->updateStatusTransactionAndOrder($transaction_xml);
        }
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     * @param XML $response
     * @return boolen 
     */
    public function _validAllTransaction($response)
    {
        $service_id = $this->getConfigData('service_id');
        // Klucz współdzielony
        $shared_key = $this->getConfigData('shared_key');

        $algorithm = $this->getConfigData("hash_algorithm");

        $separator = $this->getConfigData("hash_separator");

        if ($service_id != $response->serviceID)
            return false;
        $this->_checkHashArray = [];
        $hash = (string) $response->hash;
        $this->_checkHashArray[] = (string) $response->serviceID;

        foreach ($response->transactions->transaction as $trans) {
            $this->_checkInList($trans);
        }
        $this->_checkHashArray[] = $shared_key;
        return hash($algorithm, implode($separator, $this->_checkHashArray)) == $hash;
    }

    private function _checkInList($list)
    {
        foreach ((array) $list as $row) {
            if (is_object($row)) {
                $this->_checkInList($row);
            } else {
                $this->_checkHashArray[] = $row;
            }
        }
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
        $status = $order->getStatus();
        $stateOrderTab = array(
            \Magento\Sales\Model\Order::STATE_CLOSED,
            \Magento\Sales\Model\Order::STATE_CANCELED,
            \Magento\Sales\Model\Order::STATE_COMPLETE
        );

        return in_array($status, $stateOrderTab);
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
        // Id serwisu partnera
        $serviceId = $this->getConfigData('service_id');

        // Klucz współdzielony
        $sharedKey = $this->getConfigData('shared_key');

        // Tablica danych z których wygenerować hash
        $hashData = array($serviceId, $orderId, $confirmation, $sharedKey);

        // Klucz hash
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
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     * @throws Exception
     */
    protected function updateStatusTransactionAndOrder($transaction)
    {
        // Status płatności
        $paymentStatus = $transaction->paymentStatus;

        // Id transakcji nadany przez bramkę
        $remoteId = $transaction->remoteID;

        // Id zamówienia
        $orderId = $transaction->orderID;

        // Objekt zamówienia
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        // Obiekt płatności zamówienia
        /**
         * @var \Magento\Sales\Model\Order\Payment $orderPayment
         */
        $orderPayment = $order->getPayment();

        // Stan płatności w zamówieniu
        $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');

        // Suma zamówienia
        $amount = number_format(round($order->getGrandTotal(), 2), 2, '.', '');

        // Statusy i stany zamówienia
        // TODO: zastanowić się nad możliwością ustawiania "własnego" stanu zamówienia
        $orderStatusWaitingState = '';
        if ($this->getConfigData('status_waiting_payment') != '') {
            $statusWaitingPayment = $this->getConfigData('status_waiting_payment');
            $orderStatusWaitingState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            foreach($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var \Magento\Sales\Model\Order\Status $status */
                if ($status->getStatus() == $statusWaitingPayment) {
                    $orderStatusWaitingState = $status->getState();
                }
            }
        } else {
            $statusWaitingPayment = $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        }

        if ($this->getConfigData('status_accept_payment') != '') {
            $statusAcceptPayment = $this->getConfigData('status_accept_payment');
            $orderStatusAcceptState = \Magento\Sales\Model\Order::STATE_PROCESSING;
            foreach($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var \Magento\Sales\Model\Order\Status $status */
                if ($status->getStatus() == $statusAcceptPayment) {
                    $orderStatusAcceptState = $status->getState();
                }
            }
        } else {
            $statusAcceptPayment = $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        }

        if ($this->getConfigData('status_error_payment') != '') {
            $statusErrorPayment = $this->getConfigData('status_error_payment');
            $orderStatusErrorState = \Magento\Sales\Model\Order::STATE_NEW;
            foreach($this->statusCollectionFactory->create()->joinStates() as $status) {
                /** @var \Magento\Sales\Model\Order\Status $status */
                if ($status->getStatus() == $statusErrorPayment) {
                    $orderStatusErrorState = $status->getState();
                }
            }
        } else {
            $statusErrorPayment = $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
        }

        $paymentStatus = (string) $paymentStatus;

        try {
            // Jeśli zamówienie jest otwarte i status płatności zamówienia jest różny od statusu płatności z bramki
            if (!($this->isOrderCompleted($order)) && $orderPaymentState != $paymentStatus) {
                switch ($paymentStatus) {
                    // Jeśli transakcja została rozpoczęta
                    case self::PAYMENT_STATUS_PENDING:
                        // Jeśli aktualny status zamówienia jest różny od ustawionego jako "oczekiwanie na płatność"
                        if ($paymentStatus != $orderPaymentState) {
                            $transaction = $orderPayment->setTransactionId((string) $remoteId);
                            $transaction->prependMessage('[' . self::PAYMENT_STATUS_PENDING . ']');
                            $transaction->save();
                            // Powiadomienie mailowe dla klienta
                            $order->setState($orderStatusWaitingState)
                                    ->setStatus($statusWaitingPayment)
                                    ->addStatusToHistory($statusWaitingPayment, '', true)
                                    ->save();
                        }
                        break;
                    // Jeśli transakcja została zakończona poprawnie
                    case self::PAYMENT_STATUS_SUCCESS:
                        $transaction = $orderPayment->setTransactionId((string) $remoteId);
                        $transaction->prependMessage('[' . self::PAYMENT_STATUS_SUCCESS . ']');
                        $transaction->registerAuthorizationNotification($amount)
                                ->setIsTransactionApproved(true)
                                ->setIsTransactionClosed(true)
                                ->save();
                        // Powiadomienie mailowe dla klienta
                        $order->setState($orderStatusAcceptState)
                                ->setStatus($statusAcceptPayment)
                                ->addStatusToHistory($statusAcceptPayment, '', true)
                                ->save();
                        break;
                    // Jeśli transakcja nie została zakończona poprawnie
                    case self::PAYMENT_STATUS_FAILURE:

                        // Jeśli aktualny status zamówienia jest równy ustawionemu jako "oczekiwanie na płatność"
                        if ($orderPaymentState != $paymentStatus) {
                            $transaction = $orderPayment->setTransactionId((string) $remoteId);
                            $transaction->prependMessage('[' . self::PAYMENT_STATUS_FAILURE . ']');
                            $transaction->registerCaptureNotification($order->getGrandTotal())
                                ->save();
                            // Powiadomienie mailowe dla klienta
                            $order->setState($orderStatusErrorState)
                                ->setStatus($statusAcceptPayment)
                                ->addStatusToHistory($statusErrorPayment, '', true)
                                ->save();
                        }
                        break;
                    default:
                        break;
                }
            }
            $this->sender->send($order);
            $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
            $orderPayment->save();
            $this->returnConfirmation($orderId, self::TRANSACTION_CONFIRMED);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

}
