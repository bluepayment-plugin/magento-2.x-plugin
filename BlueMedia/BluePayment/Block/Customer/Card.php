<?php

namespace BlueMedia\BluePayment\Block\Customer;

use BlueMedia\BluePayment\Model\Card as CardModel;
use Magento\Framework\View\Element\Template;

class Card extends Template
{
    /** @var CardModel */
    private $card;

    public function render(CardModel $card)
    {
        $this->card = $card;

        return $this->toHtml();
    }

    public function getCardIndex()
    {
        return $this->card->getCardIndex();
    }

    public function getNumber()
    {
        return $this->card->getNumber();
    }

    public function getExpDate()
    {
        return $this->card->getValidityMonth().'/'.$this->card->getValidityYear();
    }

    public function getIssuer()
    {
        return $this->card->getIssuer();
    }
}
