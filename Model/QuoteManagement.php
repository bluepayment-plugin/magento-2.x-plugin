<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\CartInterface;
use BlueMedia\BluePayment\Api\Data\ConfigurationInterface;
use BlueMedia\BluePayment\Api\Data\ConfigurationInterfaceFactory;
use BlueMedia\BluePayment\Api\Data\PlaceOrderResponseInterface;
use BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface;
use BlueMedia\BluePayment\Api\Data\ShippingMethodInterfaceFactory;
use BlueMedia\BluePayment\Api\QuoteManagementInterface;
use BlueMedia\BluePayment\Model\Autopay\ConfigProvider;
use BlueMedia\BluePayment\Model\Autopay\GetCartDetails;
use BlueMedia\BluePayment\Model\Data\PlaceOrderResponseDataFactory;
use BlueMedia\BluePayment\Model\Data\PlaceOrderResponseFactory;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Customer\Api\AddressRepositoryInterface;
use BlueMedia\BluePayment\Logger\Logger as Logger;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartInterface as MagentoCartInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class QuoteManagement implements QuoteManagementInterface
{
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_INVALID = 'INVALID';

    public const ERROR_WRONG_ORDER_AMOUNT = 'WRONG_ORDER_AMOUNT';

    /**
     * @var string[] Error messages
     */
    public $errorMessages = [
        self::ERROR_WRONG_ORDER_AMOUNT => 'The order amount is different from the shopping cart amount.',
    ];

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var ShippingMethodInterfaceFactory */
    private $shippingMethodFactory;

    /** @var TotalsCollector */
    private $totalsCollector;

    /** @var DataObjectProcessor */
    private $dataObjectProcessor;

    /** @var CartExtensionFactory */
    private $cartExtensionFactory;

    /** @var AddressRepositoryInterface */
    private $addressRepository;

    /** @var CartManagementInterface */
    private $cartManagement;

    /** @var PaymentInterfaceFactory */
    private $paymentFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var ConfigurationInterfaceFactory */
    private $configurationFactory;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var PlaceOrderResponseFactory */
    private $placeOrderResponseFactory;

    /** @var PlaceOrderResponseDataFactory */
    private $placeOrderResponseDataFactory;

    /** @var Metadata */
    private $metadata;

    /** @var Logger */
    private $logger;

    /**
     * QuoteManagement constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingMethodInterfaceFactory $shippingMethodFactory
     * @param TotalsCollector $totalsCollector
     * @param DataObjectProcessor $dataObjectProcessor
     * @param CartExtensionFactory $cartExtensionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CartManagementInterface $cartManagement
     * @param PaymentInterfaceFactory $paymentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ConfigurationInterfaceFactory $configurationFactory
     * @param ConfigProvider $configProvider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PlaceOrderResponseFactory $placeOrderResponseFactory
     * @param PlaceOrderResponseDataFactory $placeOrderResponseDataFactory
     * @param Metadata $metadata
     * @param Logger $logger
     */
    public function __construct(
        CartRepositoryInterface        $cartRepository,
        ShippingMethodInterfaceFactory $shippingMethodFactory,
        TotalsCollector                $totalsCollector,
        DataObjectProcessor            $dataObjectProcessor,
        CartExtensionFactory           $cartExtensionFactory,
        AddressRepositoryInterface     $addressRepository,
        CartManagementInterface        $cartManagement,
        PaymentInterfaceFactory        $paymentFactory,
        OrderRepositoryInterface       $orderRepository,
        ConfigurationInterfaceFactory  $configurationFactory,
        ConfigProvider                 $configProvider,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        PlaceOrderResponseFactory      $placeOrderResponseFactory,
        PlaceOrderResponseDataFactory  $placeOrderResponseDataFactory,
        GetCartDetails                 $getCartDetails,
        Metadata                       $metadata,
        Logger                         $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->totalsCollector = $totalsCollector;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->addressRepository = $addressRepository;
        $this->cartManagement = $cartManagement;
        $this->paymentFactory = $paymentFactory;
        $this->orderRepository = $orderRepository;
        $this->configurationFactory = $configurationFactory;
        $this->configProvider = $configProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->placeOrderResponseFactory = $placeOrderResponseFactory;
        $this->placeOrderResponseDataFactory = $placeOrderResponseDataFactory;
        $this->getCartDetails = $getCartDetails;
        $this->metadata = $metadata;
        $this->logger = $logger;
    }

    /**
     * Get website configuration.
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration(): ConfigurationInterface
    {
        $configuration = $this->configurationFactory->create();

        $lifetime = $this->configProvider->getQuoteLifetime();

        $configuration->setQuoteLifetime(
            $lifetime ? (int)$lifetime : null
        );
        $configuration->setPlatformVersion($this->metadata->getMagentoVersion());
        $configuration->setPlatformEdition($this->metadata->getMagentoEdition());
        $configuration->setModuleVersion($this->metadata->getModuleVersion());

        return $configuration;
    }

    /**
     * Get cart details by id.
     *
     * @param int $cartId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function getCartDetails(int $cartId): CartInterface
    {
        return $this->getCartDetails->execute(
            $this->getCart($cartId)
        );
    }

    /**
     * Get cart by id
     *
     * @param int $cartId
     * @return MagentoCartInterface
     * @throws NoSuchEntityException
     */
    private function getCart(int $cartId): MagentoCartInterface
    {
        return $this->cartRepository->get($cartId);
    }

    /**
     * Get available addresses for cart.
     *
     * @param int $cartId
     *
     * @return mixed
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getAddresses(int $cartId)
    {
        $cart = $this->getCart($cartId);
        $customer = $cart->getCustomerIsGuest() ? null : $cart->getCustomer();

        if ($customer) {
            $customerAddressData = ObjectManager::getInstance()
                ->get(PaymentProcessingRateLimiterInterface::class);

            if ($customerAddressData) {
                return $customerAddressData->getAddressDataByCustomer($customer);
            }

            $configProvider = ObjectManager::getInstance()
                ->get(DefaultConfigProvider::class);
            $config = $configProvider->getConfig();

            return $config['customerData']['addresses'];
        }

        return [];
    }

    /**
     * Set shipping address by ID
     *
     * @param int $cartId
     * @param int $addressId
     * @return bool
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setShippingAddressById(int $cartId, int $addressId): bool
    {
        $cart = $this->getCart($cartId);

        if ($cart->getCustomerIsGuest()) {
            throw new InputException(__('Guest cart can not have shipping address set by id'));
        }

        $shippingAddress = $cart->getShippingAddress();

        $address = $this->addressRepository->getById($addressId);

        if ($address->getCustomerId() !== $cart->getCustomer()->getId()) {
            throw new InputException(__('Address is not owned by customer'));
        }

        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $cart->setShippingAddress($shippingAddress);

        $this->totalsCollector->collectAddressTotals($cart, $shippingAddress);

        $this->cartRepository->save($cart);

        return true;
    }

    /**
     * Get transform address interface into Array
     *
     * @param QuoteAddressInterface|CustomerAddressInterface $address
     * @return array
     */
    private function extractAddressData($address): array
    {
        $className = CustomerAddressInterface::class;
        if ($address instanceof QuoteAddressInterface) {
            $className = QuoteAddressInterface::class;
            $this->logger->info('[AutoPay] Address is instance of Quote AddressInterface');
        } else {
            $this->logger->info('[AutoPay] Address is instance of customer AddressInterface');
        }

        $addressData = $this->dataObjectProcessor->buildOutputDataArray($address, $className);
        unset($addressData[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);

        $this->logger->info('[AutoPay] extractAddressData', [
            'address' => $addressData,
        ]);
        return $addressData;
    }

    /**
     * Set shipping address
     *
     * @param int $cartId
     * @param QuoteAddressInterface $address
     * @return true
     * @throws NoSuchEntityException
     */
    public function setShippingAddress(int $cartId, QuoteAddressInterface $address): bool
    {
        $this->logger->info('setBillingAddress', [
            'cartId' => $cartId,
            'email' => $address->getEmail(),
        ]);

        $cart = $this->getCart($cartId);

        $shippingAddress = $cart->getShippingAddress();
        $this->logger->info('[AutoPay] setShippingAddress - ', [
            'email' => $shippingAddress->getEmail(),
        ]);
        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $cart->setShippingAddress($shippingAddress);

        $this->logger->info('[AutoPay] setShippingAddress - ', [
            'email' => $shippingAddress->getEmail(),
        ]);

        $this->totalsCollector->collectAddressTotals($cart, $shippingAddress);

        $this->cartRepository->save($cart);

        return true;
    }

    /**
     * Set billing address by ID
     *
     * @param int $cartId
     * @param int $addressId
     * @return bool
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setBillingAddressById(int $cartId, int $addressId): bool
    {
        $cart = $this->getCart($cartId);

        if ($cart->getCustomerIsGuest()) {
            throw new InputException(__('Guest cart can not have shipping address setted by id'));
        }

        $address = $this->addressRepository->getById($addressId);

        if ($address->getCustomerId() !== $cart->getCustomer()->getId()) {
            throw new InputException(__('Address is not owned by customer'));
        }

        $billingAddres = $cart->getBillingAddress();
        $billingAddres->addData($this->extractAddressData($address));

        $cart->setBillingAddress($billingAddres);

        $this->cartRepository->save($cart);
        return true;
    }

    /**
     * Set billing address
     *
     * @param int $cartId
     * @param QuoteAddressInterface $address
     *
     * @return boolean
     *
     * @throws NoSuchEntityException
     */
    public function setBillingAddress(int $cartId, QuoteAddressInterface $address): bool
    {
        $this->logger->info('setBillingAddress', [
            'cartId' => $cartId,
            'email' => $address->getEmail(),
        ]);

        $cart = $this->getCart($cartId);

        $billingAddres = $cart->getBillingAddress();
        $billingAddres->addData($this->extractAddressData($address));

        $cart->setBillingAddress($billingAddres);

        $this->cartRepository->save($cart);
        return true;
    }

    /**
     * Get available shipping methods for cart.
     *
     * @param int $cartId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getShippingMethods(int $cartId): array
    {
        $cart = $this->getCart($cartId);
        $currency = $cart->getStore()->getBaseCurrency();

        $address = $cart->getShippingAddress();
        $address->setLimitCarrier(null);

        // Allow shipping rates by setting country id for new addresses
        if (!$address->getCountryId() && $address->getCountryCode()) {
            $address->setCountryId($address->getCountryCode());
        }

        $address->setCollectShippingRates(true);
        $cart = $address->getQuote();
        $this->totalsCollector->collectAddressTotals($cart, $address);

        $methods = [];
        $shippingRates = $address->getGroupedAllShippingRates();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $shippingMethod = $this->shippingMethodFactory->create();

                $shippingMethod->setCarrierCode($rate->getCarrier())
                    ->setMethodCode($rate->getMethod())
                    ->setCarrierTitle($rate->getCarrierTitle())
                    ->setMethodTitle($rate->getMethodTitle())
                    ->setAmount($currency->convert($rate->getPrice(), $cart->getQuoteCurrencyCode()));

                $methods[] = $shippingMethod;
            }
        }

        return $methods;
    }

    /**
     * Set shipping method for cart.
     *
     * @param int $cartId
     * @param string $carrierCode
     * @param string $methodCode
     * @param ShippingMethodAdditionalInterface|null $additional
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function setShippingMethod(
        int                               $cartId,
        string                            $carrierCode,
        string                            $methodCode,
        ShippingMethodAdditionalInterface $additional = null
    ): bool {
        $this->logger->info('[AutoPay] Set shipping method', [
            'cartId' => $cartId,
            'carrier' => $carrierCode,
            'method' => $methodCode,
            'additional' => [
                'lockerId' => $additional ? $additional->getLockerId() : null,
            ]
        ]);

        $cart = $this->getCart($cartId);

        $shippingAddress = $cart->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->setShippingMethod($carrierCode . '_' . $methodCode);

        if ($carrierCode === 'inpostlocker' && class_exists('Smartmage\Inpost\Model\Checkout\Processor')) {
            $cartExtensionAttributes = $cart->getExtensionAttributes();
            if (!$cartExtensionAttributes) {
                $cartExtensionAttributes = $this->cartExtensionFactory->create();
            }

            $cartExtensionAttributes->setInpostLockerId($additional->getLockerId());

            $cart->setExtensionAttributes($cartExtensionAttributes);
        }

        $this->totalsCollector->collectAddressTotals($cart, $shippingAddress);

        $this->cartRepository->save($cart);

        return true;
    }

    /**
     * Place order.
     *
     * @param int $cartId
     * @param float $amount
     * @return PlaceOrderResponseInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function placeOrder(int $cartId, float $amount): PlaceOrderResponseInterface
    {
        $this->logger->info('[AutoPay] Place Order', [
            'cartId' => $cartId,
            'amount' => $amount,
        ]);

        // If we try to place order which has been stored already - just return order id
        if ($order = $this->findOrderByCartId($cartId)) {
            return $this->createSuccessResponse($order);
        }

        $quote = $this->getCart($cartId);

        if (!$this->validateCartAmount($quote, $amount)) {
            return $this->createErrorResponse(self::ERROR_WRONG_ORDER_AMOUNT);
        }

        $customer = $quote->getCustomer();
        $customerId = $customer ? $customer->getId() : null;

        if (!$customerId) {
            $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
            $this->cartRepository->save($quote);
        }

        $paymentMethod = $this->paymentFactory->create();
        $paymentMethod->setMethod('autopay');

        $order = $this->cartManagement->placeOrder($cartId, $paymentMethod);
        $order = $this->orderRepository->get($order);

        $orderComment = '[Autopay] Amount: ' . $amount;

        $order->addCommentToStatusHistory($orderComment);
        $this->orderRepository->save($order);

        return $this->createSuccessResponse($order);
    }

    /**
     * Get order by cart id or return false.
     *
     * @param int $cartId
     *
     * @return OrderInterface|false
     */
    private function findOrderByCartId(int $cartId)
    {
        $orders = $this->orderRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('quote_id', $cartId)
                ->create()
        );

        if ($orders->getTotalCount() > 0) {
            return current($orders->getItems());
        }

        return false;
    }

    /**
     * Create success response for place order.
     *
     * @param OrderInterface $order
     * @return PlaceOrderResponseInterface
     */
    private function createSuccessResponse(OrderInterface $order): PlaceOrderResponseInterface
    {
        $response = $this->placeOrderResponseFactory->create();
        $responseData = $this->placeOrderResponseDataFactory->create();

        $responseData->setRemoteOrderId($order->getIncrementId());

        $response->setStatus(self::STATUS_SUCCESS);
        $response->setOrderData($responseData);

        return $response;
    }

    /**
     * Validate amount of cart
     *
     * @param MagentoCartInterface $cart
     * @param float $amount
     * @return bool
     */
    private function validateCartAmount(MagentoCartInterface $cart, float $amount): bool
    {
        $this->logger->info('Validate cart amount', [
            'cart_id' => $cart->getId(),
            'cart_amount' => (float)$cart->getGrandTotal(),
            'amount' => $amount,
        ]);

        return (float)$cart->getGrandTotal() === $amount;
    }

    /**
     * Create error response for place order.
     *
     * @param string $code
     * @return PlaceOrderResponseInterface
     */
    private function createErrorResponse(string $code): PlaceOrderResponseInterface
    {
        $message = $this->errorMessages[$code] ?? 'Unknown error';

        $response = $this->placeOrderResponseFactory->create();

        $response->setStatus(self::STATUS_INVALID);
        $response->setErrorCode($code);
        $response->setErrorMessage($message);

        return $response;
    }
}
