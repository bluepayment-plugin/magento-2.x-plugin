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

    public function getMerchantId()
    {
        return $this->autopayConfigProvider->getMerchantId();
    }

    public function getLanguage()
    {
        $locale = $this->_scopeConfig
            ->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE
            );

        return $this->getLanguageFromLocale($locale);
    }

    private function getLanguageFromLocale($locale)
    {
        $locales = [
            'pl_' => 'pl', // polski
            'en_' => 'en', // angielski
        ];

        $prefix = substr($locale, 0, 3);

        if (isset($locales[$prefix])) {
            return $locales[$prefix];
        }

        return 'eb';
    }
}
