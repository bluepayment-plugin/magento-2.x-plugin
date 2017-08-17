<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

/**
 * Class Delete
 *
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Delete extends Gateways
{
    /**
     * @return void
     */
    public function execute()
    {
        $gatewaysId = (int)$this->getRequest()->getParam('id', 0);
        if ($gatewaysId) {
            /** @var $gatewaysModel \BlueMedia\BluePayment\Model\Gateways */
            $gatewaysModel = $this->_gatewaysFactory->create();
            $gatewaysModel->load($gatewaysId);

            if (!$gatewaysModel->getId()) {
                $this->messageManager->addError(__('This gateway no longer exists.'));
            } else {
                try {
                    $gatewaysModel->delete();
                    $this->messageManager->addSuccess(__('The gateway has been deleted.'));
                    $this->_redirect('*/*/');
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                    $this->_redirect('*/*/edit', ['id' => $gatewaysModel->getId()]);
                }
            }
        }
    }
}
