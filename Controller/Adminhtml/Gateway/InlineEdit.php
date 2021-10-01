<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateway;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway as GatewayResource;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory as GatewayCollectionFactory;

/**
 * InlineEdit Controller
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'BlueMedia_BluePayment::gateways';

    /**
     * @var GatewayResource
     */
    private $gatewayResource;

    /**
     * @var GatewayCollectionFactory
     */
    private $gatewayCollectionFactory;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @param Context $context
     * @param GatewayCollectionFactory $gatewayCollectionFactory
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context                  $context,
        GatewayResource          $gatewayResource,
        GatewayCollectionFactory $gatewayCollectionFactory,
        JsonFactory              $jsonFactory
    ) {
        parent::__construct($context);
        $this->gatewayResource = $gatewayResource;
        $this->gatewayCollectionFactory = $gatewayCollectionFactory;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Inline edit save action
     *
     * @return Json
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $items = $this->getRequest()->getParam(
            'items',
            []
        );
        if (!($this->getRequest()->getParam('isAjax') && count($items))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }

        foreach (array_keys($items) as $gatewayId) {
            $cardCollection = $this->gatewayCollectionFactory->create();
            $gatewayData = $items[$gatewayId];

            try {
                /** @var \BlueMedia\BluePayment\Model\Gateway $gateway */
                $gateway = $cardCollection->getItemById($gatewayId);
                $gateway->setData(array_merge($gateway->getData(), $gatewayData));
                $this->gatewayResource->save($gateway);
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
                $error = true;
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}
