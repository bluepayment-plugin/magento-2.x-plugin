<?php

namespace BlueMedia\BluePayment\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;

class CartDataPlugin
{
    /** @var Session */
    protected $checkoutSession;

    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param  Cart  $subject
     * @param  array  $result
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
