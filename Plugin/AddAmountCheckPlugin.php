<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Plugin;

use Magento\Payment\Model\Checks\SpecificationFactory;

class AddAmountCheckPlugin
{
    /**
     * Add Autopay amount check to list of payment method checks.
     *
     * @param SpecificationFactory $subject
     * @param array|string $data
     * @return array
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function beforeCreate(SpecificationFactory $subject, $data): array
    {
        $checks = (array) $data;

        if (!in_array('bm_amount', $checks, true)) {
            $checks[] = 'bm_amount';
        }

        return [$checks];
    }
}
