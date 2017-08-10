<?php
namespace BlueMedia\BluePayment\Model\ResourceModel\Gateways;

use BlueMedia\BluePayment\Model\Gateways;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways as GatewaysResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package BlueMedia\BluePayment\Model\ResourceModel\Gateways
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'bluemedia_bluepayment_gateways_collection';
    protected $_eventObject = 'gateways_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Gateways::class, GatewaysResource::class);
    }

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Zend_Db_Select::GROUP);

        return $countSelect;
    }

    /**
     * @param string $valueField
     * @param string $labelField
     * @param array  $additional
     *
     * @return array
     */
    protected function _toOptionArray($valueField = 'entity_id', $labelField = 'gateway_name', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }
}