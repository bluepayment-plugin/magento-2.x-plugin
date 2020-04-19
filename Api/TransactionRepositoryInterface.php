<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Transaction\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface TransactionRepositoryInterface
 */
interface TransactionRepositoryInterface
{
    /**
     * @param TransactionInterface $page
     *
     * @return mixed
     */
    public function save(TransactionInterface $page);

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
     * @param TransactionInterface $page
     *
     * @return mixed
     */
    public function delete(TransactionInterface $page);

    /**
     * @param OrderInterface $order
     *
     * @return bool
     */
    public function orderHasSuccessTransaction(OrderInterface $order);

    /**
     * @param OrderInterface $order
     *
     * @return TransactionInterface|null
     */
    public function getSuccessTransactionFromOrder(OrderInterface $order);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function deleteById($id);
}
