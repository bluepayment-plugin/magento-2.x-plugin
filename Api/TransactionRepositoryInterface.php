<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface TransactionRepositoryInterface
 * @package BlueMedia\BluePayment\Api
 */
interface TransactionRepositoryInterface
{
    /**
     * @param \BlueMedia\BluePayment\Api\Data\TransactionInterface $page
     *
     * @return mixed
     */
    public function save(TransactionInterface $page);

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
     * @return \BlueMedia\BluePayment\Model\ResourceModel\Transaction\Collection
     */
    public function getListForOrder(OrderInterface $order);

    /**
     * @param \BlueMedia\BluePayment\Api\Data\TransactionInterface $page
     *
     * @return mixed
     */
    public function delete(TransactionInterface $page);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return bool
     */
    public function orderHasSuccessTransaction(OrderInterface $order);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return \BlueMedia\BluePayment\Api\Data\TransactionInterface|null
     */
    public function getSuccessTransactionFromOrder(OrderInterface $order);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteById($id);
}
