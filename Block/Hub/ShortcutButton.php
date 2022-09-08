<?php

namespace BlueMedia\BluePayment\Block\Hub;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\View\Element\Template\Context;

class ShortcutButton extends Template implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';
    private $isMiniCart = false;

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
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
}
