<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface ShouldShowAutopayInterface
 */
interface ShouldShowAutopayInterface
{
    /**
     * Check if AutoPay should be shown in catalog/cart page.
     *
     * @return boolean
     */
    public function execute(): bool;
}
