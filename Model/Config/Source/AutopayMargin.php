<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AutopayMargin implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'margin-0', 'label' => '0 px'],
            ['value' => 'margin-10', 'label' => '10 px'],
            ['value' => 'margin-15', 'label' => '15 px'],
            ['value' => 'margin-20', 'label' => '20 px'],
        ];
    }
}
