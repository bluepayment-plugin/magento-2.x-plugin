<?php

namespace BlueMedia\BluePayment\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class Options
 */
class StoreColumn implements OptionSourceInterface
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository
    )
    {
        $this->storeRepository = $storeRepository;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $stores = [];
        foreach ($this->storeRepository->getList() as $store) {
            $stores[] = [
                'value' => $store->getId(),
                'label' => $store->getName(),
            ];
        }
        return $stores;
    }
}
