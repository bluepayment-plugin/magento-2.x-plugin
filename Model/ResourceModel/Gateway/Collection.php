<?php

namespace BlueMedia\BluePayment\Model\ResourceModel\Gateway;

use BlueMedia\BluePayment\Model\Gateway;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway as GatewayResource;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zend_Db_Select;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'bluemedia_bluepayment_gateway_collection';
    protected $_eventObject = 'gateway_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Gateway::class, GatewayResource::class);
    }

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);

        return $countSelect;
    }
}
