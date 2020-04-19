<?php

namespace BlueMedia\BluePayment\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * User saved card model
 *
 * @method int getCardId()
 * @method int getCustomerId()
 * @method int getCardIndex()
 * @method string getValidityYear()
 * @method string getValidityMonth()
 * @method string getIssuer()
 * @method string getMask()
 * @method string getClientHash()
 */
class Card extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'blue_card';

    protected $_cacheTag = 'blue_card';
    protected $_eventPrefix = 'blue_card';

    protected function _construct()
    {
        $this->_init(ResourceModel\Card::class);
    }

    /**
     * @return string[]
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
        $values = [];

        return $values;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return '**** **** **** '.$this->getMask();
    }
}
