<?php

namespace BlueMedia\BluePayment\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Card
 *
 * @method int getCardId()
 * @method int getCustomerId()
 * @method int getCardIndex()
 * @method string getValidityYear()
 * @method string getValidityMonth()
 * @method string getIssuer()
 * @method string getMask()
 * @method string getClientHash()
 *
 * @package BlueMedia\BluePayment\Model
 */
class Card extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'blue_card';

    protected $_cacheTag = 'blue_card';
    protected $_eventPrefix = 'blue_card';

    protected function _construct()
    {
        $this->_init('BlueMedia\BluePayment\Model\ResourceModel\Card');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    public function getNumber()
    {
        return '**** **** **** '.$this->getMask();
    }
}
