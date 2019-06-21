<?php

namespace BlueMedia\BluePayment\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Head extends Template
{
    public function isTest()
    {
        return $this->_scopeConfig->getValue('payment/bluepayment/test_mode');
    }
}
