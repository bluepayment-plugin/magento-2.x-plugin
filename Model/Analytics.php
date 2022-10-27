<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Helper\Analytics\Data as AnalyticsHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class Analytics
{
    /** @var AnalyticsHelper */
    private $analyticsHelper;

    /** @var CategoryRepositoryInterface */
    private $categoryRepository;

    /** @var Session */
    private $checkoutSession;

    /** @var PriceCurrencyInterface */
    private $priceCurrency;

    /** @var Data */
    private $taxHelper;

    public function __construct(
        AnalyticsHelper $analyticsHelper,
        CategoryRepositoryInterface $categoryRepository,
        Session $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        Data $taxHelper
    ) {
        $this->analyticsHelper = $analyticsHelper;
        $this->categoryRepository = $categoryRepository;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->taxHelper = $taxHelper;
    }

    /**
     * Check is Google Analytics 4 available.
     *
     * @return bool
     */
    public function isGoogleAnalytics4Available(): bool
    {
        return $this->analyticsHelper->isGoogleAnalytics4Available();
    }

    /**
     * Returns Account ID for Google Analytics 4.
     *
     * @return string|null
     */
    public function getAccountIdGa4(): ?string
    {
        return $this->analyticsHelper->getAccountIdGa4();
    }

    /**
     * Returns API Secret for Google Analytics 4.
     *
     * @return string|null
     */
    public function getApiSecret(): ?string
    {
        return $this->analyticsHelper->getApiSecret();
    }

    /**
     * Add new GA event to queue.
     *
     * @param array $event
     * @return array
     */
    public function addEvent(array $event): array
    {
        $data = $this->checkoutSession->getAnalyticsData();
        if (!is_array($data)) {
            $data = [];
        }

        $data[] = $event;

        $this->checkoutSession->setAnalyticsData($data);

        return $data;
    }

    /**
     * Get all queued GA events.
     *
     * @return array
     */
    public function getEvents(): array
    {
        $data = $this->checkoutSession->getAnalyticsData();

        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

    /**
     * Remove all queued GA events.
     *
     * @return void
     */
    public function clearEvents(): void
    {
        $this->checkoutSession->setAnalyticsData([]);
    }

    /**
     * @param  Product|ProductInterface  $product
     *
     * @return float
     */
    public function getPrice($product)
    {
        return $this->taxHelper->getTaxPrice(
            $product,
            $product->getFinalPrice()
        );
    }

    /**
     * Get category name for product.
     *
     * @param Product|ProductInterface $product
     * @return ?string
     * @throws NoSuchEntityException
     */
    public function getCategoryName($product): ?string
    {
        $category = $product->getCategory();

        if ($category) {
            $parents = $category->getParentCategories();
            if ($parents) {
                $categoryNames = [];

                foreach ($category->getParentCategories() as $parentCategory) {
                    $categoryNames[] = $parentCategory->getName();
                }

                return implode('/', $categoryNames);
            }

            return $category->getName();
        }

        return null;
    }

    /**
     * Convert price to base currency.
     *
     * @param float $price
     * @return float
     */
    public function convertPrice(float $price): float
    {
        return $this->priceCurrency->convert($price);
    }

    /**
     * Get quote from session.
     *
     * @return CartInterface|Quote
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Parse product collection and add view_item_list GA event to queue.
     *
     * @param Collection $collection
     * @return void
     * @throws NoSuchEntityException
     */
    public function parseProductCollection(Collection $collection): void
    {
        if (! $this->isGoogleAnalytics4Available()) {
            return;
        }

        $position = 0;

        $items = [];
        foreach ($collection as $product) {
            $items[] = [
                'id'            => $product->getSku(),
                'name'          => $product->getName(),
                'category'      => $this->getCategoryName($product),
                'list_position' => ++$position,
                'quantity'      => 1,
                'price'         => $this->getPrice($product),
            ];
        }

        if (count($items)) {
            $this->addEvent([
                'event' => 'view_item_list',
                'data' => [
                    'items' => $items,
                ],
            ]);
        }
    }
}
