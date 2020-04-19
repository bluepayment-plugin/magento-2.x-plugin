<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways;
use Exception;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Gateways
{
    /**
     * @return void|ResultInterface
     */
    public function execute()
    {
        $gatewaysId = (int)$this->getRequest()->getParam('id', 0);
        if ($gatewaysId) {
            /** @var \BlueMedia\BluePayment\Model\Gateways $gatewaysModel */
            $gatewaysModel = $this->gatewaysFactory->create();
            $gatewaysModel->load($gatewaysId);

            if (!$gatewaysModel->getId()) {
                $this->messageManager->addErrorMessage(__('This gateway no longer exists.'));
            } else {
                try {
                    $gatewaysModel->delete();
                    $this->messageManager->addSuccessMessage(__('The gateway has been deleted.'));
                    $this->_redirect('*/*/');
                } catch (Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    $this->_redirect('*/*/edit', ['id' => $gatewaysModel->getId()]);
                }
            }
        }
    }
}
