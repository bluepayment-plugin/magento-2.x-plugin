<?php

namespace BlueMedia\BluePayment\Model;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use SimpleXMLElement;
use BlueMedia\BluePayment\Logger\Logger;

class GenerateOrderBasket
{
    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Order $order): string
    {
        $xml = new SimpleXMLElement('<productList/>');

        foreach ($order->getItems() as $item) {
            $this->parseItem($xml, $item);
        }

        $this->addShipping($xml, $order);

        return $this->xmlToBase64($xml);
    }

    public function parseItem(SimpleXMLElement $xml, OrderItemInterface $item): void
    {
        $total = $this->getItemTotal($item);

        $this->logger->info('GenerateOrderBasket:' . __LINE__, [
            'total' => $total,
            'name' => $item->getName(),
            'rowTotal' => $item->getRowTotal(),
            'rowTotalInclTax' => $item->getRowTotalInclTax(),
            'taxAmount' => $item->getTaxAmount(),
            'discountAmount' => $item->getDiscountAmount(),
            'qtyOrdered' => $item->getQtyOrdered(),
        ]);

        if ($total > 0) {
            $product = $xml->addChild('product');

            $product->addChild('subAmount', $this->formatAmount($total));
            $params = $product->addChild('params');

            $this->addParam($params, 'productName', $item->getName(), 'Nazwa');
            $this->addParam($params, 'productID', $item->getSku(), 'SKU');
        }
    }

    public function getItemTotal(OrderItemInterface $item): float
    {
        $total = $item->getRowTotalInclTax() ?? ($item->getRowTotal() + $item->getTaxAmount() ?? 0);
        $total -= ($item->getDiscountAmount() ?? 0);

        if ($total > 0) {
            return round($total, 2);
        }

        return 0;
    }

    public function addShipping(SimpleXMLElement $xml, Order $order): void
    {
        $total = $this->getShippingTotal($order);

        $this->logger->info('GenerateOrderBasket:' . __LINE__, [
            'total' => $total,
            'shippingAmount' => $order->getShippingAmount(),
            'shippingDiscountAmount' => $order->getShippingDiscountAmount(),
            'shippingTaxAmount' => $order->getShippingTaxAmount(),
            'shippingDescription' => $order->getShippingDescription(),
        ]);

        if ($total > 0) {
            $product = $xml->addChild('product');

            $product->addChild('subAmount', $this->formatAmount($total));
            $params = $product->addChild('params');

            $this->addParam($params, 'productName', $order->getShippingDescription(), 'Nazwa');
            $this->addParam($params, 'productID', 'SHIPPING', 'SKU');
        }
    }

    public function getShippingTotal(Order $order): float
    {
        $total = (float)$order->getShippingAmount();
        $total += (float)$order->getShippingTaxAmount();
        $total += (float)$order->getShippingDiscountAmount();

        return $total;
    }

    public function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    public function addParam(SimpleXMLElement $params, string $name, string $value, ?string $title = null): void
    {
        $element = $params->addChild('param');
        $element->addAttribute('name', $name);
        $element->addAttribute('value', $value);
        if ($title) {
            $element->addAttribute('title', $title);
        }
    }

    public function xmlToBase64(SimpleXMLElement $xml): string
    {
        $base64 = base64_encode($xml->asXML());

        $this->logger->info('GenerateOrderBasket:' . __LINE__, [
            'xml' => $xml->asXML(),
            'base64' => $base64,
        ]);

        return $base64;
    }
}
