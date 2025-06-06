# What's new in the Autopay payment gateway module?

## Version 2.26.1
- We have fixed bug related to missing header in requests to pay.autopay.eu.
- We have fixed the behavior of the module in case of no status selection for refunds.

## Version 2.26.0
- We have introduced support for gatewayList/v3, offering an even broader selection of the latest payment methods and improved gateway communication performance. The updated endpoint and expanded configuration parameters allow merchants to instantly access new, dynamically emerging payment options, resulting in higher conversion rates and an enhanced user experience.

## Version 2.25.0
- We have changed the way of creating payment returns. We have made modifications to the handling of the parameter related to the status of the return transaction (RemoteOutId - previously, the ID and status were automatically assigned when the return was generated). Currently, Magento asynchronously retrieves the status of the return using CRON.

## Version 2.24.1
- We have corrected an error with text translation for the Apple Pay method.

## Version 2.24.0
- We have added possibility for asynchronous ITN processing.
- We have added a lock on the orders table in case of multiple ITN calls.

## Version 2.23.0
- We have added FirstName and LastName to transaction start parameters.

## Version 2.22.11
- We have fixed the bug related to forcing int in Payment.php
- We have fixed a bug in translations

## Version 2.22.10
- Fix typo in config.xml

## Version 2.22.9
- We removed the place-order.js override (submission #12).

## Version 2.22.8
- We have added support for Amasty's One Step Checkout module.
- We have added the option to set a link to the relevant calculator (0 or 1%) for "Alior Instalments".
- Poprawiliśmy działanie Google Pay.

## Version 2.22.7
- We have added support for Magento 2.4.7.
- We have fixed issue with CSP for Analytics.
- We have fixed error if logo URL is empty in gateway list response.
- We have updated the information for BLIK Pay Later payment.

## Version 2.22.6
- We have added the ability to include a phone number for payment start.
- We have added new entries (for photos) to CSP whitelist.
- We have fixed a bug with multiple email sending for Google Pay / BLIK 0 / Card payment.
- We have fixed a bug with wrong redirect when "Add Store Code to URLs" was set to true (credits @piotrmatras).

## Version 2.22.5
- We have fixed support for Magneto 2.4.6 (Zend -> Laminas change).
- We have fixed the problem with status race.

## Version 2.22.4
- From now on, JS scripts are not included when the Autopay payment method is disabled.
- We have added a translation of the payment channels in the order details.
- We have removed the Pay Smartney method.

## Version 2.22.3
- We have changed the supporting text for BLIK Pay Later.

## Version 2.22.3
- We have changed the supporting text for BLIK Pay Later.

## Version 2.22.2
- We have corrected a typo in the translations.

## Version 2.22.1
- We have changed the help text for Alior installments payments.
- We have changed the text of the commission information in the administration panel.
- We have changed the link to the regulations offer in the administration panel.
- We have added a new payment method "BLIK Pay later".
- We have improved the operation in the event of an ITN status race.
- We have fixed a bug that caused the Autopay method not to be visible when only separate payment methods were available.

## Version 2.22.0
- We added the ability to set the payment method above the bulk method "Autopay" (payment by wire transfer).
- We refactored the front-end code (JS) for all Autopay channels.
- We have changed our name to Autopay.

## Version 2.21.7
- We have added new payment method **Spingo** - deffered payment for business.

## Version 2.21.6
- We have fixed a bug with **Google Pay** method.
- We have added checkout styling for the Mageplaza One Step Checkout module.

## Version 2.21.5
- We have fixed displaying the payment method when only card payment is available.

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
- We added text with information for Autopay payment method.
- We changed the appearance of the payment selection screen.
- We added help texts in module configuration and channel configuration.
- We have added new payment channel "PayPo".
- From now on, also "Card payment" and "PayPo" gateways are always displayed as separate payment methods.
- We have improved support for many Autopay services within different Magento contexts.

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
- We fixed the bug related to lack of redirection to Autopay payment.

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
