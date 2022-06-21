<?php

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface GatewayInterface
 */
interface ConfigurationInterface
{
    public const QUOTE_LIFETIME = 'quote_lifetime';

    /**
     * @return int
     */
    public function getQuoteLifetime();

    /**
     * @param int $quoteLifetime Lifetime of quote in days.
     * @return ConfigurationInterface
     */
    public function setQuoteLifetime($quoteLifetime);
}
