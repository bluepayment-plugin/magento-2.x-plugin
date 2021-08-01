<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ConfigProvider;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

class Head extends Template
{
    /** @var CollectionFactory */
    public $gatewayFactory;

    /** @var PriceCurrencyInterface */
    public $priceCurrency;

    /**
     * @param Context $context
     * @param CollectionFactory $gatewayFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $gatewayFactory,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->gatewayFactory = $gatewayFactory;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return boolean
     */
    public function isTest()
    {
        return (bool)$this->_scopeConfig->getValue(
            'payment/bluepayment/test_mode',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function hasGPay()
    {
        $currency = $this->getCurrentCurrencyCode();

        $serviceId = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
        );

        $gateway = $this->gatewayFactory->create()
            ->addFieldToFilter('gateway_service_id', $serviceId)
            ->addFieldToFilter('gateway_id', ConfigProvider::GPAY_GATEWAY_ID)
            ->getFirstItem();

        return $gateway && $gateway->isActive() && $gateway->isSeparatedMethod();
    }

    /**
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->priceCurrency->getCurrency()->getCurrencyCode();
    }
}
