<?php

namespace BlueMedia\BluePayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Transaction
 */
class RefundTransaction extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('blue_refund', 'refund_id');
    }
}
