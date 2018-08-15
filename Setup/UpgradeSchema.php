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
 *
 * @package BlueMedia\BluePayment\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Function that upgrades module
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
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

        if (version_compare($context->getVersion(), '2.2.2') < 0) {
            $this->addForceDisabledToBlueMediaGatewaysTable($setup);
        }

        if (version_compare($context->getVersion(), '2.3.0') < 0) {
            $this->addTransactionAndRefundTables($setup);
        }
    }

    /**
     * creates table blue_gateways in database
     *
     * @param SchemaSetupInterface $setup
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
                    'primary'  => true,
                ], 'Entity Id')
                ->addColumn('gateway_status', Table::TYPE_INTEGER, null, ['nullable' => false], 'Gateway Status')
                ->addColumn('gateway_id', Table::TYPE_INTEGER, null, ['nullable' => false], 'Gateway ID')
                ->addColumn('bank_name', Table::TYPE_TEXT, 100, ['nullable' => false], 'Bank Name')
                ->addColumn('gateway_name', Table::TYPE_TEXT, 100, ['nullable' => false], 'Gateway name')
                ->addColumn('gateway_description', Table::TYPE_TEXT, 1000, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Description')
                ->addColumn('gateway_sort_order', Table::TYPE_INTEGER, null, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Sort Order')
                ->addColumn('gateway_type', Table::TYPE_TEXT, 50, ['nullable' => false], 'Gateway Type')
                ->addColumn('gateway_logo_url', Table::TYPE_TEXT, 500, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Logo URL')
                ->addColumn('use_own_logo', Table::TYPE_INTEGER, null, ['nullable' => false], 'Use Own Logo')
                ->addColumn('gateway_logo_path', Table::TYPE_TEXT, 500, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Logo Path')
                ->addColumn('status_date', Table::TYPE_TIMESTAMP, null, [
                    'nullable' => true,
                    'default'  => 'CURRENT_TIMESTAMP',
                ], 'Status Date')
                ->setComment('BlueMedia BluePayment Gateways Table')
                ->setOption('type', 'INNODB')
                ->setOption('charset', 'utf8')
                ->setOption('collate', 'utf8_general_ci');
            $installer->getConnection()->createTable($table);
        }
        if (!$installer->tableExists('blue_gateways')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('blue_gateways'))
                ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true,
                ], 'Entity Id')
                ->addColumn('gateway_status', Table::TYPE_INTEGER, null, ['nullable' => false], 'Gateway Status')
                ->addColumn('gateway_id', Table::TYPE_INTEGER, null, ['nullable' => false], 'Gateway ID')
                ->addColumn('bank_name', Table::TYPE_TEXT, 100, ['nullable' => false], 'Bank Name')
                ->addColumn('gateway_name', Table::TYPE_TEXT, 100, ['nullable' => false], 'Gateway name')
                ->addColumn('gateway_description', Table::TYPE_TEXT, 1000, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Description')
                ->addColumn('gateway_sort_order', Table::TYPE_INTEGER, null, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Sort Order')
                ->addColumn('gateway_type', Table::TYPE_TEXT, 50, ['nullable' => false], 'Gateway Type')
                ->addColumn('gateway_logo_url', Table::TYPE_TEXT, 500, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Logo URL')
                ->addColumn('use_own_logo', Table::TYPE_INTEGER, null, ['nullable' => false], 'Use Own Logo')
                ->addColumn('gateway_logo_path', Table::TYPE_TEXT, 500, [
                    'nullable' => true,
                    'default'  => null,
                ], 'Gateway Logo Path')
                ->addColumn('status_date', Table::TYPE_TIMESTAMP, null, [
                    'nullable' => true,
                    'default'  => 'CURRENT_TIMESTAMP',
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
     * @param \Magento\Framework\Setup\SchemaSetupInterface $installer
     */
    private function addCardFlagToBlueMediaTable(SchemaSetupInterface $installer)
    {
        $installer->startSetup();
        if ($installer->tableExists('blue_gateways')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('blue_gateways'),
                'is_separated_method',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'default'  => 0,
                    'comment'  => 'Use gateway as separated method.',
                ]
            );
        }
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     */
    private function addForceDisabledToBlueMediaGatewaysTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $installer->startSetup();
        if ($installer->tableExists('blue_gateways')) {
            $installer->getConnection()
                ->addColumn(
                    $installer->getTable(
                        'blue_gateways'
                    ),
                    'force_disable',
                    [
                        'type'     => Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'default'  => 0,
                        'comment'  => 'Force Disable Gateway',
                    ]
                );
        }
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     */
    private function addTransactionAndRefundTables(SchemaSetupInterface $setup)
    {
        $setup->startSetup();

        $this->createTransactionTable($setup);
        $this->createRefundTable($setup);

        $setup->endSetup();
    }

    /**
     * @param $installer
     */
    private function createTransactionTable(SchemaSetupInterface $installer)
    {
        $table = $installer->getConnection()->newTable(
            $installer->getTable('blue_transaction')
        )->addColumn(
            'transaction_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
            'Entity ID'
        )->addColumn(
            'order_id',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false,],
            'Order increment ID'
        )->addColumn(
            'remote_id',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false,],
            'Remote transaction ID'
        )->addColumn(
            'amount',
            Table::TYPE_DECIMAL,
            [12,4],
            ['nullable' => false, 'unsigned' => true, 'default' => '0.0000'],
            'Transaction amount'
        )->addColumn(
            'currency',
            Table::TYPE_TEXT,
            10,
            [],
            'Transaction currency'
        )->addColumn(
            'gateway_id',
            Table::TYPE_SMALLINT,
            null,
            [],
            'Payment gateway ID'
        )->addColumn(
            'payment_date',
            Table::TYPE_TIMESTAMP,
            null,
            [],
            'Payment date'
        )->addColumn(
            'payment_status',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false,],
            'Remote transaction status'
        )->addColumn(
            'payment_status_details',
            Table::TYPE_TEXT,
            50,
            [],
            'Remote transaction status details'
        )->addColumn(
            'creation_time',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT,],
            'Creation Time'
        );
        $installer->getConnection()->createTable($table);
    }

    /**
     * @param $installer
     */
    private function createRefundTable(SchemaSetupInterface $installer)
    {
        $table = $installer->getConnection()->newTable(
            $installer->getTable('blue_refund')
        )->addColumn(
            'refund_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
            'Entity ID'
        )->addColumn(
            'order_id',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false,],
            'Order increment ID'
        )->addColumn(
            'remote_id',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false,],
            'Remote transaction ID'
        )->addColumn(
            'remote_out_id',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false,],
            'Remote refund ID'
        )->addColumn(
            'amount',
            Table::TYPE_DECIMAL,
            [12,4],
            ['nullable' => false, 'unsigned' => true, 'default' => '0.0000'],
            'Refund amount'
        )->addColumn(
            'currency',
            Table::TYPE_TEXT,
            10,
            [],
            'Transaction currency'
        )->addColumn(
            'is_partial',
            Table::TYPE_BOOLEAN,
            null,
            [],
            'Is partial refund'
        )->addColumn(
            'creation_time',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT,],
            'Creation Time'
        )->addColumn(
            'update_time',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT,],
            'Creation Time'
        );
        $installer->getConnection()->createTable($table);
    }
}
