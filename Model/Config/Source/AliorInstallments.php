<?php

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AliorInstallments implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'one', 'label' => __('Installments 1 %')],
            ['value' => 'zero', 'label' => __('Installments 0 %')]
        ];
    }
}
