<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
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
            '" id="' .
            $htmlId .
            '-head" onclick="Fieldset.toggleCollapse(\'' .$htmlId ."', '" .
            $this->getUrl(
                'adminhtml/*/state'
            ) . '\'); return false;"><span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

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
