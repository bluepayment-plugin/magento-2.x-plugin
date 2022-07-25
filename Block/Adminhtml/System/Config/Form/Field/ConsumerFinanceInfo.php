<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Backend system config array field renderer
 */
class ConsumerFinanceInfo extends Template implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    protected $_template = 'BlueMedia_BluePayment::config/consumer_finance_info.phtml';

    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }
}
