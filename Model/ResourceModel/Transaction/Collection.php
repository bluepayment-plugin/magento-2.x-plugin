<?php

namespace BlueMedia\BluePayment\Model\ResourceModel\Transaction;

use BlueMedia\BluePayment\Model\ResourceModel\Transaction as TransactionResource;
use BlueMedia\BluePayment\Model\Transaction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package BlueMedia\BluePayment\Model\ResourceModel\Transaction
 */
class Collection extends AbstractCollection
{

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            Transaction::class,
            TransactionResource::class
        );
    }
}
