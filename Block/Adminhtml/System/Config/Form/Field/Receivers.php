<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\View\Design\Theme\LabelFactory;

/**
 * Backend system config array field renderer
 */
class Receivers extends AbstractFieldArray
{
    /** @var Factory */
    public $elementFactory;

    /** @var LabelFactory */
    public $labelFactory;

    /**
     * @param Context       $context
     * @param Factory       $elementFactory
     * @param LabelFactory  $labelFactory
     * @param array         $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        LabelFactory $labelFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->labelFactory   = $labelFactory;
        parent::__construct($context, $data);
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('email', ['label' => __('Email')]);
        $this->addColumn('name', ['label' => __('Name')]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add Receiver');
        parent::_construct();
    }
}
