<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Autopay;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;

class SetPaymentMethod
{
    /** @var Session */
    protected $session;

    /** @var PaymentInterfaceFactory */
    protected $paymentFactory;

    /** @var PaymentMethodManagementInterface */
    protected $paymentMethodManagement;

    /**
     * SetPaymentMethod constructor.
     *
     * @param Session $session
     * @param PaymentInterfaceFactory $paymentFactory
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     */
    public function __construct(
        Session $session,
        PaymentInterfaceFactory $paymentFactory,
        PaymentMethodManagementInterface $paymentMethodManagement
    ) {
        $this->session = $session;
        $this->paymentFactory = $paymentFactory;
        $this->paymentMethodManagement = $paymentMethodManagement;
    }

    /**
     * Set payment to AutoPay.
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): bool
    {
        $quote = $this->session->getQuote();

        if (! $quote->getIsActive()) {
            throw new LocalizedException(__('Quote is not active.'));
        }

        $payment = $this->paymentFactory->create();
        $payment->setMethod('autopay');

        $this->setPaymentMethodWithoutValidation($quote, $payment);

        return true;
    }

    /**
     * Set payment method in quote, but without validation for address.
     *
     * Address will be saved by autopay API, but for getting correct discounts we need to set payment method.
     *
     * @param Quote $quote
     * @param PaymentInterface $method
     * @return void
     * @throws LocalizedException
     */
    protected function setPaymentMethodWithoutValidation(
        Quote $quote,
        PaymentInterface $method
    ): void {
        $quote->setTotalsCollectedFlag(false);
        $method->setChecks([
            MethodInterface::CHECK_USE_CHECKOUT,
            MethodInterface::CHECK_USE_FOR_COUNTRY,
            MethodInterface::CHECK_USE_FOR_CURRENCY,
            MethodInterface::CHECK_ORDER_TOTAL_MIN_MAX,
        ]);

        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
            $address->setCollectShippingRates(true);
        }

        $paymentData = $method->getData();
        $payment = $quote->getPayment();
        $payment->importData($paymentData);
        $address->setPaymentMethod($payment->getMethod());

        $quote->save();
    }
}
