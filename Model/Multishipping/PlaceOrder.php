<?php

namespace BlueMedia\BluePayment\Model\Multishipping;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\Card;
use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Payment;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Convert\ConvertArray;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Session\Generic;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use BlueMedia\BluePayment\Logger\Logger;

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

    /** @var Generic */
    public $session;

    /** @var CardCollectionFactory */
    public $cardCollectionFactory;

    /** @var Logger */
    public $logger;

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
        Generic $session,
        CardCollectionFactory $cardCollectionFactory,
        Logger $logger
    ) {
        $this->orderManagement = $orderManagement;
        $this->gatewayFactory = $gatewayFactory;
        $this->scopeConfig = $scopeConfig;
        $this->convertArray = $convertArray;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->session = $session;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->logger = $logger;
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
    protected function createPayment(array $orderList = []): array
    {
        $productsList = [];
        $totalToPay = 0;

        foreach ($orderList as $order) {
            $payment = $order->getPayment();

            $this->logger->info('PlaceOrder:' . __LINE__, ['orderID' => $order->getIncrementId()]);

            if ($payment->getMethod() == Payment::METHOD_CODE) {
                $gatewayId = $payment->getAdditionalInformation('gateway_id');
                $currency = $order->getOrderCurrencyCode();
                $serviceId = $this->scopeConfig->getValue(
                    'payment/bluepayment/' . strtolower($currency) . '/service_id',
                    ScopeInterface::SCOPE_STORE
                );
                // Set Payment Channel to Order
                $gateway = $this->gatewayFactory->create()
                    ->addFieldToFilter('gateway_service_id', $serviceId)
                    ->addFieldToFilter('gateway_id', $gatewayId)
                    ->getFirstItem();

                $order->setBlueGatewayId((int) $gatewayId);
                $order->setPaymentChannel($gateway->getData('gateway_name'));

                $this->logger->info('PlaceOrder:' . __LINE__, ['gatewayID' => $gatewayId]);

                foreach ($order->getAllItems() as $item) {
                    $productsList[] = [
                        'subAmount' => $item->getRowTotalInclTax(),
                        'params' => [
                            'productName' => ($item->getQtyOrdered() * 1) . 'x ' . $item->getName(),
                        ],
                    ];
                }

                if (!$order->getIsVirtual()
                    && ((double)$order->getShippingAmount() || $order->getShippingDescription())
                ) {
                    $productsList[] = [
                        'subAmount' => (double)$order->getShippingAmount(),
                        'params' => [
                            'productName' => __('Shipping & Handling')->render(),
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
            ScopeInterface::SCOPE_STORE
        );
        $sharedKey = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/shared_key',
            ScopeInterface::SCOPE_STORE
        );

        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();

        $xml = new \SimpleXMLElement('<productList/>');

        foreach ($productsList as $item) {
            if ($item['subAmount'] === null) {
                continue;
            }

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
            'OrderID' => Payment::QUOTE_PREFIX . $order->getQuoteId(),
            'Amount' => $amount,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
            'Products' => base64_encode($xml->asXML()),
            'GatewayID' => $gatewayId
        ];

        /* Płatność one-click kartowa */
        if (ConfigProvider::ONECLICK_GATEWAY_ID == $gatewayId) {
            $cardIndex = $payment->getAdditionalInformation('gateway_index');

            /** @var Card $card */
            $card = $this->cardCollectionFactory
                ->create()
                ->addFieldToFilter('card_index', (string) $cardIndex)
                ->addFieldToFilter('customer_id', (string) $customerId)
                ->getFirstItem();

            if ($cardIndex == -1 || $card == null) {
                $params['RecurringAcceptanceState'] = 'ACCEPTED';
                $params['RecurringAction'] = 'INIT_WITH_PAYMENT';
            } else {
                $params['RecurringAction'] = 'MANUAL';
                $params['ClientHash'] = $card->getClientHash();
            }
        } else {
            $agreementsIds  = $payment->hasAdditionalInformation('agreements_ids')
                ? array_unique(explode(',', $payment->getAdditionalInformation('agreements_ids') ?: ''))
                : [];

            $agreementId = reset($agreementsIds);

            if ($agreementId) {
                $params['DefaultRegulationAcceptanceState'] = 'ACCEPTED';
                $params['DefaultRegulationAcceptanceID'] = $agreementId;
                $params['DefaultRegulationAcceptanceTime'] = date('Y-m-d H:i:s');
            }
        }

        $hashArray = array_values(Payment::sortParams($params));
        $hashArray[] = $sharedKey;

        $params['Hash'] = $this->helper->generateAndReturnHash($hashArray);

        $testMode = $this->scopeConfig->getValue(
            'payment/bluepayment/test_mode',
            ScopeInterface::SCOPE_STORE
        );

        $urlGateway = $this->scopeConfig->getValue(
            'payment/bluepayment/' . ($testMode ? 'test' : 'prod') . '_address_url',
            ScopeInterface::SCOPE_STORE
        );

        $this->logger->info('PlaceOrder:' . __LINE__, ['params' => $params]);

        $this->curl->addHeader('BmHeader', 'pay-bm-continue-transaction-url');
        $this->curl->post($urlGateway, $params);
        $response = $this->curl->getBody();

        $xml = simplexml_load_string($response);

        $redirectUrl = property_exists($xml, 'redirecturl') ? (string)$xml->redirecturl : null;
        $this->session->setAuthorizationRedirect($redirectUrl);

        $this->logger->info('PlaceOrder:' . __LINE__, ['redirectUrl' => $redirectUrl]);

        return [];
    }
}
