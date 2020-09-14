<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace BlueMedia\BluePayment\Setup\Patch\Data;

use BlueMedia\BluePayment\Helper\Gateways;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class SetServiceId implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface $moduleDataSetup */
    private $moduleDataSetup;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, ScopeConfigInterface $scopeConfig)
    {
        $this->moduleDataSetup = $moduleDataSetup;
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

        foreach (Gateways::$currencies as $currency) {
            $serviceId = $this->scopeConfig->getValue('payment/bluepayment/' . strtolower($currency) . '/service_id');

            if ($serviceId) {
                $this->moduleDataSetup->getConnection()
                    ->update(
                        $this->moduleDataSetup->getTable('blue_gateways'),
                        ['gateway_service_id' => $serviceId],
                        [
                            'gateway_service_id = 0',
                            'gateway_currency = ?' => $currency
                        ]
                    );
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
