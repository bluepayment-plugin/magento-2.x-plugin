<?php

namespace BlueMedia\BluePayment\Block\Processing;

use Magento\Framework\View\Element\Template;

class Redirect extends Template
{
    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getData('redirectURL');
    }

    /**
     * @return string
     */
    public function getSeconds()
    {
        return $this->getData('seconds');
    }
}
