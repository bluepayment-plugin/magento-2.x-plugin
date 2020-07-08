<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory;

class Head extends Template
{
    /** @var CollectionFactory */
    public $gatewayFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $gatewayFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $gatewayFactory,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->gatewayFactory = $gatewayFactory;
    }

    /**
     * @return boolean
     */
    public function isTest()
    {
        return (bool)$this->_scopeConfig->getValue('payment/bluepayment/test_mode');
    }

    public function hasGPay()
    {
        $gateway = $this->gatewayFactory->create()
            ->addFieldToFilter('gateway_id', ConfigProvider::GPAY_GATEWAY_ID)
            ->getFirstItem();

        return $gateway && $gateway->isActive() && $gateway->getIsSeparatedMethod();
    }
}
