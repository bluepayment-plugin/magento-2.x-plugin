<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways as GatewaysResource;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory as GatewaysCollectionFactory;

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
     * @var GatewaysResource
     */
    private $gatewaysResource;

    /**
     * @var GatewaysCollectionFactory
     */
    private $gatewaysCollectionFactory;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @param Context $context
     * @param GatewaysCollectionFactory $gatewaysCollectionFactory
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        GatewaysResource $gatewaysResource,
        GatewaysCollectionFactory $gatewaysCollectionFactory,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->gatewaysResource = $gatewaysResource;
        $this->gatewaysCollectionFactory = $gatewaysCollectionFactory;
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
            $cardCollection = $this->gatewaysCollectionFactory->create();
            $gatewayData = $items[$gatewayId];

            try {
                /** @var \BlueMedia\BluePayment\Model\Gateways $gateway */
                $gateway = $cardCollection->getItemById($gatewayId);
                $gateway->setData(array_merge($gateway->getData(), $gatewayData));
                $this->gatewaysResource->save($gateway);
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
