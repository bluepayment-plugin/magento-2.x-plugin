<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateways;
use BlueMedia\BluePayment\Helper\Email as EmailHelper;
use BlueMedia\BluePayment\Model\GatewaysFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Save
 *
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Save extends Gateways
{
    /**
     * @var EmailHelper
     */
    protected $_emailHelper;

    /**
     * Save constructor.
     *
     * @param Context         $context
     * @param Registry        $coreRegistry
     * @param PageFactory     $resultPageFactory
     * @param GatewaysFactory $gatewaysFactory
     * @param EmailHelper     $emailHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        GatewaysFactory $gatewaysFactory,
        EmailHelper $emailHelper
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory, $gatewaysFactory);
        $this->_emailHelper = $emailHelper;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $isPost = $this->getRequest()->getPost();

        if ($isPost) {
            $gatewaysModel = $this->_gatewaysFactory->create();
            $gatewaysId    = (int)$this->getRequest()->getParam('id', 0);

            $formData              = $this->getRequest()->getParam('gateways');
            $formData['entity_id'] = (int)$formData['id'];

            if ($gatewaysId) {
                $gatewaysModel->load($gatewaysId);
            } elseif ($formData['entity_id']) {
                $gatewaysModel->load($formData['entity_id']);
            }

            if (isset($formData['gateway_status'])
                && $gatewaysModel->getId()
                && $gatewaysModel->getGatewayStatus()
                && !$formData['gateway_status']
            ) {
                $disabledGateways = [
                    [
                        'gateway_name' => $gatewaysModel->getData('gateway_name'),
                        'gateway_id'   => $gatewaysModel->getData('gateway_id'),
                    ],
                ];
                $this->_emailHelper->sendGatewayDeactivationEmail($disabledGateways);
            }

            $gatewaysModel->setData($formData);

            try {
                $gatewaysModel->save();
                $this->messageManager->addSuccessMessage(__('The gateway has been saved.'));
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $gatewaysModel->getId(), '_current' => true]);

                    return;
                }

                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            $this->_getSession()->setFormData($formData);
            $this->_redirect('*/*/edit', ['id' => $gatewaysId]);
        }
    }
}
