<?php

namespace BlueMedia\BluePayment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;

class SetConsumerFinanceSetting implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var Collection */
    private $configCollection;

    /** @var WriterInterface */
    private $configWriter;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Collection $configCollection,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configCollection = $configCollection;
        $this->configWriter = $configWriter;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $configCollection = $this->configCollection->addPathFilter('payment/bluepayment');

        if ($configCollection->getSize() > 0) {
            // If module is already installed - do not show consumer_finance banners

            $this->configWriter->save('payment/bluepayment/consumer_finance/top', '0');
            $this->configWriter->save('payment/bluepayment/consumer_finance/navigation', '0');
            $this->configWriter->save('payment/bluepayment/consumer_finance/listing', '0');
            $this->configWriter->save('payment/bluepayment/consumer_finance/product', '0');
            $this->configWriter->save('payment/bluepayment/consumer_finance/cart', '0');
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
