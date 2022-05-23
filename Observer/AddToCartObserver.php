<?php

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddToCartObserver implements ObserverInterface
{
    /** @var Analytics */
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
        if ($this->analytics->isGoogleAnalytics4Available()) {
            /** @var ProductInterface $product */
            $product = $observer->getData('product');
            $request = $observer->getData('request');

            $qty = $request->getParam('qty') ?: 1;

            $items = [
                [
                    'id' => $product->getSku(),
                    'name' => $product->getName(),
                    'category' => $this->analytics->getCategoryName($product),
                    'quantity' => $qty,
                    'price' => $this->analytics->convertPrice($product->getFinalPrice())
                ]
            ];

            $this->analytics->addEvent([
                'event' => 'add_to_cart',
                'data' => [
                    'items' => $items,
                ],
            ]);
        }
    }
}
