<?php

namespace BlueMedia\Autopay\Model;

use BlueMedia\Autopay\Api\Data\ShippingMethodAdditionalInterface;
use BlueMedia\Autopay\Api\Data\ShippingMethodInterfaceFactory;
use BlueMedia\Autopay\Api\QuoteManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address\CustomerAddressDataProvider;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class QuoteManagement implements QuoteManagementInterface
{
    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var ShippingMethodInterfaceFactory */
    private $shippingMethodFactory;

    /** @var TotalsCollector */
    private $totalsCollector;

    /** @var CustomerAddressDataProvider */
    private $customerAddressData;

    /** @var DataObjectProcessor */
    private $dataObjectProcessor;

    /** @var CartExtensionFactory */
    private $cartExtensionFactory;

    /** @var AddressRepositoryInterface */
    private $addressRepository;

    /** @var CartManagementInterface  */
    private $cartManagement;

    /** @var PaymentInterfaceFactory */
    private $paymentFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ShippingMethodInterfaceFactory $shippingMethodFactory,
        TotalsCollector $totalsCollector,
        CustomerAddressDataProvider $customerAddressData,
        DataObjectProcessor $dataObjectProcessor,
        CartExtensionFactory $cartExtensionFactory,
        AddressRepositoryInterface $addressRepository,
        CartManagementInterface $cartManagement,
        PaymentInterfaceFactory $paymentFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->totalsCollector = $totalsCollector;
        $this->customerAddressData = $customerAddressData;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->addressRepository = $addressRepository;
        $this->cartManagement = $cartManagement;
        $this->paymentFactory = $paymentFactory;
        $this->orderRepository = $orderRepository;
    }

    public function getCartDetails($cartId, $hash)
    {
        return $this->getCart($cartId);
    }

    public function getAddresses($cartId, $hash)
    {
        $cart = $this->getCart($cartId);
        $customer = $cart->getCustomerIsGuest() ? null : $cart->getCustomer();

        if ($customer) {
            return $this->customerAddressData->getAddressDataByCustomer($customer);
        }

        return [];
    }

    public function setShippingAddress($cartId, $hash, AddressInterface $address)
    {
        $cart = $this->getCart($cartId);

        $shippingAddress = $cart->getShippingAddress();
        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $cart->setShippingAddress($shippingAddress);

        $this->totalsCollector->collectAddressTotals($cart, $shippingAddress);

        $this->cartRepository->save($cart);

        return true;
    }

    /**
     * @throws InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setShippingAddressById($cartId, $hash, $addressId)
    {
        $cart = $this->getCart($cartId);

        if ($cart->getCustomerIsGuest()) {
            throw new InputException('Guest cart can not have shipping address setted by id');
        }

        $shippingAddress = $cart->getShippingAddress();

        $address = $this->addressRepository->getById($addressId);

        if ($address->getCustomerId() !== $cart->getCustomer()->getId()) {
            throw new InputException('Address is not owned by customer');
        }

        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $cart->setShippingAddress($shippingAddress);

        $this->totalsCollector->collectAddressTotals($cart, $shippingAddress);

        $this->cartRepository->save($cart);

        return true;
    }

    public function setBillingAddress($cartId, $hash, AddressInterface $address)
    {
        $cart = $this->getCart($cartId);

        $billingAddres = $cart->getBillingAddress();
        $billingAddres->addData($this->extractAddressData($address));

        $cart->setBillingAddress($billingAddres);

        $this->cartRepository->save($cart);
        return true;
    }

    /**
     * @throws InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setBillingAddressById($cartId, $hash, $addressId)
    {
        $cart = $this->getCart($cartId);

        if ($cart->getCustomerIsGuest()) {
            throw new InputException('Guest cart can not have shipping address setted by id');
        }

        $address = $this->addressRepository->getById($addressId);

        if ($address->getCustomerId() !== $cart->getCustomer()->getId()) {
            throw new InputException('Address is not owned by customer');
        }

        $billingAddres = $cart->getBillingAddress();
        $billingAddres->addData($this->extractAddressData($address));

        $cart->setBillingAddress($billingAddres);

        $this->cartRepository->save($cart);
        return true;
    }

    public function getShippingMethods($cartId, $hash)
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

    public function setShippingMethod(
        $cartId,
        $hash,
        $carrierCode,
        $methodCode,
        ShippingMethodAdditionalInterface $additional
    ) {
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

    public function placeOrder($cartId, $hash, $paymentId, $amount)
    {
        $paymentMethod = $this->paymentFactory->create();
        $paymentMethod->setMethod('autopay');

        $orderId = $this->cartManagement->placeOrder($cartId, $paymentMethod);
        $order = $this->orderRepository->get($orderId);

        $orderComment =
            '[Autopay] Transaction ID: ' . $paymentId
            . ' | Amount: ' . $amount;

        $order->addCommentToStatusHistory($orderComment);
        $this->orderRepository->save($order);

        return $orderId;
    }

    private function getCart($cartId)
    {
        return $this->cartRepository->get($cartId);
    }

    /**
     * Get transform address interface into Array
     *
     * @param AddressInterface $address
     * @return array
     */
    private function extractAddressData($address)
    {
        $className = \Magento\Customer\Api\Data\AddressInterface::class;
        if ($address instanceof AddressInterface) {
            $className = AddressInterface::class;
        }

        $addressData = $this->dataObjectProcessor->buildOutputDataArray($address, $className);
        unset($addressData[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);

        return $addressData;
    }
}
