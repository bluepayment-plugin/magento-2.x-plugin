<?php

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ShowGateways implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Yes (whitelabel)')],
            ['value' => 0, 'label' => __('No (redirect to paywall)')]
        ];
    }
}
