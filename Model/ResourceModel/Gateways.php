<?php

namespace BlueMedia\BluePayment\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Gateways extends AbstractDb
{
    /**
     * Date model
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * constructor
     *
     * @param \Magento\Framework\Stdlib\DateTime\DateTime       $date
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        DateTime $date,
        Context  $context
    ) {
        $this->_date = $date;
        parent::__construct($context);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('blue_gateways', 'entity_id');
    }

    /**
     * before save callback
     *
     * @param \Magento\Framework\Model\AbstractModel|\BlueMedia\BluePayment\Model\Gateways $object
     *
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $object->setUpdatedAt($this->_date->date());
        if ($object->isObjectNew()) {
            $object->setCreatedAt($this->_date->date());
        }

        return parent::_beforeSave($object);
    }

    /**
     * Retrieves Gateways Name from DB by passed entity id.
     *
     * @param string $id
     *
     * @return string|bool
     */
    public function getGatewayNameById($id)
    {
        $adapter = $this->getConnection();

        if ($adapter) {
            $select = $adapter->select()
                ->from($this->getMainTable(), 'gateway_name')
                ->where('entity_id = :entity_id');
            $binds = ['entity_id' => (int)$id];

            return $adapter->fetchOne($select, $binds);
        }

        return false;
    }
}
