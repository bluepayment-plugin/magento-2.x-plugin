<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Info extends \Magento\Payment\Block\Info
{
    /** @var GatewayFactory */
    private $gatewayFactory;

    /** @var Session */
    private $checkoutSession;

    /** @var UrlInterface */
    private $url;

    /** @var Data */
    public $helper;

    protected $_template = 'BlueMedia_BluePayment::payment/info.phtml';

    /**
     * @param  GatewayFactory  $gatewayFactory
     * @param  Session  $checkoutSession
     * @param  Url  $url
     * @param  Template\Context  $context
     * @param  array  $data
     */
    public function __construct(
        GatewayFactory $gatewayFactory,
        Session $checkoutSession,
        Url $url,
        Data $helper,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->gatewayFactory = $gatewayFactory;
        $this->checkoutSession = $checkoutSession;
        $this->url = $url;
        $this->helper = $helper;
    }

    public function getGatewayNameFromQuote()
    {
        $gatewayId = $this->getInfo()->getAdditionalInformation('gateway_id') ?? false;

        if (!$gatewayId) {
            return null;
        }

        $currency = $this->getQuote()->getQuoteCurrencyCode();
        $serviceId = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . strtolower($currency) . '/service_id',
            ScopeInterface::SCOPE_STORE
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

    public function getGatewayNameFromOrder()
    {
        /** @var \Magento\Sales\Model\Order\Payment $info */
        $payment = $this->getInfo();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        return $order->getPaymentChannel();
    }

    public function getContinuationLink()
    {
        /** @var \Magento\Sales\Model\Order\Payment $info */
        $payment = $this->getInfo();
        $state = $payment->getAdditionalInformation('bluepayment_state');

        if ($state != 'SUCCESS') {
            if ($payment->hasAdditionalInformation('bluepayment_redirect_url')) {
                return $payment->getAdditionalInformation('bluepayment_redirect_url');
            }

            return $this->generateLink($payment->getOrder());
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    private function generateLink($order)
    {
        $this->url->setScope($order->getStore());

        $currency = strtolower($order->getOrderCurrencyCode());
        $serviceId = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . $currency . '/service_id',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $sharedKey = $this->_scopeConfig->getValue(
            'payment/bluepayment/' . $currency . '/shared_key',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        $hash = $this->helper->generateAndReturnHash([
            $serviceId,
            $order->getIncrementId(),
            $sharedKey
        ]);

        return $this->url->getUrl('bluepayment/processing/continuepayment', [
            '_secure' => true,
            '_query' => [
                'order_id' => $order->getIncrementId(),
                'hash' => $hash,
            ]
        ]);
    }
}
