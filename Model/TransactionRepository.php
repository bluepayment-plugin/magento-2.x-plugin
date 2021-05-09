<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\TransactionRepositoryInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Transaction\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\Transaction\CollectionFactory;
use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @var \BlueMedia\BluePayment\Model\TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var \BlueMedia\BluePayment\Model\ResourceModel\Transaction\CollectionFactory
     */
    private $transactionCollectionFactory;

    /**
     * @var \Magento\Framework\Api\SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * TransactionRepository constructor.
     *
     * @param \BlueMedia\BluePayment\Model\TransactionFactory                          $transactionFactory
     * @param \BlueMedia\BluePayment\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
     * @param \Magento\Framework\Api\SearchResultsInterfaceFactory                     $searchResultsFactory
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        CollectionFactory $transactionCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->transactionFactory           = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->searchResultsFactory         = $searchResultsFactory;
    }

    /**
     * @param \BlueMedia\BluePayment\Api\Data\TransactionInterface $object
     *
     * @return \BlueMedia\BluePayment\Api\Data\TransactionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(TransactionInterface $object)
    {
        try {
            $object->save();
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return bool
     */
    public function orderHasSuccessTransaction(OrderInterface $order)
    {
        return !($this->getSuccessTransactionFromOrder($order) === null);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return \BlueMedia\BluePayment\Api\Data\TransactionInterface|null
     */
    public function getSuccessTransactionFromOrder(OrderInterface $order)
    {
        $transactionCollection = $this->getListForOrder($order);

        /** @var \BlueMedia\BluePayment\Api\Data\TransactionInterface $transaction */
        foreach ($transactionCollection as $transaction) {
            if ($transaction->getPaymentStatus() == TransactionInterface::STATUS_SUCCESS
                && in_array($transaction->getPaymentStatusDetails(), TransactionInterface::STATUS_DETAIL_SUCCESS)
            ) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return Collection
     */
    public function getListForOrder(OrderInterface $order)
    {
        /** @var ResourceModel\Transaction\Collection $collection */
        $collection = $this->transactionCollectionFactory->create();

        $collection->addFieldToFilter([
            TransactionInterface::ORDER_ID,
            TransactionInterface::ORDER_ID
        ], [
            ['eq' => $order->getIncrementId()],
            ['eq' => Payment::QUOTE_PREFIX . $order->getQuoteId()]
        ]);

        return $collection;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param \BlueMedia\BluePayment\Api\Data\TransactionInterface $object
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(TransactionInterface $object)
    {
        try {
            $object->delete();
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        /** @var \BlueMedia\BluePayment\Model\Transaction $object */
        $object = $this->transactionFactory->create();
        $object->load($id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Object with id "%1" does not exist.', $id));
        }

        return $object;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        /** @var ResourceModel\Transaction\Collection $collection */
        $collection = $this->transactionCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields     = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition    = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);

        return $searchResults;
    }
}
