<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\GatewaysInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways as GatewaysResource;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * @method int getGatewayStatus()
 * @method string getGatewayCurrency()
 * @method int getForceDisable()
 * @method int getGatewayId()
 * @method string getGatewayName()
 * @method string getBankName()
 * @method string getGatewayDescription()
 * @method int getGatewaySortOrder()
 * @method string getGatewayType()
 * @method string getGatewayLogoUrl()
 * @method int getUseOwnLogo()
 * @method string getGatewayLogoPath()
 */
class Gateways extends AbstractModel implements IdentityInterface, GatewaysInterface
{
    const FORCE_DISABLE   = 1;
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    const CACHE_TAG = 'blue_gateways';

    /**
     * @var string
     */
    protected $_cacheTag = 'blue_gateways';

    /**
     * @var string
     */
    protected $_eventPrefix = 'blue_gateways';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(GatewaysResource::class);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getGatewayStatus() == self::STATUS_ACTIVE && $this->getForceDisable() != self::FORCE_DISABLE;
    }

    /**
     * @return bool
     */
    public function getIsSeparatedMethod()
    {
        if ($this->getGatewayId() == ConfigProvider::AUTOPAY_GATEWAY_ID) {
            return true;
        } elseif ($this->getGatewayId() == ConfigProvider::GPAY_GATEWAY_ID) {
            return true;
        }

        return $this->getData('is_separated_method');
    }
}
