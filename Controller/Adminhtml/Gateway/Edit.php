<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateway;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateway;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\ResponseInterface;

class Edit extends Gateway
{
    public const GATEWAY_REGISTER_CODE = 'adminbluepayment_gateway';

    /**
     * @return Page|ResponseInterface
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('entity_id', 0);

        $model = $this->gatewayRepository->getById($id);

        $data = $this->_session->getGatewaysData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->coreRegistry->register(self::GATEWAY_REGISTER_CODE, $model);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('BlueMedia_BluePayment::gateway');
        $resultPage->getConfig()->getTitle()->prepend(__('Gateways'));

        return $resultPage;
    }
}
