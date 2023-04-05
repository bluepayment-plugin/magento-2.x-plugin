<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Autopay;

use BlueMedia\BluePayment\Api\Data\CartInterface;
use BlueMedia\BluePayment\Api\Data\CartInterfaceFactory;
use BlueMedia\BluePayment\Api\Data\CartItemInterfaceFactory;
use BlueMedia\BluePayment\Api\Data\CartRuleInterfaceFactory;
use Magento\OfflineShipping\Model\SalesRule\Rule;
use Magento\Quote\Api\Data\CartInterface as MagentoCartInterface;
use Magento\SalesRule\Model\RuleFactory;

class GetCartDetails
{
    /** @var CartInterfaceFactory */
    private $cartFactory;

    /** @var CartItemInterfaceFactory */
    private $cartItemFactory;

    /** @var CartRuleInterfaceFactory */
    private $cartRuleFactory;

    /** @var RuleFactory */
    private $ruleFactory;

    /**
     * GetCartDetails constructor.
     *
     * @param CartInterfaceFactory $cartFactory
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param CartRuleInterfaceFactory $cartRuleFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        CartInterfaceFactory $cartFactory,
        CartItemInterfaceFactory $cartItemFactory,
        CartRuleInterfaceFactory $cartRuleFactory,
        RuleFactory $ruleFactory
    ) {
        $this->cartFactory = $cartFactory;
        $this->cartItemFactory = $cartItemFactory;
        $this->cartRuleFactory = $cartRuleFactory;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Prepare cart details for Autopay response.
     *
     * @param MagentoCartInterface $source
     * @return CartInterface
     */
    public function execute(MagentoCartInterface $source): CartInterface
    {
        /** @var CartInterface $cart */
        $cart = $this->cartFactory->create();
        $cart->setId((int) $source->getId());
        $cart->setCurrency($source->getQuoteCurrencyCode());
        $cart->setTotal((float) $source->getGrandTotal());

        $cart->setItems($this->getItems($source));
        $cart->setRules($this->getRules($source));

        return $cart;
    }

    /**
     * Get cart items.
     *
     * @param MagentoCartInterface $source
     * @return array
     */
    protected function getItems(MagentoCartInterface $source): array
    {
        $items = [];

        foreach ($source->getAllVisibleItems() as $item) {
            /** @var \Magento\Quote\Api\Data\CartItemInterface $item */
            $cartItem = $this->cartItemFactory->create();
            $cartItem->setId((int) $item->getItemId());
            $cartItem->setSku($item->getSku());
            $cartItem->setName($item->getName());
            $cartItem->setPrice((float) $item->getPrice());
            $cartItem->setQuantity((int) $item->getQty());

            $items[] = $cartItem;
        }

        return $items;
    }

    /**
     * Get applied rules to cart
     *
     * @param MagentoCartInterface $source
     * @return array
     */
    public function getRules(MagentoCartInterface $source): array
    {
        $rules = [];

        $appliedRuleIds = $source->getAppliedRuleIds()
            ? explode(',', $source->getAppliedRuleIds()) : [];

        foreach ($appliedRuleIds as $ruleId) {
            $rule = $this->ruleFactory->create()->load($ruleId);

            if ($rule->getId()) {
                $cartRule = $this->cartRuleFactory->create();

                $rule->loadCouponCode();
                $freeShipping = false;
                switch ($rule->getSimpleFreeShipping()) {
                    case Rule::FREE_SHIPPING_ITEM:
                    case Rule::FREE_SHIPPING_ADDRESS:
                        $freeShipping = true;
                        break;
                }

                $cartRule
                    ->setName($rule->getName())
                    ->setDescription($rule->getDescription())
                    ->setCouponCode($rule->getCouponCode())
                    ->setAmount((float) $rule->getDiscountAmount())
                    ->setAction($rule->getSimpleAction())
                    ->setFreeShipping($freeShipping);

                $rules[] = $cartRule;
            }
        }

        return $rules;
    }
}
