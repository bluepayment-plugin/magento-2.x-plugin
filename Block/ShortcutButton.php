<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Api\ShouldShowAutopayInterface;
use BlueMedia\BluePayment\Model\Autopay\ConfigProvider;
use Exception;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\View\Element\Template\Context;

class ShortcutButton extends Template implements ShortcutInterface
{
    private const ALIAS_ELEMENT_INDEX = 'alias';

    /** @var string Scope - product, minicart or cart */
    private $scope = 'product';

    /** @var ConfigProvider */
    private $autopayConfigProvider;

    /** @var ShouldShowAutopayInterface */
    private $shouldShowAutopay;

    /**
     * Button constructor.
     *
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param ShouldShowAutopayInterface $shouldShowAutopay
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ShouldShowAutopayInterface $shouldShowAutopay,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->autopayConfigProvider = $configProvider;
        $this->shouldShowAutopay = $shouldShowAutopay;
    }

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * Set button scope - product, minicart or cart.
     *
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope): ShortcutButton
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get button scope - product, minicart or cart.
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Check whether APC is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->shouldShowAutopay->shouldShow();
    }

    /**
     * Get Autopay Merchant ID
     *
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->autopayConfigProvider->getMerchantId();
    }

    /**
     * Get APC language based on shop language (currently only pl and en).
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->autopayConfigProvider->getLanguage();
    }

    /**
     * Is minimum order amount enabled?
     *
     * @return bool
     */
    public function isMinimumOrderActive(): bool
    {
        return $this->autopayConfigProvider->isMinimumOrderActive();
    }

    /**
     * Returns minimum order amount.
     *
     * @return float
     */
    public function getMinimumOrderAmount(): float
    {
        return $this->autopayConfigProvider->getMinimumOrderAmount();
    }

    /**
     * Should minimum order amount include discount?
     *
     * @return bool
     */
    public function isMinimumOrderIncludingDiscount(): bool
    {
        return $this->autopayConfigProvider->isMinimumOrderIncludingDiscount();
    }

    /**
     * Should minimum order amount include tax?
     *
     * @return bool
     */
    public function isMinimumOrderIncludingTax(): bool
    {
        return $this->autopayConfigProvider->isMinimumOrderIncludingTax();
    }

    /**
     * Get text to display for minimum order amount.
     *
     * @return string
     */
    public function getMinimumOrderText(): string
    {
        return $this->autopayConfigProvider->getMinimumOrderText();
    }

    /**
     * Get autopay button style.
     *
     * @param string $scope Scope - product, minicart or cart
     * @return array{
     *    'theme': string,
     *    'width: string,
     *    'rounded': string,
     * }
     */
    public function getButtonStyleConfiguration(string $scope = 'product'): array
    {
        return [
            'theme' => $this->autopayConfigProvider->getButtonTheme($scope),
            'width' => $this->autopayConfigProvider->getButtonWidth($scope),
            'rounded' => $this->autopayConfigProvider->getButtonRounded($scope),
            'arrangement' => $this->autopayConfigProvider->getButtonArrangement($scope),
        ];
    }

    /**
     * Get Autopay Checkout button margins.
     *
     * @return array{
     *    'top': string,
     *    'bottom: string,
     * }
     */
    public function getButtonMargins(): array
    {
        return [
            'top' => $this->autopayConfigProvider->getButtonMarginTop(),
            'bottom' => $this->autopayConfigProvider->getButtonMarginBottom(),
        ];
    }

    /**
     * Get random ID for block.
     *
     * @return string
     * @throws Exception
     */
    public function getRandomId(): string
    {
        return (string) random_int(10, 1000);
    }
}
