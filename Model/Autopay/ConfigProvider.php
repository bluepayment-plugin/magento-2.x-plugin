<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Autopay;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
    public const STATUS_HIDDEN = 2;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * Autopay Checkout ConfigProvider constructor
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
        return (int) $this->scopeConfig->getValue(
            'payment/autopay/active',
            ScopeInterface::SCOPE_STORE
        ) === self::STATUS_ENABLED;
    }

    /**
     * Check whether APC is hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return (int) $this->scopeConfig->getValue(
            'payment/autopay/active',
            ScopeInterface::SCOPE_STORE
        ) === self::STATUS_HIDDEN;
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
     * Get Autopay Secret Key
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
     * Get Autopay Merchant ID
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
     * Get Autopay button theme - dark or light.
     *
     * @param string $scope Scope of the config - product, cart, minicart.
     * @return string
     */
    public function getButtonTheme(string $scope = 'product'): string
    {
        return $this->getButtonConfigValue('theme', $scope);
    }

    /**
     * Get Autopay button width - standard (153px) or full (100%).
     *
     * @param string $scope Scope of the config - product, cart or minicart.
     * @return string
     */
    public function getButtonWidth(string $scope = 'product'): string
    {
        return $this->getButtonConfigValue('width', $scope);
    }

    /**
     * Get Autopay button rounded style - rounded or square.
     *
     * @param string $scope Scope of the config - product, cart or minicart.
     * @return string
     */
    public function getButtonRounded(string $scope = 'product'): string
    {
        return $this->getButtonConfigValue('rounded', $scope);
    }

    /**
     * Get Autopay button arrangement - horizontal, horizontal-reversed, vertical or vertical-reversed.
     *
     * @param string $scope Scope of the config - product, cart or minicart.
     * @return string
     */
    public function getButtonArrangement(string $scope = 'product'): string
    {
        return $this->getButtonConfigValue('arrangement', $scope);
    }

    /**
     * Get top margin for Autopay button.
     *
     * @param string $scope Scope of the config - product, cart or minicart.
     * @return string
     */
    public function getButtonMarginTop(string $scope = 'product'): string
    {
        return $this->getButtonConfigValue('margin_top', $scope);
    }

    /**
     * Get bottom margin for Autopay button (margin-0, margin-10, margin-15, margin-20).
     *
     * @param string $scope Scope of the config - product, cart or minicart.
     * @return string
     */
    public function getButtonMarginBottom(string $scope = 'product'): string
    {
        return $this->getButtonConfigValue('margin_bottom', $scope);
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

    /**
     * Get config value for Autopay button with selected scope (or default if it's not set).
     *
     * @param string $path Path to config value.
     * @param string $scope Scope of the config - product, cart, minicart.
     * @return string
     */
    private function getButtonConfigValue(string $path, string $scope = 'product'): string
    {
        if (in_array($scope, ['minicart', 'cart'])) {
            $value = $this->scopeConfig->getValue(
                'payment/autopay/button/' . $scope . '/' . $path,
                ScopeInterface::SCOPE_STORE
            );

            if ($value) {
                return (string) $value;
            }
        }

        return (string) $this->scopeConfig->getValue(
            'payment/autopay/button/' . $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
