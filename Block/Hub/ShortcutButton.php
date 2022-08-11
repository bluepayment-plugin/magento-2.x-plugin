<?php

namespace BlueMedia\BluePayment\Block\Hub;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\ShortcutInterface;

class ShortcutButton extends Template implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';
    private $isMiniCart = false;

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
}
