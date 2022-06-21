<?php

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\ConfigurationInterface;
use Magento\Framework\DataObject;

class Configuration extends DataObject implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getQuoteLifetime()
    {
        return (int) $this->getData(self::QUOTE_LIFETIME);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteLifetime($quoteLifetime)
    {
        return $this->setData(self::QUOTE_LIFETIME, $quoteLifetime);
    }
}
