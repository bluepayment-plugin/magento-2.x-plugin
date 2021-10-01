<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Gateway\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Edit gateway form
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
                'id'     => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post',
            ],
        ]);
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
