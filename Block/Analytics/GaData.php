<?php

namespace BlueMedia\BluePayment\Block\Analytics;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Catalog\Model\Layer\Resolver;
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

    public function getAnalyticsData(): array
    {
        try {
            $action = $this->getRequest()->getFullActionName();

            switch ($action) {
                case 'catalog_product_view': // Product detail
                    $this->loadProductViewData();
                    break;
                case 'checkout_cart_index':   // Cart
                    $this->loadCheckoutData();
                    break;
                case 'checkout_index_index':  // Checkout
                case 'onestepcheckout_index_index': // Mageplaza One step check out page
                    $this->loadCheckoutData(true);
                    break;
            }
        } catch (\Exception $e) {}

        return $this->getDefault();
    }

    public function getJsonSerializer()
    {
        return $this->jsonSerializer;
    }

    private function getDefault(): array
    {
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

    private function getItemListName(): string
    {
        $category = $this->layerResolver->get()->getCurrentCategory();

        if ($category) {
            return $category->getName();
        }

        return '';
    }

    private function loadProductViewData()
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

    private function loadCheckoutData($checkout = false)
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
                'price'     => $this->analytics->convertPrice($item->getBasePrice())
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
                'checkout_option' => $checkout ? 'checkout' : 'cart',
            ]
        ]);
    }
}
