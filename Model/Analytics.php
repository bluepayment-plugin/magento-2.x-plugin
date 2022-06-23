<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Helper\Analytics\Data as AnalyticsHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Pricing\Price\FinalPrice;
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

    public function isGoogleAnalytics4Available()
    {
        return $this->analyticsHelper->isGoogleAnalytics4Available();
    }

    public function getAccountIdGa4()
    {
        return $this->analyticsHelper->getAccountIdGa4();
    }

    public function getApiSecret()
    {
        return $this->analyticsHelper->getApiSecret();
    }

    public function addEvent($event)
    {
        $data = $this->checkoutSession->getAnalyticsData();
        if (!is_array($data)) {
            $data = [];
        }

        $data[] = $event;

        $this->checkoutSession->setAnalyticsData($data);

        return $data;
    }

    public function getEvents()
    {
        $data = $this->checkoutSession->getAnalyticsData();

        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

    public function clearEvents()
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
     * @param  Product|ProductInterface  $product
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryName($product)
    {
        $categoryIds = $product->getCategoryIds();
        $categoryName = '';
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                $categoryName .= '/'.$category->getName();
            }
        }

        return trim($categoryName, '/');
    }

    /**
     * @param  float  $product
     *
     * @return float
     */
    public function convertPrice($price)
    {
        return $this->priceCurrency->convert($price);
    }

    /**
     * @return CartInterface|Quote
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    public function parseProductCollection(Collection $collection)
    {
        $position = 0;

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
