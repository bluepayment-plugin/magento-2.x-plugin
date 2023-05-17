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

        $result['bm_cart_id'] = $quote->getId();
        $result['bm_currency'] = $quote->getQuoteCurrencyCode();

        $result['bm_grand_total'] = (float) $quote->getGrandTotal();
        $result['bm_tax_amount'] = $address->getBaseTaxAmount() + $address->getBaseDiscountTaxCompensationAmount();
        $result['bm_shipping_incl_tax'] = (float) $address->getShippingInclTax();

        $result['bm_base_subtotal_with_discount'] = (float) $address->getBaseSubtotalWithDiscount();
        $result['bm_base_subtotal'] = (float) $address->getBaseSubtotal();

        return $result;
    }
}
