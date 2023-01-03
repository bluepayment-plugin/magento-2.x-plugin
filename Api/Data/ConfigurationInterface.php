<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface GatewayInterface
 */
interface ConfigurationInterface
{
    public const QUOTE_LIFETIME = 'quote_lifetime';
    public const PLATFORM_VERSION = 'platform_version';
    public const PLATFORM_EDITION = 'platform_edition';
    public const MODULE_VERSION = 'module_version';

    /**
     * @return int
     */
    public function getQuoteLifetime(): int;

    /**
     * @param int $quoteLifetime Lifetime of quote in days.
     * @return ConfigurationInterface
     */
    public function setQuoteLifetime(int $quoteLifetime): ConfigurationInterface;

    /**
     * @return string
     */
    public function getPlatformVersion(): string;

    /**
     * @param string $platformVersion
     * @return ConfigurationInterface
     */
    public function setPlatformVersion(string $platformVersion): ConfigurationInterface;

    /**
     * @return string
     */
    public function getPlatformEdition(): string;

    /**
     * @param string $platformEdition
     * @return ConfigurationInterface
     */
    public function setPlatformEdition(string $platformEdition): ConfigurationInterface;

    /**
     * @return string
     */
    public function getModuleVersion(): string;

    /**
     * @param string $moduleVersion
     * @return ConfigurationInterface
     */
    public function setModuleVersion(string $moduleVersion): ConfigurationInterface;
}
