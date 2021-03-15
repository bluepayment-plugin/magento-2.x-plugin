<?php

namespace BlueMedia\BluePayment\Model\Multishipping;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\Payment;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\Scope;
use Magento\Framework\Convert\ConvertArray;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Session\Generic;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use function DeepCopy\deep_copy;

class PlaceOrder implements PlaceOrderInterface
{
    /**
     * @var OrderManagementInterface
     */
    public $orderManagement;

    /** @var CollectionFactory */
    public $gatewayFactory;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var ConvertArray */
    public $convertArray;

    /** @var Data */
    public $helper;

    /** @var Curl */
    public $curl;

    /**
     * @var Generic
     */
    public $session;

    /**
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        CollectionFactory $gatewayFactory,
        ScopeConfigInterface $scopeConfig,
        ConvertArray $convertArray,
        Data $helper,
        Curl $curl,
        Generic $session
    ) {
        $this->orderManagement = $orderManagement;
        $this->gatewayFactory = $gatewayFactory;
        $this->scopeConfig = $scopeConfig;
        $this->convertArray = $convertArray;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function place(array $orderList): array
    {
        if (empty($orderList)) {
            return [];
        }

        $errorList = $this->createPayment($orderList);
        foreach ($orderList as $order) {
            try {
                $this->orderManagement->place($order);
            } catch (\Exception $e) {
                $incrementId = $order->getIncrementId();
                $errorList[$incrementId] = $e;
            }
        }

        return $errorList;
    }

    /**
     * @param Order[] $orderList
     *
     * @return array Error list
     */
    protected function createPayment(array $orderList = [])
    {
        $orderToPayment = [];
        $productsList = [];
        $totalToPay = 0;

        foreach ($orderList as $order) {
            $payment = $order->getPayment();

            if ($payment->getMethod() == Payment::METHOD_CODE) {
                $orderToPayment[] = $order;

                if (!isset($gatewayId)) {
                    $gatewayId = $payment->getAdditionalInformation('gateway_id');
                    $websiteCode = $order->getStore()->getWebsite()->getCode();
                    $currency = $order->getOrderCurrencyCode();
                    $serviceId = $this->scopeConfig->getValue(
                        'payment/bluepayment/' . strtolower($currency) . '/service_id',
                        ScopeInterface::SCOPE_WEBSITE,
                        $websiteCode
                    );
                    // Set Payment Channel to Order
                    $gateway = $this->gatewayFactory->create()
                        ->addFieldToFilter('gateway_service_id', $serviceId)
                        ->addFieldToFilter('gateway_id', $gatewayId)
                        ->getFirstItem();
                }

                $order->setBlueGatewayId((int) $gatewayId);
                $order->setPaymentChannel($gateway->getData('gateway_name'));

                foreach ($order->getAllItems() as $item) {
                    $productsList[] = [
                        'subAmount' => $item->getRowTotalInclTax(),
                        'params' => [
                            'productName' => ($item->getQtyOrdered() * 1) . 'x ' . $item->getName(),
                        ],
                    ];
                }

                if (!$order->getIsVirtual() && ((double)$order->getShippingAmount() || $order->getShippingDescription())) {
                    $productsList[] = [
                        'subAmount' => (double)$order->getShippingAmount(),
                        'params' => [
                            'productName' => 'Dostawa',# __('Shipping & Handling')->render(),
                        ],
                    ];
                }

                $totalToPay += $order->getGrandTotal();
            }
        }

        // @ToDo Create Payment
        $amount = number_format(round($totalToPay, 2), 2, '.', '');
        $currency = $order->getOrderCurrencyCode();

        // Config
        $serviceId = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $sharedKey = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );

        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();

        $xml = new \SimpleXMLElement('<productList/>');

        foreach ($productsList as $item) {
            $subAmount = number_format(round($item['subAmount'], 2), 2, '.', '');

            $product = $xml->addChild('product');
            $product->addChild('subAmount', $subAmount);
            $params = $product->addChild('params');

            foreach ($item['params'] as $key => $value) {
                $param = $params->addChild('param');
                $param->addAttribute('name', $key);
                $param->addAttribute('value', $value);
            }
        }

        $params = [
            'ServiceID' => $serviceId,
            'OrderID' => 'QUOTE_'.$order->getQuoteId(),
            'Amount' => $amount,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
            'Products' => base64_encode($xml->asXML()),
            'GatewayID' => $gatewayId
        ];

        $hashArray = array_values(Payment::sortParams($params));
        $hashArray[] = $sharedKey;

        $params['Hash'] = $this->helper->generateAndReturnHash($hashArray);

        $testMode = $this->scopeConfig->getValue(
            'payment/bluepayment/test_mode',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );

        $urlGateway = $this->scopeConfig->getValue(
            'payment/bluepayment/' . ($testMode ? 'test' : 'prod') . '_address_url',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );

        $this->curl->addHeader('BmHeader', 'pay-bm-continue-transaction-url');
        $this->curl->post($urlGateway, $params);
        $response = $this->curl->getBody();

        $xml = simplexml_load_string($response);

        $redirectUrl = property_exists($xml, 'redirecturl') ? (string)$xml->redirecturl : null;
        $this->session->setAuthorizationRedirect($redirectUrl);

        return [];
    }
}
