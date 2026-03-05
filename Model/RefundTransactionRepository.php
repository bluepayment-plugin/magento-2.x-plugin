<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\CollectionFactory;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction as RefundTransactionResource;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

class RefundTransactionRepository implements RefundTransactionRepositoryInterface
{
    /**
     * @var RefundTransactionResource
     */
    protected $resource;

    /**
     * @var RefundTransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var CollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * TransactionRepository constructor.
     *
     * @param  RefundTransactionResource  $resource
     * @param  RefundTransactionFactory  $transactionFactory
     * @param  CollectionFactory  $transactionCollectionFactory
     * @param  SearchResultsInterfaceFactory  $searchResultsFactory
     * @param  SearchCriteriaBuilder  $searchCriteriaBuilder
     */
    public function __construct(
        RefundTransactionResource $resource,
        RefundTransactionFactory $transactionFactory,
        CollectionFactory $transactionCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource                     = $resource;
        $this->transactionFactory           = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->searchResultsFactory         = $searchResultsFactory;
        $this->searchCriteriaBuilder        = $searchCriteriaBuilder;
    }

    /**
     * @param  RefundTransactionInterface  $refundTransaction
     *
     * @return RefundTransactionInterface
     * @throws CouldNotSaveException
     */
    public function save(RefundTransactionInterface $refundTransaction)
    {
        try {
            $this->resource->save($refundTransaction);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $refundTransaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return Collection
     */
    public function getListForOrder(OrderInterface $order): Collection
    {
        /** @var ResourceModel\RefundTransaction\Collection $collection */
        $collection = $this->transactionCollectionFactory->create();

        $collection->addFieldToFilter(RefundTransactionInterface::ORDER_ID, $order->getIncrementId());

        return $collection;
    }

    /**
     * @param  int  $id
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param  RefundTransactionInterface  $object
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(RefundTransactionInterface $object)
    {
        try {
            $this->resource->delete($object);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param  int  $id
     *
     * @return RefundTransaction
     * @throws NoSuchEntityException
     */
    public function getById(int $id)
    {
        /** @var \BlueMedia\BluePayment\Model\RefundTransaction $object */
        $object = $this->transactionFactory->create();
        $object->load($id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Object with id "%1" does not exist.', $id));
        }

        return $object;
    }

    /**
     * @param  SearchCriteriaInterface  $criteria
     *
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        /** @var ResourceModel\RefundTransaction\Collection $collection */
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

    /**
     * @param TransactionInterface $transaction
     *
     * @return float
     */
    public function getTotalRefundAmountOnTransaction(TransactionInterface $transaction): float
    {
        $total = 0.00;

        /** @var ResourceModel\RefundTransaction\Collection $collection */
        $collection = $this->transactionCollectionFactory->create();
        $collection->addFieldToFilter(RefundTransactionInterface::REMOTE_ID, $transaction->getRemoteId());

        /** @var RefundTransactionInterface $refund */
        foreach ($collection as $refund) {
            $total += $refund->getAmount();
        }

        return (float) $total;
    }

    /**
     * Pobierz wszystkie transakcje zwrotów o statusie 'pending'
     *
     * @return SearchResultsInterface
     */
    public function getPendingRefundTransactions(): SearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(RefundTransactionInterface::REMOTE_OUT_ID, null, 'eq')
            ->addFilter(RefundTransactionInterface::MESSAGE_ID, null, 'neq')
            ->create();

        return $this->getList($searchCriteria);
    }

}
