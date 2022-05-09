<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateway;

use BlueMedia\BluePayment\Api\GatewayRepositoryInterface;
use BlueMedia\BluePayment\Controller\Adminhtml\Gateway;
use BlueMedia\BluePayment\Helper\Email as EmailHelper;
use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Api\Data\GatewayInterfaceFactory;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Save extends Gateway
{
    /** @var EmailHelper */
    public $emailHelper;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param GatewayInterfaceFactory $gatewayFactory
     * @param GatewayRepositoryInterface $gatewayRepository
     * @param Logger $logger
     * @param EmailHelper $emailHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        GatewayInterfaceFactory $gatewayFactory,
        GatewayRepositoryInterface $gatewayRepository,
        Logger $logger,
        EmailHelper $emailHelper
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory, $gatewayFactory, $gatewayRepository, $logger);
        $this->emailHelper = $emailHelper;
    }

    /**
     * @return void|ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id = $this->getRequest()->getParam('entity_id');
            $gateway = $this->gatewayRepository->getById($id);

            $gateway->setData($data);

            try {
                $this->gatewayRepository->save($gateway);
                $this->messageManager->addSuccessMessage(__('The gateway has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $gateway->getId(), '_current' => true]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            $this->messageManager->addErrorMessage(__('Invalid request'));

            $this->dataPersistor->set('bluemedia_bluepayment_gateway', $data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
