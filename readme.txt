=== WooCommerce 3D Secure (Bankart) Payment Gateway ===
Contributors: geca
Tags: woocommerce, payment gateway, gateway, credit card, bankart, 3d secure, visa, mastercard
Requires at least: 4.7
Tested up to: 5.2.3
Requires WooCommerce at least: 3.0
Tested WooCommerce up to: 3.7.0
Stable tag: trunk
License: GPLv2 or later

== Description ==
> **Requires: WooCommerce 3.0+**

This plugin allows your customers to make payments using MasterCard or Visa via 3D Secure Payment Gateway provided by Bankart (http://www.bankart.si). Slovene banks that currently support this gateway are NLB, Abanka, NKBM, and SKB.

After choosing this payment method, the customer is redirected to Bankart's HPP (Hosted Payment Page) where she is able to input her credit card details. After transaction, the customer is then redirected back to your shop, either to success or to error page.

Before getting response from Bankart, customer's order is considered "pending". If the transactions succeeds, order is set to "completed", otherwise to "failed".

This plugin logs extensively. Make sure to check log messages inside wp-content/uploads/wc-logs/bankart-*.log file before reporting any issues.

== Frequently Asked Questions ==

=== Why am I getting "White Screen of Death" before/during/after the purchase? ===

There can be many possible reasons for this. Please check the log file prefixed by 'bankart' inside 'wp-content/uploads/wc-logs' for errors and create a Support ticket if you're unable to solve the problem yourself.

=== What about Diners and/or installments? ===

I'm offering a premium plugin that covers Diners and has support for installments. Installments can be further customized by specifying a minimum installment amount as well as total number of installments. If you'd like to offer your customers these functionalities, feel free to contact me at https://www.linkedin.com/in/gregorzorc/ or send me an e-mail at g*****.z***@hotmail.com (replace asterisks as per my actual name/surname).

== Screenshots ==
1. Bankart Payment Gateway settings
2. Bankart Payment Gateway checkout

== Installation ==
1. Make sure you're running WooCommerce 3.0+.
2. Upload the entire `woo-3d-secure-bankart-payment-gateway` folder to the `/wp-content/plugins/` directory
3. Upload your resource.cgn files to `/cgn/testing` and `/cgn/production` subdirectories
4. Make sure a) both cgn (testing and production) subdirectories are writable and b) both resource.cgn files are readable by your app server user (typically `apache`)
5. Activate the plugin through the 'Plugins' menu in WordPress
6. Go to **WooCommerce &gt; Settings &gt; Checkout** and select 'Bankart 3D Secure (Visa, Mastercard)'
7. Change currency and Bankart's HPP language if necessary
8. Choose the correct environment (testing or production)
9. Select the transaction type you desire, typically 'Authorization' for Visa and Mastercard.

== Changelog ==
= 2019-OCT-04 =
 * Tested compatibility with WP 4.7
 * Added uninstall script
 * Updated readme.txt
= 2018-AUG-28 =
 * Plugin is now compatible with WooCommerce 3.X
= 2018-MAY-17 =
 * Fixed changing HTTP to HTTPS protocol
= 2017-JUL-14 - version 1.0 =
 * Initial Release
