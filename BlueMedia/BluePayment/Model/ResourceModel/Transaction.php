<?php

namespace BlueMedia\BluePayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Transaction
 * @package BlueMedia\BluePayment\Model\ResourceModel
 */
class Transaction extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('blue_transaction', 'transaction_id');
    }
}
