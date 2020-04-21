<?php

namespace BlueMedia\BluePayment\Controller\Customer;

use BlueMedia\BluePayment\Model\Card;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey\Validator;

class DeleteCard extends CardsManagement
{
    /** @var Validator */
    private $validator;

    /** @var CardCollectionFactory */
    private $cardCollectionFactory;

    /** @var CardResource */
    private $cardResource;

    public function __construct(
        Context $context,
        Session $customerSession,
        Validator $validator,
        CardCollectionFactory $cardCollectionFactory,
        CardResource $cardResource
    ) {
        parent::__construct($context, $customerSession);

        $this->validator = $validator;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardResource = $cardResource;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('bluepayment/customer/cards');

        $request = $this->_request;
        if (!$request instanceof Http) {
            $this->createErrorResponse('Wrong request.');
            return $redirect;
        }

        if (!$this->validator->validate($request)) {
            $this->createErrorResponse('Wrong request.');
            return $redirect;
        }

        $card = $this->getCard($request);
        if ($card === null) {
            $this->createErrorResponse('Wrong card index.');
            return $redirect;
        }

        try {
            $this->cardResource->delete($card);
            $this->createSuccessMessage();
        } catch (Exception $e) {
            $this->createErrorResponse('Deletion failure. Please try again.');
        }

        return $redirect;
    }

    /**
     * @param Http $request
     *
     * @return Card|null
     */
    private function getCard(Http $request)
    {
        $cardIndex = $request->getPostValue('card_index');
        if ($cardIndex === null) {
            return null;
        }

        /** @var Card $card */
        $card = $this->cardCollectionFactory
            ->create()
            ->addFieldToFilter('card_index', (string)$cardIndex)
            ->addFieldToFilter('customer_id', (string)$this->customerSession->getCustomerId())
            ->getFirstItem();

        return $card;
    }

    /**
     * @param string $errorMessage
     *
     * @return void
     */
    private function createErrorResponse($errorMessage)
    {
        $this->messageManager->addErrorMessage(
            __($errorMessage)
        );
    }

    /**
     * @return void
     */
    private function createSuccessMessage()
    {
        $this->messageManager->addSuccessMessage(
            __('Payment card was successfully removed.')
        );
    }
}
