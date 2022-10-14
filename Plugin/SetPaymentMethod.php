<?php

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Model\Payment;

class SetPaymentMethod
{
    public function beforeSetPaymentMethod($subject, $payment)
    {
        if (false !== strpos($payment['method'], Payment::SEPARATED_PREFIX_CODE)) {
            $payment['method'] = 'bluepayment';
        }

        return [$payment];
    }
}
