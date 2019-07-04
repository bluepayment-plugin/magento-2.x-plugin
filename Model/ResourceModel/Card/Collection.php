<?php

namespace BlueMedia\BluePayment\Model\ResourceModel\Card;

use BlueMedia\BluePayment\Model\Card as Card;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'card_id';
    protected $_eventPrefix = 'bluemedia_bluepayment_card_collection';
    protected $_eventObject = 'card_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Card::class,
            CardResource::class
        );
    }
}
