<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface RefundTransactionRepositoryInterface
 */
interface RefundTransactionRepositoryInterface
{
    /**
     * @param RefundTransactionInterface $refundTransaction
     *
     * @return mixed
     */
    public function save(RefundTransactionInterface $refundTransaction);

    /**
     * @param  int  $id
     *
     * @return mixed
     */
    public function getById(int $id);

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
    public function getListForOrder(OrderInterface $order): Collection;

    /**
     * @param RefundTransactionInterface $refundTransaction
     *
     * @return mixed
     */
    public function delete(RefundTransactionInterface $refundTransaction);

    /**
     * @param  int  $id
     *
     * @return mixed
     */
    public function deleteById(int $id);

    /**
     * @param TransactionInterface $transaction
     *
     * @return float
     */
    public function getTotalRefundAmountOnTransaction(TransactionInterface $transaction): float;


    /**
     * Get all pending refund transactions
     *
     * @return SearchResultsInterface
     * @throws NoSuchEntityException
     */
    public function getPendingRefundTransactions(): SearchResultsInterface;
}
