<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Gateways\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Class Form
 * @package BlueMedia\BluePayment\Block\Adminhtml\Gateways\Edit
 */
class Form extends Generic
{
    /**
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create([
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]);
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}