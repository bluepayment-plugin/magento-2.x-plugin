<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Client;
use BlueMedia\BluePayment\Api\Data\TransactionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Transaction as TransactionResource;
use DateTime;
use DateTimeZone;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Transaction extends AbstractModel implements TransactionInterface, IdentityInterface
{
    const CACHE_TAG = 'blue_transaction';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface    $timezone
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TimezoneInterface $timezone,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection);
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(TransactionResource::class);
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
        return $this->_getData(TransactionInterface::ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderId($orderId)
    {
        return $this->setData(TransactionInterface::ORDER_ID, $orderId);
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoteId()
    {
        return $this->_getData(TransactionInterface::REMOTE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setRemoteId($remoteId)
    {
        return $this->setData(TransactionInterface::REMOTE_ID, $remoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return $this->_getData(TransactionInterface::AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setAmount($amount)
    {
        return $this->setData(TransactionInterface::AMOUNT, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return $this->_getData(TransactionInterface::CURRENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        return $this->setData(TransactionInterface::CURRENCY, $currency);
    }

    /**
     * {@inheritdoc}
     */
    public function getGatewayId()
    {
        return $this->_getData(TransactionInterface::GATEWAY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setGatewayId($gatewayId)
    {
        return $this->setData(TransactionInterface::GATEWAY_ID, $gatewayId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentDate()
    {
        return $this->_getData(TransactionInterface::PAYMENT_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentDate($date)
    {
        $dateTime = $this->timezone->date(new DateTime($date, new DateTimeZone(Client::RESPONSE_TIMEZONE)));

        return $this->setData(TransactionInterface::PAYMENT_DATE, $dateTime->getTimestamp());
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatus()
    {
        return $this->_getData(TransactionInterface::PAYMENT_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentStatus($status)
    {
        return $this->setData(TransactionInterface::PAYMENT_STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatusDetails()
    {
        return $this->_getData(TransactionInterface::PAYMENT_STATUS_DETAILS);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentStatusDetails($status)
    {
        return $this->setData(TransactionInterface::PAYMENT_STATUS_DETAILS, $status);
    }
}
