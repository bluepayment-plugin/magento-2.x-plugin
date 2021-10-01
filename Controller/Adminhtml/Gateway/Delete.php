<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateway;

use BlueMedia\BluePayment\Controller\Adminhtml\Gateway;
use Exception;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Gateway
{

    public function execute()
    {
        try {
            $id = (int) $this->getRequest()->getParam('id', 0);

            if ($id === 0) {
                throw new LocalizedException(__('Invalid gateway id. Should be numeric value greater than 0'));
            }

            $gateway = $this->gatewayRepository->getById($id);
            $this->gatewayRepository->delete($gateway);

            $this->messageManager->addSuccessMessage(__('The gateway has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage('This gateway doesn\'t exist.');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Can not delete gateway.'));
        }

        return $this->_redirect("*/*/");
    }
}
