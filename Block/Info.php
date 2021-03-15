<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory as GatewayFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Info extends \Magento\Payment\Block\Info
{
    /** @var GatewayFactory */
    private $gatewayFactory;

    /** @var Session */
    private $checkoutSession;

    /** @var string */
    private $websiteCode;

    public function __construct(
        GatewayFactory $gatewayFactory,
        Session $checkoutSession,
        Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->gatewayFactory = $gatewayFactory;
        $this->checkoutSession = $checkoutSession;
        $this->websiteCode = $this->_storeManager->getWebsite()->getCode();
    }

    public function getGatewayName()
    {
        $gatewayId = $this->getInfo()->getAdditionalInformation('gateway_id') ?? false;

        if (!$gatewayId) {
            return null;
        }

        $currency = $this->getQuote()->getQuoteCurrencyCode();
        $serviceId = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_WEBSITE,
            $this->websiteCode
        );

        $gateway = $this->gatewayFactory->create()
            ->addFieldToFilter('gateway_service_id', $serviceId)
            ->addFieldToFilter('gateway_id', $gatewayId)
            ->getFirstItem();

        return $gateway->getData('gateway_name');
    }

    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
