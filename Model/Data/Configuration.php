<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Data;

use BlueMedia\BluePayment\Api\Data\ConfigurationInterface;
use Magento\Framework\DataObject;

class Configuration extends DataObject implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getQuoteLifetime(): int
    {
        return (int) $this->getData(self::QUOTE_LIFETIME);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteLifetime(int $quoteLifetime): ConfigurationInterface
    {
        return $this->setData(self::QUOTE_LIFETIME, $quoteLifetime);
    }
}
