<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Introduction block in admin configuration page.
 */
class TestMode extends Template implements RendererInterface
{
    /** @var string */
    protected $_template = 'BlueMedia_BluePayment::config/test_mode.phtml';

    /**
     * Render template
     *
     * @param  AbstractElement  $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(AbstractElement $element): string
    {
        return $this->toHtml();
    }
}
