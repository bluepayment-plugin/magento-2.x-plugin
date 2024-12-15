<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\ResourceModel;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
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
        $this->_init('blue_refund', RefundTransactionInterface::ID);
    }
}
