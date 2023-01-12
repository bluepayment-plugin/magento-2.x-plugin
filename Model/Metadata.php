<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use Magento\Framework\App\ProductMetadataInterface;

class Metadata
{
    private const VERSION = '2.22.9';

    /** @var ProductMetadataInterface */
    private $productMetadata;

    public function __construct(
        ProductMetadataInterface $productMetadata
    ) {
        $this->productMetadata = $productMetadata;
    }

    /**
     * Get Magento version from composer.
     *
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get Magento edition.
     *
     * @return string
     */
    public function getMagentoEdition(): string
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * Get module version.
     *
     * @return string
     */
    public function getModuleVersion(): string
    {
        return self::VERSION;
    }
}
