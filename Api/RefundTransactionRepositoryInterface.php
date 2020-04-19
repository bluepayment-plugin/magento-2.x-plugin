<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface RefundTransactionRepositoryInterface
 */
interface RefundTransactionRepositoryInterface
{
    /**
     * @param RefundTransactionInterface $page
     *
     * @return mixed
     */
    public function save(RefundTransactionInterface $page);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getById($id);

    /**
     * @param SearchCriteriaInterface $criteria
     *
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param OrderInterface $order
     *
     * @return Collection
     */
    public function getListForOrder(OrderInterface $order);

    /**
     * @param RefundTransactionInterface $page
     *
     * @return mixed
     */
    public function delete(RefundTransactionInterface $page);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function deleteById($id);

    /**
     * @param TransactionInterface $transaction
     *
     * @return float
     */
    public function getTotalRefundAmountOnTransaction(TransactionInterface $transaction);
}
