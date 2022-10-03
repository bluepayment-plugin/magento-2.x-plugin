<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /** @var SecureHtmlRenderer */
    private $secureRenderer;

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
        $this->secureRenderer = $secureRenderer;
    }

    /**
     * Add custom css class
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getFrontendClass($element): string
    {
        return parent::_getFrontendClass($element) . ' with-button';
    }

    /**
     * Return header title part of html for payment solution
     *
     * @param  AbstractElement  $element
     *
     * @return string
     */
    protected function _getHeaderTitleHtml($element): string
    {
        $html = '<div class="config-heading">';

        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"' .
            ' class="button action-configure' .
            '" id="' . $htmlId . '-head" >' .
            '<span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

        $html .= /* @noEscape */ $this->secureRenderer->renderEventListenerAsTag(
            'onclick',
            "Fieldset.toggleCollapse('" . $htmlId . "', '" . $this->getUrl('adminhtml/*/state') .
            "'); event.preventDefault();",
            'button#' . $htmlId . '-head'
        );

        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Return header comment part of html for payment solution
     *
     * @param  AbstractElement  $element
     *
     * @return string
     */
    protected function _getHeaderCommentHtml($element): string
    {
        return '';
    }

    /**
     * Get collapsed state on-load
     *
     * @param  AbstractElement $element
     *
     * @return false
     */
    protected function _isCollapseState($element): bool
    {
        return false;
    }

    /**
     * Return extra Js.
     *
     * @param  AbstractElement $element
     *
     * @return string
     */
    protected function _getExtraJs($element): string
    {
        return '';
    }
}
