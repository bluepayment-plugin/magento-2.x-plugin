<?php

namespace BlueMedia\BluePayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Card extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('blue_card', 'card_id');
    }
}
