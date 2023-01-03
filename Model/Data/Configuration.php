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

    /**
     * @inheritDoc
     */
    public function getPlatformVersion(): string
    {
        return (string) $this->getData(self::PLATFORM_VERSION);
    }

    /**
     * @inheritDoc
     */
    public function setPlatformVersion(string $platformVersion): ConfigurationInterface
    {
        return $this->setData(self::PLATFORM_VERSION, $platformVersion);
    }

    /**
     * @inheritDoc
     */
    public function getPlatformEdition(): string
    {
        return (string) $this->getData(self::PLATFORM_EDITION);
    }

    /**
     * @inheritDoc
     */
    public function setPlatformEdition(string $platformEdition): ConfigurationInterface
    {
        return $this->setData(self::PLATFORM_EDITION, $platformEdition);
    }

    /**
     * @inheritDoc
     */
    public function getModuleVersion(): string
    {
        return (string) $this->getData(self::MODULE_VERSION);
    }

    /**
     * @inheritDoc
     */
    public function setModuleVersion(string $moduleVersion): ConfigurationInterface
    {
        return $this->setData(self::MODULE_VERSION, $moduleVersion);
    }
}
