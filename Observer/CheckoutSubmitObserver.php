<?php

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Helper\Analytics\Data;
use BlueMedia\BluePayment\Logger\Logger;
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
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CheckoutSubmitObserver implements ObserverInterface
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Analytics */
    private $analytics;

    /** @var Logger */
    private $logger;

    /** @var Curl */
    private $curl;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Analytics $analytics,
        Logger $logger,
        Curl $curl
    ) {
        $this->productRepository = $productRepository;
        $this->analytics = $analytics;
        $this->logger = $logger;
        $this->curl = $curl;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('CheckoutSubmitObserver:' . __LINE__);

        if ($this->analytics->isGoogleAnalytics4Available()) {
            /** @var OrderInterface $order */
            $order = $observer->getData('order');

            /** @var CartInterface $quote */
            $quote = $observer->getData('quote');

            $clientId = $quote->getGaClientId();
            $apiSecret = $this->analytics->getApiSecret();

            $this->logger->info('CheckoutSubmitObserver:' . __LINE__.' - Checkout submit', [
                'quote_id' => $quote->getId(),
                'client_id' => $clientId,
                'api_secret' => $apiSecret
            ]);

            if ($clientId && $apiSecret) {
                $items = [];

                foreach ($order->getItems() as $orderItem) {
                    $product = $this->productRepository->get($orderItem->getSku());

                    $item = [
                        'item_id' => $orderItem->getSku(),
                        'item_name' => $orderItem->getName(),
                        'quantity' => $orderItem->getQtyOrdered(),
                        'tax' => $orderItem->getTaxAmount(),
                        'price' => $orderItem->getRowTotal(),
                    ];

                    if ($product) {
                        $item['item_category'] = $this->analytics->getCategoryName($product);
                    }

                    $items[] = $item;
                }

                $payload = [
                    'client_id' => $clientId,
                    'events' => [[
                        'name' => 'purchase',
                        'params' => [
                            'transaction_id' => $order->getIncrementId(),
                            'value' => $order->getGrandTotal(),
                            'tax' => $order->getTaxAmount(),
                            'shipping' => $order->getShippingAmount(),
                            'currency' => $order->getOrderCurrencyCode(),
                            'items' => $items,
                        ],
                    ]],
                ];

                if ($order->getCouponCode()) {
                    $payload['events']['params']['coupon'] = $order->getCouponCode();
                }

                $urlParams = [
                    'api_secret' => $apiSecret,
                    'measurement_id' => $this->analytics->getAccountIdGa4(),
                ];
                $url = 'https://www.google-analytics.com/mp/collect?' . http_build_query($urlParams);

                $this->curl->addHeader('Content-Type', 'application/json');
                $this->curl->post($url, json_encode($payload));

                $statusCode = $this->curl->getStatus();
                $response = $this->curl->getBody();

                $this->logger->info('CheckoutSubmitObserver:' . __LINE__.' - Checkout submit', [
                    'urlParams' => $urlParams,
                    'payload' => $payload,
                    'statusCode' => $statusCode,
                    'response' => $response,
                ]);
            }
        }
    }
}
