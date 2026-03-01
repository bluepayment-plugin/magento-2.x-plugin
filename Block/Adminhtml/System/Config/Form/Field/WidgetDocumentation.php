<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Widget documentation notice block in admin configuration page.
 */
class WidgetDocumentation extends Template implements RendererInterface
{
    /** @var string */
    protected $_template = 'BlueMedia_BluePayment::config/widget_documentation.phtml';

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
