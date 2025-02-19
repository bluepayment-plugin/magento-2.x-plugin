<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Api\Data\GatewayInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface GatewayRepositoryInterface
{
    /**
     * Save a gateway.
     *
     * @param GatewayInterface $gateway
     *
     * @return GatewayInterface
     * @throws CouldNotSaveException
     */
    public function save(GatewayInterface $gateway);

    /**
     * Get gateway by gateway id.
     *
     * @param int $id
     *
     * @return GatewayInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getById($id);

    /**
     * Get gateway by gateway id and store id.
     *
     * @param int $gatewayId
     * @param int $storeId
     *
     * @return GatewayInterface|null
     * @throws LocalizedException
     */
    public function getByGatewayIdAndStoreId(
        int $gatewayId,
        int $storeId
    ): ?GatewayInterface;

    /**
     * Delete gateway.
     *
     * @param GatewayInterface $gateway
     *
     * @return boolean
     * @throws LocalizedException
     */
    public function delete(GatewayInterface $gateway);
}
