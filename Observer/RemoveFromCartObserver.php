<?php

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Helper\Analytics\Data;
use BlueMedia\BluePayment\Model\Analytics;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class RemoveFromCartObserver implements ObserverInterface
{
    /** @var Data */
    private $data;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Analytics */
    private $analytics;

    public function __construct(
        Data $data,
        ProductRepositoryInterface $productRepository,
        Analytics $analytics
    ) {
        $this->data = $data;
        $this->productRepository = $productRepository;
        $this->analytics = $analytics;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if ($this->data->isGoogleAnalytics4Available()) {
            /** @var CartItemInterface $quoteItem */
            $quoteItem = $observer->getData('quote_item');
            $qty = $quoteItem->getQty();

            $product = $this->productRepository->get($quoteItem->getSku());
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
                'event' => 'remove_from_cart',
                'data' => [
                    'items' => $items,
                ],
            ]);
        }
    }
}
