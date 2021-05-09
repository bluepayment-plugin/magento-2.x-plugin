<?php

namespace BlueMedia\BluePayment\Block\Checkout\Payment;

use Magento\Multishipping\Block\Checkout\Payment\Info;

class BluePaymentMultishipping extends Info
{
    /**
     * @return string
     */
    protected function _toHtml()
    {
        $html = '';
        $block = $this->getChildBlock($this->_getInfoBlockName());
        if ($block) {
            $html = $block->toHtml();
        }

        return $html;
    }
}
