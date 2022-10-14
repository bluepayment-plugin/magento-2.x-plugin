<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Payment;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;

class Form extends \Magento\Payment\Block\Form
{
    /** @var string */
    protected $_template = 'BlueMedia_BluePayment::form.phtml';

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
        $logo_src = $this->getViewFileUrl('BlueMedia_BluePayment::images/logo.svg');

        return $logo_src != '' ? $logo_src : false;
    }

    public function isAutopay(): bool
    {
        return $this->isSeparated() && $this->getGatewayId() === ConfigProvider::ONECLICK_GATEWAY_ID;
    }

    public function isSeparated(): bool
    {
        return (false !== strpos($this->getMethodCode(), Payment::SEPARATED_PREFIX_CODE));
    }

    public function getGatewayId(): int
    {
        return (int) str_replace(Payment::SEPARATED_PREFIX_CODE, '', $this->getMethodCode());
    }
}
