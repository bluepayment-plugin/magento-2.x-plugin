<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Helper\Data;
use BlueMedia\BluePayment\Model\GetTransactionLifetime;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;

class Info extends \Magento\Payment\Block\Info
{
    /** @var Data */
    public $helper;

    /** @var GatewayFactory */
    private $gatewayFactory;

    /** @var Session */
    private $checkoutSession;

    /** @var UrlInterface */
    private $url;

    /** @var GetTransactionLifetime */
    private $getTransactionLifetime;

    protected $_template = 'BlueMedia_BluePayment::payment/info.phtml';

    /**
     * @param GatewayFactory $gatewayFactory
     * @param Session $checkoutSession
     * @param Url $url
     * @param Data $helper
     * @param GetTransactionLifetime $getTransactionLifetime
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        GatewayFactory $gatewayFactory,
        Session $checkoutSession,
        Url $url,
        Data $helper,
        GetTransactionLifetime $getTransactionLifetime,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->gatewayFactory = $gatewayFactory;
        $this->checkoutSession = $checkoutSession;
        $this->url = $url;
        $this->helper = $helper;
        $this->getTransactionLifetime = $getTransactionLifetime;
    }

    /**
     * Get used gateway name from quote.
     *
     * @return array|mixed|null
     * @throws LocalizedException
     */
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

    /**
     * Get Quote instance from checkout session.
     *
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get used channel (gateway) name.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getGatewayNameFromOrder(): ?string
    {
        /** @var Payment $info */
        $payment = $this->getInfo();

        return $payment->getOrder()->getPaymentChannel();
    }

    /**
     * Get continuation link
     *
     * @return string|boolean
     * @throws LocalizedException
     */
    public function getContinuationLink()
    {
        /** @var Payment $info */
        $payment = $this->getInfo();
        $order = $payment->getOrder();
        $state = $payment->getAdditionalInformation('bluepayment_state');

        $lifetime = $this->getTransactionLifetime->getForOrder($order);

        if ($lifetime === false) {
            return false;
        }

        if ($state === 'SUCCESS') {
            return false;
        }

        return $this->generateLink($order);
    }

    /**
     * Generate link to continuation
     *
     * @param Order $order
     * @return string
     */
    private function generateLink(Order $order): string
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
