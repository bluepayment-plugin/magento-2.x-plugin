<?php
/**
 * @author    Bold Piotr Kozioł
 * @copyright Copyright (c) 2016 Bold Bran Commerce
 * @package   BlueMedia_BluePayment
 */

namespace BlueMedia\BluePayment\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package BlueMedia\BluePayment\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * Function that upgrades module
     *
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * For module upgrade purpose table is created in database
         * as the previous versions did not  use it
         */
        if (version_compare($context->getVersion(), '2.1.0') < 0) {
            $this->installBlueMediaTable($setup);
        }

        if (version_compare($context->getVersion(), '2.1.1') < 0) {
            $this->addCardFlagToBlueMediaTable($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     *
     * creates table blue_gateways in database
     *
     */
    private function installBlueMediaTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists('blue_gateways')) {
            $table = $installer->getConnection()
               ->newTable($installer->getTable('blue_gateways'))
               ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                       'identity' => true,
                       'unsigned' => true,
                       'nullable' => false,
                       'primary' => true
                   ], 'Entity Id')
               ->addColumn('gateway_status', Table::TYPE_INTEGER, null, ['nullable' => false], 'Gateway Status')
               ->addColumn('gateway_id', Table::TYPE_INTEGER, null, ['nullable' => false], 'Gateway ID')
               ->addColumn('bank_name', Table::TYPE_TEXT, 100, ['nullable' => false], 'Bank Name')
               ->addColumn('gateway_name', Table::TYPE_TEXT, 100, ['nullable' => false], 'Gateway name')
               ->addColumn('gateway_description', Table::TYPE_TEXT, 1000, [
                       'nullable' => true,
                       'default' => null
                   ], 'Gateway Description')
               ->addColumn('gateway_sort_order', Table::TYPE_INTEGER, null, [
                       'nullable' => true,
                       'default' => null
                   ], 'Gateway Sort Order')
               ->addColumn('gateway_type', Table::TYPE_TEXT, 50, ['nullable' => false], 'Gateway Type')
               ->addColumn('gateway_logo_url', Table::TYPE_TEXT, 500, [
                       'nullable' => true,
                       'default' => null
                   ], 'Gateway Logo URL')
               ->addColumn('use_own_logo', Table::TYPE_INTEGER, null, ['nullable' => false], 'Use Own Logo')
               ->addColumn('gateway_logo_path', Table::TYPE_TEXT, 500, [
                       'nullable' => true,
                       'default' => null
                   ], 'Gateway Logo Path')
               ->addColumn('status_date', Table::TYPE_TIMESTAMP, null, [
                       'nullable' => true,
                       'default' => 'CURRENT_TIMESTAMP'
                   ], 'Status Date')
               ->setComment('BlueMedia BluePayment Gateways Table')
               ->setOption('type', 'INNODB')
               ->setOption('charset', 'utf8')
               ->setOption('collate', 'utf8_general_ci');
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     */
    private function addCardFlagToBlueMediaTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $installer->startSetup();
        if ($installer->tableExists('blue_gateways')) {
            $installer->getConnection()->addColumn($installer->getTable('blue_gateways'), 'is_separated_method', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => true,
                'default' => 0,
                'comment' => 'Use gateway as separated method.'
            ]);
        }
    }
}
