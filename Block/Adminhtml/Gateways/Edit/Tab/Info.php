<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Gateways\Edit\Tab;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways\Edit as GatewaysController;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;

/**
 * Class Info
 *
 * @package BlueMedia\BluePayment\Block\Adminhtml\Gateways\Edit\Tab
 */
class Info extends Generic implements TabInterface
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @param Context     $context
     * @param Registry    $registry
     * @param FormFactory $formFactory
     * @param Config      $wysiwygConfig
     * @param array       $data
     */
    public function __construct(
        Context     $context,
        Registry    $registry,
        FormFactory $formFactory,
        Config      $wysiwygConfig,
        array       $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Gateways Info');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Gateways Info');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form fields
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        /** @var $model \BlueMedia\BluePayment\Model\Gateways */
        $model = $this->_coreRegistry->registry(GatewaysController::GATEWAYS_REGISTER_CODE);

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('gateways_');
        $form->setFieldNameSuffix('gateways');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General')]);

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField('gateway_status', 'select', [
            'name'     => 'gateway_status',
            'label'    => __('Gateway Status'),
            'required' => true,
            'options'  => ['1' => __('Yes'), '0' => __('No')],
        ]);
        $fieldset->addField('gateway_currency', 'text', [
            'name'     => 'gateway_currency',
            'label'    => __('Gateway Currency'),
            'required' => true,
            'disabled' => true,
        ]);
        $fieldset->addField('gateway_id', 'text', [
            'name'     => 'gateway_id',
            'label'    => __('Gateway ID'),
            'required' => true,
            'disabled' => true,
        ]);
        $fieldset->addField('bank_name', 'text', [
            'name'     => 'bank_name',
            'label'    => __('Bank Name'),
            'required' => true,
            'disabled' => true,
        ]);
        $fieldset->addField('gateway_name', 'text', [
            'name'     => 'gateway_name',
            'label'    => __('Gateway Name'),
            'required' => true,
        ]);
        $fieldset->addField('gateway_description', 'text', [
            'name'     => 'gateway_description',
            'label'    => __('Gateway Description'),
            'required' => false,
        ]);
        $fieldset->addField('gateway_sort_order', 'text', [
            'name'     => 'gateway_sort_order',
            'label'    => __('Gateway Sort Order'),
            'required' => false,
        ]);
        $fieldset->addField('gateway_type', 'text', [
            'name'     => 'gateway_type',
            'label'    => __('Gateway Type'),
            'required' => true,
            'disabled' => true,
        ]);
        $fieldset->addField('is_separated_method', 'select', [
            'name'     => 'is_separated_method',
            'label'    => __('Is separated method'),
            'required' => false,
            'options'  => ['1' => __('Yes'), '0' => __('No')],
        ]);
        $fieldset->addField('gateway_logo_url', 'text', [
            'name'     => 'gateway_logo_url',
            'label'    => __('Gateway Logo URL'),
            'required' => false,
            'disabled' => true,
        ]);
        $fieldset->addField('use_own_logo', 'select', [
            'name'     => 'use_own_logo',
            'label'    => __('Use Own Logo'),
            'required' => false,
            'options'  => ['1' => __('Yes'), '0' => __('No')],
        ]);
        $fieldset->addField('gateway_logo_path', 'text', [
            'name'     => 'gateway_logo_path',
            'label'    => __('Gateway Logo Path'),
            'required' => false,
        ]);
        $fieldset->addField('status_date', 'date', [
            'name'        => 'status_date',
            'date_format' => 'yyyy-MM-dd',
            'time_format' => 'hh:mm:ss',
            'label'       => __('Status Date'),
            'required'    => false,
            'disabled'    => true,
        ]);
        $fieldset->addField('force_disable', 'select', [
            'name'     => 'force_disable',
            'label'    => __('Force Disable Gateway'),
            'required' => false,
            'options'  => ['1' => __('Yes'), '0' => __('No')],
        ]);

        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
