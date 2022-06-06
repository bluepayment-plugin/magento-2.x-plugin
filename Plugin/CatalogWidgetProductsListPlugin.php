<?php

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;

class CatalogWidgetProductsListPlugin
{
    /** @var Analytics  */
    private $analytics;

    public function __construct(Analytics $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * @param  ProductsList  $subject
     * @param  Collection  $result
     *
     * @return Collection
     */
    public function afterCreateCollection(ProductsList $subject, Collection $result): Collection
    {
        $this->analytics->parseProductCollection($result);
        return $result;
    }
}
