<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AutopayExtended extends AutopayWidth implements OptionSourceInterface
{
    /** @var OptionSourceInterface $base */
    private $base;

    /**
     * Constructor for Config Source
     *
     * @param OptionSourceInterface $base
     */
    public function __construct(
        OptionSourceInterface $base
    ) {
        $this->base = $base;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return array_merge([
            ['value' => null, 'label' => __('Use default (as on the product page)')],
        ], $this->base->toOptionArray());
    }
}
