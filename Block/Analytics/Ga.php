<?php

namespace BlueMedia\BluePayment\Block\Analytics;

use BlueMedia\BluePayment\Helper\Analytics\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Ga extends Template
{
    /**
     * @var Data
     */
    protected $googleAnalyticsData;

    /**
     * @param  Context  $context
     * @param  Data  $googleAnalyticsData
     * @param  array  $data
     */
    public function __construct(
        Context $context,
        Data $googleAnalyticsData,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->googleAnalyticsData = $googleAnalyticsData;
    }

    public function getAccountId()
    {
        return $this->googleAnalyticsData->getAccountId();
    }

    public function getAccountIdGa4()
    {
        return $this->googleAnalyticsData->getAccountIdGa4();
    }

    public function isAnonymizedIpActive(): bool
    {
        return $this->googleAnalyticsData->isAnonymizedIpActive();
    }

    public function getCurrency(): string
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    protected function _toHtml()
    {
        if (!$this->googleAnalyticsData->isGoogleAnalyticsAvailable() &&
            !$this->googleAnalyticsData->isGoogleAnalytics4Available()) {
            return '';
        }

        return parent::_toHtml();
    }
}
