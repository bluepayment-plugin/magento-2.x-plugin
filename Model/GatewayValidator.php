<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\GatewayInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

/**
 * Checks that a gateway (channel) is offered for a store, currency, amount and customer.
 *
 * The filters match ConfigProvider::getActiveGateways(), which builds the list shown at checkout,
 * but take the store explicitly instead of using the current store.
 */
class GatewayValidator
{
    /** @var GatewayCollectionFactory */
    private $gatewayCollectionFactory;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param GatewayCollectionFactory $gatewayCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        GatewayCollectionFactory $gatewayCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->gatewayCollectionFactory = $gatewayCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Validate the gateway for an order, including the amount range for the order total.
     *
     * @param Order $order
     * @param int $gatewayId
     * @return void
     * @throws LocalizedException
     */
    public function validateForOrder(Order $order, int $gatewayId): void
    {
        if ($gatewayId === 0) {
            return;
        }

        $storeId = (int) $order->getStoreId();
        $useBaseCurrency = $this->isUseBaseCurrency($storeId);

        $this->validate(
            $gatewayId,
            $storeId,
            (string) ($useBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode()),
            (float) ($useBaseCurrency ? $order->getBaseGrandTotal() : $order->getGrandTotal()),
            (int) $order->getCustomerId()
        );
    }

    /**
     * Validate the gateway for a quote.
     *
     * The amount range is checked only for multishipping quotes, because the total of other quotes
     * can still change before the order is placed, where it is checked.
     *
     * @param Quote $quote
     * @param int $gatewayId
     * @return void
     * @throws LocalizedException
     */
    public function validateForQuote(Quote $quote, int $gatewayId): void
    {
        if ($gatewayId === 0) {
            return;
        }

        $storeId = (int) $quote->getStoreId();
        $useBaseCurrency = $this->isUseBaseCurrency($storeId);
        $amount = null;
        if ($quote->getIsMultiShipping()) {
            $amount = (float) ($useBaseCurrency ? $quote->getBaseGrandTotal() : $quote->getGrandTotal());
        }

        $this->validate(
            $gatewayId,
            $storeId,
            (string) ($useBaseCurrency ? $quote->getBaseCurrencyCode() : $quote->getQuoteCurrencyCode()),
            $amount,
            (int) $quote->getCustomerId()
        );
    }

    /**
     * Validate the gateway; the amount is rounded like the transaction amount, null skips the range check.
     *
     * @param int $gatewayId
     * @param int $storeId
     * @param string $currency
     * @param float|null $amount
     * @param int $customerId
     * @return void
     * @throws LocalizedException
     */
    public function validate(
        int $gatewayId,
        int $storeId,
        string $currency,
        ?float $amount,
        int $customerId
    ): void {
        if ($gatewayId === 0) {
            return;
        }

        if ($gatewayId === ConfigProvider::ONECLICK_GATEWAY_ID && $customerId === 0) {
            $this->throwNotAvailable();
        }

        $serviceId = $this->scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $gateways = $this->gatewayCollectionFactory->create()
            ->addFieldToFilter(GatewayInterface::STORE_ID, ['eq' => $storeId])
            ->addFieldToFilter(GatewayInterface::SERVICE_ID, ['eq' => $serviceId])
            ->addFieldToFilter(GatewayInterface::CURRENCY, ['eq' => $currency])
            ->addFieldToFilter(GatewayInterface::STATUS, ['eq' => 1])
            ->addFieldToFilter(GatewayInterface::IS_FORCE_DISABLED, ['eq' => 0])
            ->addFieldToFilter(GatewayInterface::GATEWAY_ID, ['eq' => $gatewayId]);

        if ($amount !== null) {
            $amount = (float) number_format($amount, 2, '.', '');
            $gateways
                ->addFieldToFilter(GatewayInterface::MIN_AMOUNT, [
                    ['lteq' => $amount],
                    ['null' => true],
                ])
                ->addFieldToFilter(GatewayInterface::MAX_AMOUNT, [
                    ['gteq' => $amount],
                    ['null' => true],
                ]);
        }

        if (count($gateways->getItems()) === 0) {
            $this->throwNotAvailable();
        }
    }

    /**
     * Check if base currency is used for transactions in the store.
     *
     * @param int $storeId
     * @return bool
     */
    private function isUseBaseCurrency(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'payment/bluepayment/use_base_currency',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Reject the gateway with a customer-safe message.
     *
     * @return void
     * @throws LocalizedException
     */
    private function throwNotAvailable(): void
    {
        throw new LocalizedException(
            __('The selected payment channel is not available. Choose another payment method and try again.')
        );
    }
}
