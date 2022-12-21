<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AutopayTheme implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'dark', 'label' => __('Dark theme (black)')],
            ['value' => 'light', 'label' => __('Light theme (white)')],
            ['value' => 'orange', 'label' => __('Orange theme')],
            ['value' => 'gradient', 'label' => __('Gradient')],
        ];
    }
}
