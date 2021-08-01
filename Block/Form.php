<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;

class Form extends \Magento\Payment\Block\Form
{
    /** @var string */
    protected $_template = 'BlueMedia_BluePayment::form.phtml';

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * Form constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if ($this->isAutopay()) {
            return 'BlueMedia_BluePayment::multishipping/bluepayment_autopay_form.phtml';
        }

        if ($this->isSeparated()) {
            return 'BlueMedia_BluePayment::multishipping/bluepayment_separated_form.phtml';
        }

        return $this->_template;
    }

    /**
     * Zwraca adres do logo firmy
     *
     * @return string|bool
     */
    public function getLogoSrc()
    {
        $logo_src = $this->getViewFileUrl('BlueMedia_BluePayment::images/logo.jpg');

        return $logo_src != '' ? $logo_src : false;
    }

    public function isAutopay()
    {
        return $this->isSeparated() && $this->getGatewayId() === ConfigProvider::AUTOPAY_GATEWAY_ID;
    }

    public function isSeparated()
    {
        return (false !== strpos($this->getMethodCode(), 'bluepayment_'));
    }

    public function getGatewayId()
    {
        return (int) str_replace('bluepayment_', '', $this->getMethodCode());
    }
}
