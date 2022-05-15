<?php

namespace BlueMedia\Autopay\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session;
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
     */
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        $quote = $this->checkoutSession->getQuote();

        $result['cartId'] = $quote->getId();
        $result['currency'] = $quote->getQuoteCurrencyCode();

        return $result;
    }
}