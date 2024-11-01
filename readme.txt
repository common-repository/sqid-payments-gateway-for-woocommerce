=== SQID Payments Gateway for Woocommerce ===
Contributors: SQID Payments
Donate link:
Tags:  australia,  cart, checkout, commerce, credit card, e-commerce, ecommerce, sqid payments, payment gateway, woocommerce, westpac, SMS payments, Direct Debits
Requires at least: 4.5.0
Tested up to: 5.4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The SQID Payments Gateway for Woocommerce allows Australian Merchants to accept payments on your Woocommerce store for Visa Card and Mastercard.

== Description ==

[SQID Payments](https://sqidpayments.com.au) offers one of the fastest and easiest way to __accept payments online in Australia__. SQID can create the merchant account for you and __settles funds to any Australian Bank__ and you can __use your existing business account__ because SQID Payments is a payment aggregator using Westpac Bank to connect to the Australian banking network.

The innovative payment solution enables online businesses to quickly integrate payments into their websites. The developer-friendly REST API is flexible and well documented. A SQID Merchant Account also includes access to a __Virtual POS terminal using Payment Manager for mobile and desktop based payments__. Access to SMS and email based marketing tools that allow you to send direct payment links for deals, offers and bill payments are just part of the normal merchant account.  All major card brands like MasterCard, VISA, American Express, are supported. 

__We strongly recommend you read the [Installation](https://wordpress.org/plugins/sqid-payments-gateway-for-woocommerce/installation/) details.__

[Merchant pricing](https://sqidpayments.com.au/pricing/) includes plans with no monthly fees.  [Apply to become a merchant here](https://sqidpayments.com.au/merchant_application/) and typical approval is in about 24 hours. If you are an eCommerce developer [Apply here to become a Referral Partner](https://sqidpayments.com.au/referral-program/) and enquire about commissions.

= Features =

* Uses the WooCommerce built in checkout so the customer never leaves your website
* Uses securely stored tokenisation off your site to allow recurring payments.

Note: You must have an SSL Certificate installed on your site to ensure your customer’s credit card details are safe.

= Supported Shops: =

* WooCommerce (2.4.x) tested to 4.1.0
* PHP 5.3 or higher
* WordPress 4.5.x or higher

= Supported plugins: =

* WooCommerce Subscriptions (1.5.x) …tested to 1.5.14	
* WooCommerce Direct Checkout …tested to 2.3.5

= Installation =

__Minimum Requirements__

* WooCommerce 2.1.0 or later - recommended 2.4 or later (Tested to 4.1.0)

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “SQID Payments Woocommerce Gateway” and click Search Plugins. Once you've found the plugin you can install it by simply clicking “Install Now”.

__IMPORTANT - If your test transaction produces a page not found error then add a page called Response and onto that page add the shortcode [response]__

__Manual installation__

Manual installation method involves downloading our the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).
__If your test transaction produces a page not found error then add a page called Response and onto that page add the shortcode [response]__

= What next? =

* Activate the plugin through the 'Plugins' menu in WordPress.
* Register for a merchant account on [SQID Payments](https://sqidpayments.com.au)
* Go to __Woocommerce > Settings>Checkout__ and at the bottom of the page drag SQID to the top of the gateways list and check __default__	
* Insert Api Keys and credentials and then enable test mode in plugin settings/checkout page that were sent to you.
* Run some test transactions and log into your [Staging Payment Manager](https://pm.staging.sqidpay.com) to see that they are all correct.  Test cards are available here https://sqidpay.atlassian.net/wiki/display/SRP/Test+Credit+Cards  __REMEMBER:__ test cards in the test environment and live cards in the live environment.
* If you are happy how the plugin works, then enable your live account by deselecting the __Enable test mode__ and your cart is __live instantly__.
* We suggest you run some live transactions and log into your [Payment Manager](https://pm.sqidpay.com) to see that they are all correct.

You'll also need to force SSL on checkout in the WooCommerce settings and of course have an SSL certificate to ensure your customer’s credit card details are safe.

= Team =

* Officially supported by [SQID Payments](https://sqidpayments.com.au)
* Developed and maintained by [SQID Payments](https://sqidpayments.com.au)
* API for card payments available at [SQID Payments/developers](https://sqidpayments.com.au/developers/)
* API for Direct Debits contact SQID at [SQID Payments/contact](https://sqidpayments.com.au/contact)
* Two Factor authenticated payments via API or licensed from SQID contact us at [SQID Payments/contact](https://sqidpayments.com.au/contact)
* SMS initiated payments via API or licensed from SQID contact us at [SQID Payments/contact](https://sqidpayments.com.au/contact)
* Direct Debit payments via API from SQID contact us at [SQID Payments/contact](https://sqidpayments.com.au/contact)


== Frequently Asked Questions ==

= Who’s is the acquiring bank? =

SQID Payments is a merchant aggregator for ecommerce and payment terminals with Westpac Bank in Australia. This DOES NOT mean you need a Westpac account and we will settle to any Australian business bank account.

= Do I need and SSL certificate? =

__Yes you need an SSL Certificate__ to be installed on your site to ensure your customer’s credit card details are safe.

= Is this plugin for free? =

This plugin is for free and licensed to GPL.
It's open source following the GPL policy.

= Is there also an API available? =

Yes, __there is an API for card payments and direct debits__ and details of the latest release are here for [Developers](https://sqidpayments.com.au/developers/)

= Are there any fees for payments? =

Merchants must create an account at [SQID Payments](https://sqidpayments.com.au) to use the payment service.
The TEST mode is for free, but there are "per transaction” fees in LIVE mode, see [SQID Pricing](https://sqidpayments.com.au/pricing/)

= Do customers need to create an account for payment? =

No. SQID allows payments without annoying your customers creating an account.
They'll just fill out the payment fields on your checkout-page - that's all.  Unless you set Woocommerce to require them to create an account, it’s your option.

= Does this plugin redirects the users to SQID for payment? =

No. SQID allows payment directly through your website without any extra redirects etc.

= Which Credit Cards are supported? =

In Australia we support VISA, MasterCard and American Express.

= I get a 404 error when I submit test card data? =

Be sure that IF your test transaction produces a page not found error then add a page called Response and onto that page add the shortcode [response].

== Screenshots ==

1. WooCommerce checkout page settings page

2. SQID Payments Gateway Checkout Setting Page

3. How to create a transactions results page called Response and insert the [response] shortcode.


== Changelog ==

= 1.1.1 =
* Automatically creates payment response page on activation.
* Improved token handling
* Updated readme.txt 

= 1.1.0 =
* Changed version numbering
* Updated readme.txt 

= 1.0.8 =
* Updated API fields
* Updated readme.txt to include Payment Manager
* Minor additions to readme.txt
* Minor fixes

= 1.0.7 =
* Updated form validation
* Minor fixes
* Updated images

= 1.0.6 =
* Update for Woocommerce 2.5.4
* Improved error message presentation
* Reduced unnecessary table creation and error logging
* Edited API interaction for countries without state/county
* edited receipt content that was confusing
* Minor enhancements

= 1.0.5 =
* Update for WP 4.4.2 and Woocommerce 2.5.3
* Minor bug fixes

= 1.0.4 =
* Update for WP 4.2.4 
* Improved validation on checkout fields
* Minor bug fixes

= 1.0.3 =
* Update for WP 4.1.1 and some receipt format changes

= 1.0.2 =
* Various minor tweaks and speed improvements
* Minor bug fixes and revised receipt format

= 1.0.1 =
* Minor edits and improvements
* Added tokenisation for WooCommerce Subscriptions 1.5.x

= 1.0 =
* Initial release

== Upgrade Notice ==

= 0.9 =
* Beta release
