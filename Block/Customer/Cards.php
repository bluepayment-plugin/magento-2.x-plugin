<?php

namespace BlueMedia\BluePayment\Block\Customer;

use BlueMedia\BluePayment\Model\ResourceModel\Card\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;

class Cards extends Template
{
    /** @var CardCollectionFactory */
    private $cardCollectionFactory;

    /** @var Session */
    private $session;

    public function __construct(
        Template\Context $context,
        CardCollectionFactory $cardCollectionFactory,
        Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->session = $session;
    }

    /**
     * @return Collection
     */
    public function getCards()
    {
        $collection = $this->cardCollectionFactory->create();

        $collection
            ->addFieldToFilter('customer_id', (string) $this->session->getCustomerId())
            ->load();

        return $collection;
    }

    /**
     * @param \BlueMedia\BluePayment\Model\Card $card
     * @return string
     */
    public function renderCardHtml($card)
    {
        foreach ($this->getChildNames() as $childName) {
            /** @var Card $childBlock */
            $childBlock = $this->getChildBlock($childName);

            return $childBlock->render($card);
        }

        return '';
    }
}
