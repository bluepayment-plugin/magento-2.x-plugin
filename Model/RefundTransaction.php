<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\RefundTransactionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\RefundTransaction as RefundTransactionResource;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Transaction
 */
class RefundTransaction extends AbstractModel implements RefundTransactionInterface, IdentityInterface
{
    const CACHE_TAG = 'blue_refund';

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(RefundTransactionResource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderId()
    {
        return $this->_getData(RefundTransactionInterface::ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderId($orderId)
    {
        return $this->setData(RefundTransactionInterface::ORDER_ID, $orderId);
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoteId()
    {
        return $this->_getData(RefundTransactionInterface::REMOTE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setRemoteId($remoteId)
    {
        return $this->setData(RefundTransactionInterface::REMOTE_ID, $remoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return $this->_getData(RefundTransactionInterface::AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setAmount($amount)
    {
        return $this->setData(RefundTransactionInterface::AMOUNT, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return $this->_getData(RefundTransactionInterface::CURRENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        return $this->setData(RefundTransactionInterface::CURRENCY, $currency);
    }

    /**
     * @return string
     */
    public function getRemoteOutId()
    {
        return $this->_getData(RefundTransactionInterface::REMOTE_OUT_ID);
    }

    /**
     * @param string $remoteId
     *
     * @return $this
     */
    public function setRemoteOutId($remoteId)
    {
        return $this->setData(RefundTransactionInterface::REMOTE_OUT_ID, $remoteId);
    }

    /**
     * @return bool
     */
    public function isPartial()
    {
        return (bool) $this->_getData(RefundTransactionInterface::IS_PARTIAL);
    }

    /**
     * @param bool $isPartial
     *
     * @return $this
     */
    public function setIsPartial($isPartial)
    {
        return $this->setData(RefundTransactionInterface::IS_PARTIAL, $isPartial);
    }
}
