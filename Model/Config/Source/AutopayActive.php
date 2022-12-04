<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AutopayActive implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 2, 'label' => __('Hidden')],
            ['value' => 1, 'label' => __('Yes')],
            ['value' => 0, 'label' => __('No')]
        ];
    }
}
