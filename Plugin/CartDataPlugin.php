<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class CartDataPlugin
{
    /** @var Session */
    protected $checkoutSession;

    /**
     * CartDataPlugin constructor.
     *
     * @param Session $checkoutSession
     */
    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Extend get section data with data needed for AutoPay.
     *
     * @param  Cart $subject
     * @param  array $result
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        $quote = $this->checkoutSession->getQuote();
        $address = $quote->getShippingAddress();

        $result['cart_id'] = $quote->getId();
        $result['currency'] = $quote->getQuoteCurrencyCode();

        $result['grand_total'] = (float) $quote->getGrandTotal();
        $result['tax_amount'] = $address->getBaseTaxAmount() + $address->getBaseDiscountTaxCompensationAmount();
        $result['shipping_incl_tax'] = (float) $address->getShippingInclTax();

        $result['base_subtotal_with_discount'] = (float) $address->getBaseSubtotalWithDiscount();
        $result['base_subtotal'] = (float) $address->getBaseSubtotal();

        return $result;
    }
}
