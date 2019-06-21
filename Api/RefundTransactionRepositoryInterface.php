<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface RefundTransactionRepositoryInterface
 * @package BlueMedia\BluePayment\Api
 */
interface RefundTransactionRepositoryInterface
{
    /**
     * @param \BlueMedia\BluePayment\Api\Data\RefundTransactionInterface $page
     *
     * @return mixed
     */
    public function save(RefundTransactionInterface $page);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getById($id);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return \BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\Collection
     */
    public function getListForOrder(OrderInterface $order);

    /**
     * @param \BlueMedia\BluePayment\Api\Data\RefundTransactionInterface $page
     *
     * @return mixed
     */
    public function delete(RefundTransactionInterface $page);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteById($id);

    /**
     * @param $transaction
     *
     * @return float
     */
    public function getTotalRefundAmountOnTransaction(TransactionInterface $transaction);
}
