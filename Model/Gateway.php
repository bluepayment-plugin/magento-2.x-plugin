<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\GatewayInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway as GatewayResource;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Gateway extends AbstractModel implements IdentityInterface, GatewayInterface
{
    public const CACHE_TAG = 'blue_gateway';

    protected $_eventPrefix = 'blue_gateway';
    protected $_cacheTag = 'blue_gateway';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(GatewayResource::class);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getStatus()
            && ! $this->isForceDisabled();
    }

    /**
     * @return bool
     */
    public function isSeparatedMethod()
    {
        if (in_array($this->getGatewayId(), ConfigProvider::ALWAYS_SEPARATED)) {
            return true;
        }

        return $this->getIsSeparatedMethod();
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @param int $store
     * @return GatewayInterface
     */
    public function setStoreId($store)
    {
        return $this->setData(self::STORE_ID, $store);
    }

    /**
     * @return int
     */
    public function getServiceId()
    {
        return $this->getData(self::SERVICE_ID);
    }

    /**
     * @param int $serviceId
     * @return GatewayInterface
     */
    public function setServiceId($serviceId)
    {
        return $this->setData(self::SERVICE_ID, $serviceId);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * @param string $currency
     * @return GatewayInterface
     */
    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return (bool) $this->getData(self::STATUS);
    }

    /**
     * @param bool $status
     * @return GatewayInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return int
     */
    public function getGatewayId()
    {
        return $this->getData(self::GATEWAY_ID);
    }

    /**
     * @param int $gatewayId
     * @return GatewayInterface
     */
    public function setGatewayId($gatewayId)
    {
        return $this->setData(self::GATEWAY_ID, $gatewayId);
    }

    /**
     * @return string
     */
    public function getBankName()
    {
        return $this->getData(self::BANK_NAME);
    }

    public function setBankName($bankName)
    {
        return $this->setData(self::BANK_NAME, $bankName);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @param string $description
     * @return GatewayInterface
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->getData(self::LOGO_URL);
    }

    public function setLogoUrl($logoUrl)
    {
        return $this->setData(self::LOGO_URL, $logoUrl);
    }

    /**
     * @return bool
     */
    public function getUseOwnLogo()
    {
        return (bool) $this->getData(self::USE_OWN_LOGO);
    }

    /**
     * @param bool $useOwnLogo
     * @return GatewayInterface
     */
    public function setUseOwnLogo($useOwnLogo)
    {
        return $this->setData(self::USE_OWN_LOGO, $useOwnLogo);
    }

    /**
     * @return string
     */
    public function getLogoPath()
    {
        return $this->getData(self::LOGO_PATH);
    }

    /**
     * @param string $logoPath
     * @return GatewayInterface
     */
    public function setLogoPath($logoPath)
    {
        return $this->setData(self::LOGO_PATH, $logoPath);
    }

    /**
     * @return string
     */
    public function getStatusDate()
    {
        return $this->getData(self::STATUS_DATE);
    }

    /**
     * @param string $statusDate
     * @return GatewayInterface
     */
    public function setStatusDate($statusDate)
    {
        return $this->setData(self::STATUS_DATE, $statusDate);
    }

    /**
     * @return bool
     */
    public function getIsSeparatedMethod()
    {
        return (bool) $this->getData(self::IS_SEPARATED_METHOD);
    }

    /**
     * @param bool $isSeparatedMethod
     * @return GatewayInterface
     */
    public function setIsSeparatedMethod($isSeparatedMethod)
    {
        return $this->setData(self::IS_SEPARATED_METHOD, $isSeparatedMethod);
    }

    /**
     * @return bool
     */
    public function isForceDisabled()
    {
        return (bool) $this->getData(self::IS_FORCE_DISABLED);
    }

    /**
     * @param bool $forceDisable
     * @return GatewayInterface
     */
    public function setForceDisable($forceDisable)
    {
        return $this->setData(self::IS_FORCE_DISABLED, $forceDisable);
    }

    /**
     * @inheritDoc
     */
    public function getMinAmount()
    {
        return $this->getData(self::MIN_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setMinAmount($minAmount)
    {
        return $this->setData(self::MIN_AMOUNT, $minAmount);
    }

    /**
     * @inheritDoc
     */
    public function getMaxAmount()
    {
        return $this->getData(self::MAX_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setMaxAmount($maxAmount)
    {
        return $this->setData(self::MAX_AMOUNT, $maxAmount);
    }

    /**
     * @inheritDoc
     */
    public function getMinValidityTime()
    {
        return $this->getData(self::MIN_VALIDITY_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setMinValidityTime($minValidityTime)
    {
        return $this->setData(self::MIN_VALIDITY_TIME, $minValidityTime);
    }
}
