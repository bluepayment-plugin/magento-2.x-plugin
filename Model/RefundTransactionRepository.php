<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Api\RefundTransactionRepositoryInterface;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction\CollectionFactory;
use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

class RefundTransactionRepository implements RefundTransactionRepositoryInterface
{
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
     * TransactionRepository constructor.
     *
     * @param RefundTransactionFactory $transactionFactory
     * @param CollectionFactory $transactionCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        RefundTransactionFactory $transactionFactory,
        CollectionFactory $transactionCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->transactionFactory           = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->searchResultsFactory         = $searchResultsFactory;
    }

    /**
     * @param \BlueMedia\BluePayment\Api\Data\RefundTransactionInterface $object
     *
     * @return \BlueMedia\BluePayment\Api\Data\RefundTransactionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(RefundTransactionInterface $object)
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
     * @return Collection
     */
    public function getListForOrder(OrderInterface $order)
    {
        /** @var ResourceModel\RefundTransaction\Collection $collection */
        $collection = $this->transactionCollectionFactory->create();

        $collection->addFieldToFilter(RefundTransactionInterface::ORDER_ID, $order->getIncrementId());

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
     * @param \BlueMedia\BluePayment\Api\Data\RefundTransactionInterface $object
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(RefundTransactionInterface $object)
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
        /** @var \BlueMedia\BluePayment\Model\RefundTransaction $object */
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
    public function getTotalRefundAmountOnTransaction(TransactionInterface $transaction)
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
}
