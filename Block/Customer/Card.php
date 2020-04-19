<?php

namespace BlueMedia\BluePayment\Block\Customer;

use BlueMedia\BluePayment\Model\Card as CardModel;
use Magento\Framework\View\Element\Template;

class Card extends Template
{
    /** @var CardModel */
    private $card;

    /**
     * @param CardModel $card
     * @return string
     */
    public function render(CardModel $card)
    {
        $this->card = $card;

        return $this->toHtml();
    }

    /**
     * @return int
     */
    public function getCardIndex()
    {
        return $this->card->getCardIndex();
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->card->getNumber();
    }

    /**
     * @return string
     */
    public function getExpDate()
    {
        return $this->card->getValidityMonth().'/'.$this->card->getValidityYear();
    }

    /**
     * @return string
     */
    public function getIssuer()
    {
        return $this->card->getIssuer();
    }
}
