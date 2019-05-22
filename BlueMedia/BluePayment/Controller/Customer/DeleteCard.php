<?php

namespace BlueMedia\BluePayment\Controller\Customer;

use BlueMedia\BluePayment\Model\Card;
use BlueMedia\BluePayment\Model\ResourceModel\Card as CardResource;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
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

    public function execute()
    {
        $request = $this->_request;
        if (!$request instanceof Http) {
            return $this->createErrorResponse('Wrong request.');
        }

        if (!$this->validator->validate($request)) {
            return $this->createErrorResponse('Wrong request.');
        }

        $card = $this->getCard($request);
        if ($card === null) {
            return $this->createErrorResponse('Wrong card index.');
        }

        try {
            $this->cardResource->delete($card);
            $this->createSuccessMessage();
        } catch (\Exception $e) {
            $this->createErrorResponse('Deletion failure. Please try again.');
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('bluepayment/customer/cards');

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

        return $this->cardCollectionFactory
            ->create()
            ->addFieldToFilter('card_index', $cardIndex)
            ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
            ->getFirstItem();
    }

    /**
     * @param string $errorMessage
     */
    private function createErrorResponse($errorMessage)
    {
        $this->messageManager->addErrorMessage(
            __($errorMessage)
        );
    }

    private function createSuccessMessage()
    {
        $this->messageManager->addSuccessMessage(
            __('Payment card was successfully removed.')
        );
    }

}
