<?php

namespace BlueMedia\BluePayment\Block;

use Magento\Framework\View\Element\Template;

class Head extends Template
{
    public function isTest()
    {
        return $this->_scopeConfig->getValue('payment/bluepayment/test_mode');
    }
}
