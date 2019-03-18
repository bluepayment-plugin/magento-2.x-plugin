<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways\Edit as GatewaysController;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

/**
 * Class Edit
 *
 * @package BlueMedia\BluePayment\Block\Adminhtml\Gateways
 */
class Edit extends Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @param Context  $context
     * @param Registry $registry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId   = 'id';
        $this->_controller = 'adminhtml_gateways';
        $this->_blockGroup = 'BlueMedia_BluePayment';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->add('saveandcontinue', [
            'label'          => __('Save and Continue Edit'),
            'class'          => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'button' => [
                        'event'  => 'saveAndContinueEdit',
                        'target' => '#edit_form',
                    ],
                ],
            ],
        ], -100);
        $this->buttonList->update('delete', 'label', __('Delete'));
    }

    /**
     * Retrieve text for header element depending on loaded gateways
     *
     * @return string
     */
    public function getHeaderText()
    {
        $gatewaysRegistry = $this->coreRegistry->registry(GatewaysController::GATEWAYS_REGISTER_CODE);
        if ($gatewaysRegistry->getId()) {
            $gatewaysTitle = $this->escapeHtml($gatewaysRegistry->getTitle());

            return __("Edit Gateway '%1'", $gatewaysTitle);
        } else {
            return __('Add Gateway');
        }
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('post_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'post_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'post_content');
                }
            };
        ";

        return parent::_prepareLayout();
    }
}
