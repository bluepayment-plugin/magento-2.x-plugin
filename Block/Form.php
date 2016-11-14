<?php

namespace BlueMedia\BluePayment\Block;
/**
 * Blok formularza z metodą płatności bluepayment
 *
 * @category    BlueMedia
 * @package     BlueMedia_BluePayment
 */
class Form extends \Magento\Payment\Block\Form
{
    protected $_template = 'BlueMedia_BluePayment::bluepayment/form.phtml';

    /**
     * Zwraca adres do logo firmy
     *
     * @return string|bool
     */
    public function getLogoSrc()
    {
        $logo_src = $this->getViewFileUrl('BlueMedia_BluePayment::bluepayment/logo.png');

        return $logo_src != '' ? $logo_src : false;
    }

    public function getMethodTitle()
    {
        return __('Online payment BM');
    }

}
