<?php

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\GatewayInterface;
use BlueMedia\BluePayment\Api\Data\GatewayInterfaceFactory;
use BlueMedia\BluePayment\Api\GatewayRepositoryInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway as GatewayResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class GatewayRepository implements GatewayRepositoryInterface
{
    /** @var GatewayInterfaceFactory */
    private $factory;

    /** @var GatewayResource */
    private $resource;

    public function __construct(
        GatewayInterfaceFactory $factory,
        GatewayResource $resource
    ) {
        $this->factory = $factory;
        $this->resource = $resource;
    }

    /**
     * Save a gateway.
     *
     * @param GatewayInterface $gateway
     *
     * @return GatewayInterface
     * @throws CouldNotSaveException
     */
    public function save(GatewayInterface $gateway)
    {
        try {
            $this->resource->save($gateway);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }

        return $gateway;
    }


    /**
     * Get gateway by gateway id.
     *
     * @param int $id
     *
     * @return GatewayInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getById($id)
    {
        $gateway = $this->factory->create();
        $this->resource->load(
            $gateway,
            $id,
            GatewayInterface::ENTITY_ID
        );
        if (! $gateway->getId()) {
            throw new NoSuchEntityException(__('Unable to find gateway with Entity ID "%1"', $id));
        }

        return $gateway;
    }

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(GatewayInterface $gateway)
    {
        try {
            $this->resource->delete($gateway);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()), $e);
        }

        return true;
    }
}
