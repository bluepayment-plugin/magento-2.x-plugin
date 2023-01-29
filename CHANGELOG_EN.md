# What's new in BluePayment?

## Version 2.21.4
- We have added a new payment method **Visa Mobile**.
- We have added information about the test environment in the administration panel.
- We have updated the description of **PayPo** method.
- We have fixed a bug with incorrect use of `array_contains` method instead of `in_array` - [GitHub #8](https://github.com/bluepayment-plugin/magento-2.x-plugin/issues/8).
- We have fixed a bug with missing return comment for order details in case of **--Do not change status--** option.
- We have standardized the text **Configuration key (hash)** (formerly _Shared key_) in the module configuration.
- We have added information about the current version of the platform and modules when starting a transaction.

## Version 2.21.3
- We have added the option "Disable the payment continuation link with the expiration of the transaction".
- We have improved the performance of GA4 analytics - when it is disabled, product information is no longer retrieved.
- We have improved the retrieval of product category information in GA4 analytics.

## Version 2.21.2
- We have fixed integration with BluePaymentGraphQl.
- We have fixed problem with ConsumerFinance block.

## Version 2.21.1
- We have improved support for Magento 2.3.*.

## Version 2.21.0
- We have added the SEK currency.
- We added text with information for BM payment method.
- We changed the appearance of the payment selection screen.
- We added help texts in module configuration and channel configuration.
- We have added new payment channel "PayPo".
- From now on, also "Card payment" and "PayPo" gateways are always displayed as separate payment methods.
- We have improved support for many BM services within different Magento contexts.

## Version 2.20.0
- We have added the **initialize** method to the **BluePayment\Model\Method\BluePayment**, which sets the default order status, according to the "Payment waiting status" setting in the module configuration (only for orders placed using the BlueMedia payment method).
- We have added support for Magento 2.4.4 and PHP 8.1.
- We added the promotion of Consumer Finance payments.
- We improved the appearance of the payment selection.

## Version 2.19.2
- We've separated the consent display into a separate knockout view.
- We've added the host `platnosci-accept.bm.pl` to CSP.
- We've corrected an error when creating Google Pay payments.

## Version 2.19.1
- Bugfix - we've removed invalid component in default.xml

## Version 2.19.0
- We've added extended Google Analytics 4.
- We've improved calculation of the order amount when selecting a different currency.
- We've added dispatching events `bluemedia_payment_failure`, `bluemedia_payment_pending` and `bluemedia_payment_success` after receiving new payment status.

## Version 2.18.0
- We have updated the payment channel synchronization support to v2.
- We have improved the behavior when handling payment notifications (race-condition).
- We have changed the name of the payment gateway.

## Version 2.17.1
- We fixed sorting payment channels and methods (only for multishipping and GraphQL)

## Version 2.17.0
- We added a configuration option to enable/disable **BLIK 0**.
- We added a link to continue payment in the order detail and shopping thank you email.

## Version 2.16.0
- We added support for formal consents.
- We changed the endpoint for downloading payment channels to `gatewayList` (REST API).
- We fixed a bug with double entry of transactions at ITN.
- We added an option **Do not change status** when setting statuses for returns.
- We updated the User Manual and README.md.
- We have updated the BlueMedia logo.
- We hid the BlueMedia method when no payment channel is available (or all available ones are set as a separate payment method).

## Version 2.15.0
- We added support for "Delivery to multiple addresses (multishipping)".
- We changed the configuration scope from SCOPE_WEBSITE to SCOPE_STORE.

### Version 2.14.6
- We improved payment channel selection.

## Version 2.14.2
- We changed amounts for "Smartney - Buy now, pay later". - from the range ~~100 zł - 2000 zł~~ to **100 zł - 2500 zł**.
- We added a "Language" parameter to start the transaction - consistent with the store language of the order.

## Version 2.14.1
- We changed the amounts for "Smartney - Buy now, pay later" - from the range ~~200 zł - 1500 zł~~ to **100 zł - 2000 zł**.

## Version 2.14.0
- We added "Show payment channels in store" option - enabled by default.
- We corrected the "Payment expiration time" option.

## Version 2.13.7
- We improved the behavior of the module when Google Pay is disabled.

## Version 2.13.6
- We unlocked Google Pay for all currencies.

## Version 2.13.5
- We corrected placing order - order method instead of authorize.
- We improved db_schema.
- We updated content security policy.
- We updated composer.json.

## Version 2.13.4
- We improved ordering - **order** method instead of **authorize**.

## Version 2.13.3
- We improved payment channels configuration module.
- We improved channel synchronization for multiple sites.
- We disabled clickable overlay for BLIK 0 and Google Pay.

## Version 2.13.2
- We improved the display of the custom BLIK 0 logo.

## Version 2.13.1
- We fixed the custom validators (additional-validators) in placeOrder.

## Version 2.13.0
- We added a new payment method: Pay Smartney - Buy now, pay later (i.e. deferred payments). This service is available for transactions made in Polish currency.

## Version 2.12.0
- We added the ability to order refunds online via Credit Memo.

## Version 2.11.0
- Added a page to wait for redirection to payment.

## Version 2.10.0
- We added option to send payment link for orders created from admin panel.
- We disabled unnecessary queries to pay.google.com.
- We added informational text next to the Apple Pay channel.
- We changed the logs creation path to `var/log/BlueMedia/Bluemedia-[data].log`.
- We added payment method information to the order list table in the admin panel.
- We added payment_channel variable containing payment channel name to email templates.

## Version 2.9.0
- We added an expandable list of channels.
- We hid the payment channel name on the payment step.

## Version 2.8.2
- We made changes to the returns module and the payment return page
- We added support for currencies: RON, HUF, BGN, UAH.
- Changes to the return from payment page.

## Version 2.8.1
- We adapted the module to Magento Marketplace requirements.

## Version 2.8.0
- We have customized the module to meet Magento Marketplace requirements.

## Version 2.7.7
- We fixed a bug that sometimes caused the BLIK 0 window to not display after entering a code.

## Version 2.7.6
- We added support for a new currency: CZK.

## Version 2.7.5
- Displayed all available statuses in module configuration.

## Version 2.7.4
- We adjusted payment module to Google Pay API 2.0 requirements.
- We simplified the Google Pay configuration.

## Version 2.7.3.
- We changed the sorting mechanism.
- We fixed the bug related to lack of redirection to BM payment.

## Version 2.7.2
- We adjusted module to Magento Marketplace requirements.
- With this version, the structure of the zip file and the command to install and update has changed!

## Version 2.7.1
- We added support for Magento 2.3.1

## Version 2.7.0
- We added automatic payments

## Version 2.6.0
- We added support for Magento 2.3.0
- We added direct payment via Google Pay.

## Version 2.4.0
- We added support for currencies: GBP, EUR, USD.

## Version 2.3.0
- We added support for returns.
