<?php

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductListObserver implements ObserverInterface
{
    /** @var Analytics  */
    private $analytics;

    public function __construct(
        Analytics $analytics
    ) {
        $this->analytics = $analytics;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $items = [];
        $productCollection = $observer->getEvent()->getCollection();
        $position = 0;

        foreach ($productCollection as $product) {
            $items[] = [
                'id'            => $product->getSku(),
                'name'          => $product->getName(),
                'category'      => $this->analytics->getCategoryName($product),
                'list_position' => ++$position,
                'quantity'      => 1,
                'price'         => $this->analytics->getPrice($product),
            ];
        }

        if (count($items)) {
            $this->analytics->addEvent([
                'event' => 'view_item_list',
                'data' => [
                    'items' => $items,
                ],
            ]);
        }
    }
}
