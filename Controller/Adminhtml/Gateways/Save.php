<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

/**
 * Class Save
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Save extends Gateways
{
    /**
     * @return void
     */
    public function execute()
    {
        $isPost = $this->getRequest()->getPost();

        if ($isPost) {
            $gatewaysModel = $this->_gatewaysFactory->create();
            $gatewaysId    = (int)$this->getRequest()->getParam('id', 0);

            if ($gatewaysId) {
                $gatewaysModel->load($gatewaysId);
            }
            $formData              = $this->getRequest()->getParam('gateways');
            $formData['entity_id'] = (int)$formData['id'];
            $gatewaysModel->setData($formData);

            try {
                $gatewaysModel->save();
                $this->messageManager->addSuccess(__('The gateway has been saved.'));
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $gatewaysModel->getId(), '_current' => true]);

                    return;
                }

                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            $this->_getSession()->setFormData($formData);
            $this->_redirect('*/*/edit', ['id' => $gatewaysId]);
        }
    }
}