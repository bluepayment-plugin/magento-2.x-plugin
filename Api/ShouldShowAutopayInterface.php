<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api;

/**
 * Interface ShouldShowAutopayInterface
 */
interface ShouldShowAutopayInterface
{
    /**
     * Check if Autopay should be shown in catalog/cart page.
     *
     * @return boolean
     */
    public function execute(): bool;
}
