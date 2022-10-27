<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Block\Analytics;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Data as CatalogHelper;

class GaData extends Template
{
    /** @var Resolver */
    private $layerResolver;

    /** @var Json */
    private $jsonSerializer;

    /** @var CatalogHelper */
    private $catalogHelper;

    /** @var Analytics */
    private $analytics;

    public function __construct(
        Template\Context $context,
        Resolver $layer,
        Json $jsonSerializer,
        CatalogHelper $catalogHelper,
        Analytics $analytics,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->layerResolver = $layer;
        $this->jsonSerializer = $jsonSerializer;
        $this->catalogHelper = $catalogHelper;
        $this->analytics = $analytics;
    }

    /**
     * Get all available GA events to send.
     *
     * @return array
     */
    public function getAnalyticsData(): array
    {
        if (! $this->analytics->isGoogleAnalytics4Available()) {
            return [];
        }

        try {
            $action = $this->getRequest()->getFullActionName();

            switch ($action) {
                case 'catalog_product_view': // Product detail
                    $this->loadProductViewData();
                    break;
                case 'checkout_index_index':  // Checkout
                case 'onestepcheckout_index_index': // Mageplaza One step check out page
                    $this->loadCheckoutData();
                    break;
            }
        } catch (\Exception $e) {}

        return $this->getDefault();
    }

    /**
     * Get JSON Serializer instance.
     *
     * @return Json
     */
    public function getJsonSerializer(): Json
    {
        return $this->jsonSerializer;
    }

    /**
     * Return events for default page.
     *
     * @return array
     */
    private function getDefault(): array
    {
        if (! $this->analytics->isGoogleAnalytics4Available()) {
            return [];
        }

        $events = $this->analytics->getEvents();
        $this->analytics->clearEvents();

        foreach ($events as &$event) {
            if ($event['event'] === 'view_item_list') {
                foreach ($event['data']['items'] as &$item) {
                    $item['list_name'] = $this->getItemListName();
                }
            }
        }

        return $events;
    }

    /**
     * Get item name for list.
     *
     * @return string
     */
    private function getItemListName(): string
    {
        $category = $this->layerResolver->get()->getCurrentCategory();

        if ($category) {
            return $category->getName();
        }

        return '';
    }

    /**
     * Add information about product page to GA event queue.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    private function loadProductViewData(): void
    {
        $product = $this->catalogHelper->getProduct();

        if ($product) {
            $items = [
                [
                    'id' => $product->getSku(),
                    'name' => $product->getName(),
                    'category' => $this->analytics->getCategoryName($product),
                    'quantity' => 1,
                    'price' => $this->analytics->getPrice($product)
                ]
            ];

            $this->analytics->addEvent([
                'event' => 'view_item',
                'data' => [
                    'items' => $items,
                ],
            ]);
        }
    }

    /**
     * Add information about checkout progress to GA events queue.
     *
     * @param bool $checkout
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loadCheckoutData(bool $checkout = false): void
    {
        $quote = $this->analytics->getQuote();

        $items = $quote->getAllVisibleItems();
        if (empty($items)) {
            return;
        }

        $products = [];
        foreach ($items as $item) {
            $products[] = [
                'id'        => $item->getSku(),
                'name'      => $item->getName(),
                'category'  => $this->analytics->getCategoryName($item->getProduct()),
                'quantity'  => $item->getQtyOrdered() ?: $item->getQty(),
                'price'     => $this->analytics->convertPrice((float) $item->getBasePrice())
            ];
        }

        $this->analytics->addEvent([
            'event' => $checkout ? 'checkout_progress' : 'begin_checkout',
            'data' => [
                'items'         => $products,
                'coupon'        => $quote->getCouponCode() ?: '',
                'checkout_step' => $checkout ? 2 : 1,
            ]
        ]);

        $this->analytics->addEvent([
            'event' => 'set_checkout_option',
            'data' => [
                'checkout_step'   => $checkout ? 2 : 1,
                'checkout_option' => 'checkout',
            ]
        ]);
    }
}
