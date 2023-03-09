<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AutopayArrangement implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'horizontal', 'label' => __('Horizontal')],
            ['value' => 'horizontal-reversed', 'label' => __('Horizontal reversed')],
            ['value' => 'vertical', 'label' => __('Vertical')],
            ['value' => 'vertical-reversed', 'label' => __('Vertical reversed')],
        ];
    }
}
