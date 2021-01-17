<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\ResponseInterface;

class Edit extends Gateways
{
    const GATEWAYS_REGISTER_CODE = 'adminbluepayment_gateways';

    /**
     * @return Page|ResponseInterface
     */
    public function execute()
    {
        $gatewaysId = (int)$this->getRequest()->getParam('entity_id', 0);
        /** @var \BlueMedia\BluePayment\Model\Gateways $model */
        $model = $this->gatewaysFactory->create();

        if ($gatewaysId) {
            $model->load($gatewaysId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This gateway no longer exists.'));

                return $this->_redirect('*/*/');
            }
        }

        $data = $this->_session->getGatewaysData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->coreRegistry->register(self::GATEWAYS_REGISTER_CODE, $model);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('BlueMedia_BluePayment::gateways');
        $resultPage->getConfig()->getTitle()->prepend(__('Gateways'));

        return $resultPage;
    }
}
