<?php

namespace BlueMedia\BluePayment\Plugin;

use Magento\Multishipping\Model\Payment\Method\Specification\Enabled\Interceptor;

class SetEnabled
{
    public function beforeIsSatisfiedBy(Interceptor $interceptor, $code)
    {
        if (false !== strpos($code, 'bluepayment_')) {
            return 'bluepayment';
        }

        return $code;
    }
}
