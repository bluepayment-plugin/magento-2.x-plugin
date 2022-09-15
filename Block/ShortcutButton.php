<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\Autopay\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class ShortcutButton extends Template implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';

    private $isMiniCart = false;
    private $autopayConfigProvider;

    /**
     * Button constructor.
     *
     * @param  Context  $context
     * @param  ConfigProvider  $configProvider
     * @param  array  $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->autopayConfigProvider = $configProvider;
    }

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    public function setIsInCatalogProduct($isCatalogProduct)
    {
        $this->isMiniCart = !$isCatalogProduct;

        return $this;
    }

    public function getIsInCatalogProduct()
    {
        return ! $this->isMiniCart;
    }

    public function isActive(): bool
    {
        return $this->autopayConfigProvider->isActive();
    }

    public function getMerchantId()
    {
        return $this->autopayConfigProvider->getMerchantId();
    }

    public function getLanguage(): string
    {
        return $this->autopayConfigProvider->getLanguage();
    }
}
