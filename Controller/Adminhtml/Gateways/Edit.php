<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

/**
 * Class Edit
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Edit extends Gateways
{
    const GATEWAYS_REGISTER_CODE = 'adminbluepayment_gateways';

    /**
     * @return void
     */
    public function execute()
    {
        $gatewaysId = (int)$this->getRequest()->getParam('id', 0);
        /** @var \BlueMedia\BluePayment\Model\Gateways $model */
        $model = $this->_gatewaysFactory->create();

        if ($gatewaysId) {
            $model->load($gatewaysId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This gateway no longer exists.'));
                $this->_redirect('*/*/');

                return;
            }
        }

        $data = $this->_session->getGatewaysData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register(self::GATEWAYS_REGISTER_CODE, $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('BlueMedia_BluePayment::gateways');
        $resultPage->getConfig()->getTitle()->prepend(__('Gateways'));

        return $resultPage;
    }
}