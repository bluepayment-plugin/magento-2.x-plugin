<?php

namespace BlueMedia\BluePayment\Plugin;

class SetPaymentMethod
{
    public function beforeSetPaymentMethod($subject, $payment)
    {
        if (false !== strpos($payment['method'], 'bluepayment_')) {
            $payment['method'] = 'bluepayment';
        }

        return [$payment];
    }
}
