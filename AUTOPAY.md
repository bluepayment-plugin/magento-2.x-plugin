# Autopay Checkout for Magneto 2

## Technical requirements
- Plugin works with Magento version 2.3.4 - 2.4.5.
- It's adding new payment method - with code `autopay`. If you have integrations with CRM, ERP or other software which fetching orders and statuses from Magento - **make sure it will be accepting new payment method code**. 

Plugin is based on default Magento_Luma theme, but it should work on any other theme if you meet the following requirements:
- Layout `default.xml` must contain block `head.additional` available and all children must be printed in template. It's reqlated to load required JS SDK.
- Layout `catalog_product_view.xml` must contain block `addtocart.shortcut.buttons.additional` and children must be printed in template related to block (`<?= $block->getChildHtml('', true) ?>`) - suggested is to put it after "Add to cart" button. In this place button "Pay with Autopay" will be rendered.
- Product page (also layout `catalog_product_view.xml` and related template) must contain default magento JS - at least: `jquery` `uiComponent` `Magento_Customer/js/customer-data` `mage/url` `Magento_Ui/js/modal/alert` which are used for correct working of APC button.
- Layout `checkout_cart_index.xml` should contain block `checkout.cart.shortcut.buttons` - to show APC button in cart.

For **paczkomaty**, it provides default support for module **Smartmage_Inpost**. Any other solutions requires manual adaptation, more described in [Paczkomaty](#paczkomaty)


## Installation

1. Execute the command:
```
composer require bluepayment-plugin/module-bluepayment:dev-autopay
```
(during testing time, Autopay Checkout is not available in official release)
2. While in the Magento root directory, run the following commands:
- `bin/magento module:enable BlueMedia_BluePayment --clear-static-content`
- `bin/magento setup:upgrade`
- `bin/magento setup:di:compile`
- `bin/magento cache:flush`
3. Go to configuration.


## Configuration

1. Go to **Stores** -> **Configuration** -> **Payment Methods**.
2. Expand **Autopay** section.
3. Set configuration:
   1. Set **Enabled** and **Test Mode** to **Yes**,
   2. Fill **Secret Key** and **Merchant ID** with data received from Autopay.
4. Refresh cache


## Paczkomaty

For default, Autopay is compatible with official Inpost module **Smartmage_Inpost**.   
If you're using custom solution, you need to make some minor modifications.

Example repository with custom modification:  
https://github.com/zalazdi/autopay-add-paczkomat

Autopay Checkout integration requires that "paczkomat" delivery method must have:  
`carrierCode` = `inpostlocker`  
`methodCode` = `standard`  
Only in this configuration, mobile application will give customers possibility to choose locker and sent this information to shop.

In simple words - customization requires to wrap `getShippingMethod` and `setShippingMethod` of `BlueMedia\BluePayment\Model\QuoteManagement` model by using [Plugins (Interceptors)](https://developer.adobe.com/commerce/php/development/components/plugins/).
1. For `getShippingMethod` you have to map your custom `carrierCode` to `inpostlocker` and `methodCode` to `standard`
2. For `setShippingMethod` you should make reverse-mapping of aforementioned.

## Hidden mode
Autopay Checkout can be used in hidden mode.
It means that customer will not see any button or link to APC without added GET parameter `?test_autopay` to URL.
It's working also with Full Page Cache - due to different GET parameters.

This mode is useful when you want to test APC on your production environment, but you don't want to show it to your customers.

### Change request key
By default, key to enable button in hidden mode is `test_autopay`.
You can change it by adding to `di.xml`:
```xml
<type name="BlueMedia\BluePayment\Model\Autopay\ShouldShowAutopay">
    <arguments>
        <argument name="requestKey" xsi:type="string">test_autopay</argument>
    </arguments>
</type>
```
