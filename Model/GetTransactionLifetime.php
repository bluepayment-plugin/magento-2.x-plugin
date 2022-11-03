<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use DateTime;
use Exception;
use Magento\Sales\Api\Data\OrderInterface;

class GetTransactionLifetime
{
    /** @var ConfigProvider  */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * Returns transaction expiration date for order in hours or false if it's expired and true if it's unlimited.
     *
     * @param OrderInterface $order
     * @return bool|DateTime
     * @throws Exception
     */
    public function getForOrder(OrderInterface $order)
    {
        $orderCreatedAt = $order->getCreatedAt();

        if (! $orderCreatedAt) {
            return true;
        }

        return $this->getForDateTime(
            new DateTime($orderCreatedAt)
        );
    }

    /**
     * Returns transaction expiration date or false if it's expired and true if it's unlimited.
     *
     * @param DateTime $dateTime
     * @return bool|DateTime
     */
    public function getForDateTime(DateTime $dateTime)
    {
        $lifeTime = (int) $this->configProvider->getTransactionLifetime();

        // Set unlimited if lifetime is not set
        if ($lifeTime === 0) {
            return true;
        }

        $dateTime->modify('+' . $lifeTime . ' hours');
        $now = new DateTime();

        if ($dateTime < $now) {
            if ($this->configProvider->disableContinuationLinkAfterExpiration()) {
                return false;
            }

            return true;
        }

        return $dateTime;
    }
}
