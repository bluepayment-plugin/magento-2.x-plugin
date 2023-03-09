<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Observer;

use BlueMedia\BluePayment\Block\ShortcutButton;
use Magento\Catalog\Block\ShortcutButtons;
use Magento\Checkout\Block\QuoteShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class AddAutopayShortcuts implements ObserverInterface
{
    /**
     * Add shortcut buttons to catalog product view page
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var ShortcutButtons|QuoteShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        $shortcut = $shortcutButtons->getLayout()->createBlock(ShortcutButton::class);

        $scope = $observer->getEvent()->getIsCatalogProduct()
            ? 'product'
            : (
                ($shortcutButtons instanceof QuoteShortcutButtons)
                    ? 'cart'
                    : 'minicart'
            );

        $shortcut
            ->setScope($scope);

        $shortcutButtons->addShortcut($shortcut);
    }
}
