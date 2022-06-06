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
        $productCollection = $observer->getEvent()->getCollection();
        $this->analytics->parseProductCollection($productCollection);
    }
}
