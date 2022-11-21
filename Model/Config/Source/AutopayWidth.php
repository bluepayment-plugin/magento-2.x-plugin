<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AutopayWidth implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'standard', 'label' => __('Standard width')],
            ['value' => 'full', 'label' => __('Full width')]
        ];
    }
}
