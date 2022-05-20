<?php

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Block\ShortcutButton;
use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddAutopayShortcuts implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        $shortcut = $shortcutButtons->getLayout()->createBlock(ShortcutButton::class);

        $shortcut
            ->setIsInCatalogProduct($observer->getEvent()->getIsCatalogProduct())
            ->setShowOrPosition($observer->getEvent()->getOrPosition());

        $shortcutButtons->addShortcut($shortcut);
    }
}
