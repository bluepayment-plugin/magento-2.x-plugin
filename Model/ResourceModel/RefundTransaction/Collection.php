<?php

namespace BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction;

use BlueMedia\BluePayment\Model\RefundTransaction;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction as RefundTransactionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            RefundTransaction::class,
            RefundTransactionResource::class
        );
    }
}
