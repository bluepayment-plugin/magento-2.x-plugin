<?php

namespace BlueMedia\BluePayment\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Head extends Template
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        Context $context,
        array $data = [],
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        return parent::__construct($context, $data);
    }

    public function isTest()
    {
        return $this->scopeConfig->getValue('payment/bluepayment/test_mode');
    }
}
