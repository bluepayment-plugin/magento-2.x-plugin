<?php

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Model\Payment;
use Magento\Multishipping\Model\Payment\Method\Specification\Enabled\Interceptor;

class SetEnabled
{
    public function beforeIsSatisfiedBy(Interceptor $interceptor, $code)
    {
        if (false !== strpos($code, Payment::SEPARATED_PREFIX_CODE)) {
            return 'bluepayment';
        }

        return $code;
    }
}
