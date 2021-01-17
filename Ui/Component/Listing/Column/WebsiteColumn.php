<?php

namespace BlueMedia\BluePayment\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class Options
 */
class WebsiteColumn implements OptionSourceInterface
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $websites = [];
        foreach ($this->websiteRepository->getList() as $website) {
            if ($website->getCode() === WebsiteInterface::ADMIN_CODE) {
                continue;
            }
            $websites[] = [
                'value' => $website->getId(),
                'label' => $website->getName(),
            ];
        }
        return $websites;
    }
}
