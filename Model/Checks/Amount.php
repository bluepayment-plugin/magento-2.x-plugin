<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Checks;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Gateway;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

class Amount implements SpecificationInterface
{
    /** @var CustomerSession */
    private $customerSession;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check whether payment method is applicable to quote.
     *
     * @param MethodInterface $paymentMethod
     * @param Quote $quote
     * @return bool
     */
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote): bool
    {
        if (strpos($paymentMethod->getCode(), Payment::SEPARATED_PREFIX_CODE) !== 0) {
            return true;
        }

        if (!method_exists($paymentMethod, 'getGatewayModel')) {
            return true;
        }

        $gateway = $paymentMethod->getGatewayModel();

        if (!$gateway instanceof Gateway) {
            return true;
        }

        if ((int) $gateway->getGatewayId() === ConfigProvider::ONECLICK_GATEWAY_ID
            && !$this->customerSession->isLoggedIn()
        ) {
            return false;
        }

        $total = $this->getQuoteTotal($quote);
        $minAmount = $gateway->getMinAmount();
        $maxAmount = $gateway->getMaxAmount();

        if ($minAmount !== null && $total < $minAmount) {
            return false;
        }

        if ($maxAmount !== null && $total > $maxAmount) {
            return false;
        }

        return true;
    }

    private function getQuoteTotal(Quote $quote): float
    {
        if ($this->scopeConfig->isSetFlag(
            'payment/bluepayment/use_base_currency',
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        )) {
            return (float) $quote->getBaseGrandTotal();
        }

        return (float) $quote->getGrandTotal();
    }
}
