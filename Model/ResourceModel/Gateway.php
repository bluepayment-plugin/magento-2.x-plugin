<?php

namespace BlueMedia\BluePayment\Model\ResourceModel;

use BlueMedia\BluePayment\Model\Gateway as GatewayModel;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Gateway extends AbstractDb
{
    /**
     * Date model
     *
     * @var DateTime
     */
    protected $dateTime;

    /**
     * constructor
     *
     * @param  DateTime  $date
     * @param  Context  $context
     */
    public function __construct(
        DateTime $date,
        Context  $context
    ) {
        $this->dateTime = $date;
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
     * @param  AbstractModel|GatewayModel  $object
     *
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $object->setUpdatedAt($this->dateTime->date());
        if ($object->isObjectNew()) {
            $object->setCreatedAt($this->dateTime->date());
        }

        return parent::_beforeSave($object);
    }

}
