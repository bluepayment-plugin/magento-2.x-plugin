<?php

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Block\Hub\ShortcutButton;
use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddHubShortcuts implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        $shortcut = $shortcutButtons->getLayout()->createBlock(ShortcutButton::class)
            ->setIsInCatalogProduct($observer->getEvent()->getIsCatalogProduct());

        $shortcutButtons->addShortcut($shortcut);
    }
}
