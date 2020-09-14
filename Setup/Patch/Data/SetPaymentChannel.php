<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace BlueMedia\BluePayment\Setup\Patch\Data;

use BlueMedia\BluePayment\Helper\Gateways;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory as GatewaysCollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\Transaction\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use BlueMedia\BluePayment\Model\Transaction;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class SetPaymentChannel implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface $moduleDataSetup */
    private $moduleDataSetup;

    /** @var OrderCollectionFactory $orderCollectionFactory */
    private $orderCollectionFactory;

    /** @var TransactionCollectionFactory $transactionCollectionFactory */
    private $transactionCollectionFactory;

    /** @var GatewaysCollectionFactory */
    private $gatewaysCollectionFactory;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param GatewaysCollectionFactory $gatewaysCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        OrderCollectionFactory $orderCollectionFactory,
        TransactionCollectionFactory $transactionCollectionFactory,
        GatewaysCollectionFactory $gatewaysCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->gatewaysCollectionFactory = $gatewaysCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $serviceIds = [];
        $config = $this->scopeConfig->getValue('payment/bluepayment');
        $currencies = Gateways::$currencies;

        // Get base service IDs
        foreach ($currencies as $currency) {
            if (isset($config[strtolower($currency)]['service_id'])) {
                $serviceIds[] = $config[strtolower($currency)]['service_id'];
            }
        }

        // Get gateways names
        $gatewayModels = $this->gatewaysCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('gateway_service_id', ['in' => $serviceIds]);

        $gateways = [];

        foreach ($gatewayModels as $gateway) {
            $gateways[$gateway->getData('gateway_currency')][$gateway->getData('gateway_id')]
                = $gateway->getData('gateway_name');
        }

        $orders = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*');
        $orders->getSelect()
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id = payment.parent_id',
                ['method']
            )
            ->where('payment.method = ?', 'bluepayment');

        foreach ($orders as $order) {
            /** @var $order Order */

            /** @var Transaction $transaction */
            $transaction = $this->transactionCollectionFactory->create()
                ->addFieldToFilter('order_id', $order->getIncrementId())
                ->setOrder('payment_status', 'DESC')
                ->getFirstItem();

            if (! $transaction->isEmpty()) {
                $currency = $transaction->getData('currency');
                $gatewayId = $transaction->getData('gateway_id');
                $gatewayName = $gateways[$currency][$gatewayId];

                $order->setBlueGatewayId((int) $gatewayId);
                $order->setPaymentChannel($gatewayName);
                $order->save();
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}
