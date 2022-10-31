<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Autopay;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * AutoPay Checkout ConfigProvider constructor
     *
     * @param  ScopeConfigInterface  $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [];
    }

    /**
     * Check whether APC is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/autopay/active',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check whether APC is in test mode
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/autopay/test_mode',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Blue Media Service ID for PLN currency.
     *
     * @return string
     */
    public function getServiceId(): string
    {
        return (string) $this->scopeConfig->getValue(
            'payment/bluepayment/pln/service_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get AutoPay Secret Key
     *
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->scopeConfig->getValue(
            'payment/autopay/secret_key',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get AutoPay Merchant ID
     *
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->scopeConfig->getValue(
            'payment/autopay/merchant_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get quote lifetime
     *
     * @return mixed
     */
    public function getQuoteLifetime()
    {
        return $this->scopeConfig->getValue(
            'checkout/cart/delete_quote_after',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Code language for autopay (currently only pl and en)
     *
     * @return string
     */
    public function getLanguage(): string
    {
        $locale = $this->scopeConfig
            ->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE
            );

        return $this->getLanguageFromLocale($locale);
    }

    /**
     * Is minimum order amount enabled?
     *
     * @return bool
     */
    public function isMinimumOrderActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'sales/minimum_order/active',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns minimum order amount.
     *
     * @return float
     */
    public function getMinimumOrderAmount(): float
    {
        return (float) $this->scopeConfig->getValue(
            'sales/minimum_order/amount',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Should minimum order amount include discount?
     *
     * @return bool
     */
    public function isMinimumOrderIncludingDiscount(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'sales/minimum_order/include_discount_amount',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Should minimum order amount include tax?
     *
     * @return bool
     */
    public function isMinimumOrderIncludingTax(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'sales/minimum_order/tax_including',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get text to display for minimum order amount.
     *
     * @return string
     */
    public function getMinimumOrderText(): string
    {
        return (string) $this->scopeConfig->getValue(
            'sales/minimum_order/description',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Map ISO code language to autopay correct language (currently only pl and en).
     *
     * @param  string  $locale
     *
     * @return string
     */
    private function getLanguageFromLocale(string $locale): string
    {
        $locales = [
            'pl_' => 'pl', // polski
            'en_' => 'en', // angielski
        ];

        $prefix = substr($locale, 0, 3);

        if (isset($locales[$prefix])) {
            return $locales[$prefix];
        }

        return 'en';
    }
}