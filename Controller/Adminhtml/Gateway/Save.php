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
        /** @var Http $request */
        $request = $this->getRequest();

        $isPost = $request->getPost();

        if ($isPost) {
            $gatewaysId = (int)$this->getRequest()->getParam('id', 0);

            $formData = $this->getRequest()->getParam('gateways');
            $formData['entity_id'] = (int)$formData['id'];

            if ($gatewaysId) {
                $gatewaysModel = $this->gatewayRepository->getById($gatewaysId);
            } elseif ($formData['entity_id']) {
                $gatewaysModel = $this->gatewayRepository->getById($formData['entity_id']);
            }

            if ($gatewaysModel) {
                if (isset($formData['gateway_status'])
                    && $gatewaysModel->getId()
                    && $gatewaysModel->getStatus()
                    && !$formData['gateway_status']
                ) {
                    $disabledGateways = [
                        [
                            'gateway_name' => $gatewaysModel->getName(),
                            'gateway_id' => $gatewaysModel->getGatewayId(),
                        ],
                    ];
                    $this->emailHelper->sendGatewayDeactivationEmail($disabledGateways);
                }

                $gatewaysModel->setData($formData);

                try {
                    $this->gatewayRepository->save($gatewaysModel);
                    $this->messageManager->addSuccessMessage(__('The gateway has been saved.'));
                    if ($this->getRequest()->getParam('back')) {
                        $this->_redirect('*/*/edit', ['id' => $gatewaysModel->getId(), '_current' => true]);

                        return;
                    }

                    $this->_redirect('*/*/');

                    return;
                } catch (Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            }

            $this->messageManager->addErrorMessage(__('Invalid request'));

            $this->_getSession()->setFormData($formData);
            $this->_redirect('*/*/edit', ['id' => $gatewaysId]);
        }
    }
}
