<?php

namespace BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction;

use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction as RefundTransactionResource;
use BlueMedia\BluePayment\Model\RefundTransaction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction
 */
class Collection extends AbstractCollection
{

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            RefundTransaction::class,
            RefundTransactionResource::class
        );
    }
}
