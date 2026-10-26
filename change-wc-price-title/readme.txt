=== Change Price Title for WooCommerce ===
Contributors: kartikparmar, kartechify
Donate link: https://www.paypal.me/kartikparmar
Author URI: https://woocommerce.com/vendor/kartechify/
Tags: woocommerce price title, change price label, hide price text, rename price, woocommerce price string
Requires at least: 5.3
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily rename, replace, or hide the WooCommerce price (e.g., "$100" → "From: $100 Only") — globally or per product. No coding required.

== Description ==

Stop confusing your customers with default WooCommerce price labels like **"Price:"** or **"From:"**.

Take full control of how prices are presented across your store. **Change Price Title for WooCommerce** lets you replace, rename, or completely hide the price on product pages, shop archives, and all WooCommerce-powered sections — without touching a single line of code.

= Perfect for: =
* ✅ Wholesale & B2B shops (show "Bulk Rate" or "Unit Price")
* ✅ Service-based businesses (display "Starting At" or "Project Fee")
* ✅ Subscription-based stores (change "Price" to "Monthly Fee")
* ✅ Multi-vendor & rental stores (use "Daily Rate" or "Security Deposit")
* ✅ Any store that needs clearer pricing communication

= 🔥 Free Version Features: =
* Replace the WooCommerce price with any custom text
* Use the `PRICE` placeholder to keep the original price inside your text (e.g., "From: PRICE Only")
* Hide the price completely
* Set it once for all products, then override it per product
* Apply changes on the product page only, or on all WooCommerce pages (shop, categories, related products)
* Show the total for the selected quantity on the product page (e.g., "$10 x 3 = $30")

= 🚀 Upgrade to Pro for Advanced Control: =
👉 **[Change Price Title Pro for WooCommerce](https://woocommerce.com/products/change-price-title-pro/)** — Unlock premium features to scale your pricing strategy.

**Pro Version Adds:**
* ✔ **Conditional logic** based on user roles (show different price titles for wholesalers, retail, logged-in users)
* ✔ **Price-based rules** — change titles based on price ranges (e.g., "Budget" for under $50, "Premium" for $200+)
* ✔ **Prefix & suffix support** — add text before or after the price title
* ✔ **Product type targeting** — apply titles to simple, variable, subscription, or grouped products
* ✔ **Priority support & lifetime updates**

Whether you sell **subscriptions**, **wholesale lots**, **rentals**, or **tiered pricing products** — the Pro version gives you enterprise-level flexibility without developer costs.

== Installation ==

= Method 1: From WordPress Dashboard =
1. Go to **Plugins → Add New**
2. Search for "Change Price Title for WooCommerce"
3. Click **Install Now**, then **Activate**

= Method 2: Manual Upload =
1. Download `change-wc-price-title.zip` from WordPress.org
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file and click **Install Now**
4. After installation, click **Activate Plugin**

= Configuration =
* Global settings: **WooCommerce → Settings → Price Title**
* Per product settings: edit a product, then open **Product data → Advanced**

== Frequently Asked Questions ==

= Does this work with any WordPress theme? =
Yes — it works with all WooCommerce-compatible themes including Storefront, Flatsome, Astra, GeneratePress, Divi, and custom themes.

= Can I change the price title on only one product? =
Yes. Edit the product and set the title under **Product data → Advanced**. A product level title overrides the global one.

= Does hiding the price stop customers from buying the product? =
No. Hiding the price only removes the price display. Customers can still add the product to the cart and see the price in the cart and at checkout.

= Will this affect variable products? =
Yes — both free and Pro versions support simple, variable, grouped, and external products. Pro adds support for subscription and booking product types.

= Is it compatible with currency switchers? =
Absolutely — works with WooCommerce Currency Switcher, Aelia, and multi-currency plugins.

= Can I show different price titles for logged-in vs guest users? =
Yes — that’s a Pro feature using user role conditions.

== Screenshots ==

1. Product Level Settings — override price title per product
2. Frontend Product Page Appearance
3. Global Level Settings — change price title across your entire store

== Changelog ==

= 3.1 =
* Fix: Migration notice was shown to new installs instead of existing users
* Fix: Per product settings were not applied to variation prices
* Fix: Price disappeared when the global price title had never been saved
* Fix: Quantity multiplier changed every price on the page and could reveal hidden prices
* Fix: Quantity multiplier now uses the store's currency position, decimals and separators
* Fix: Uninstall now removes all plugin data, including on multisite
* Fix: Product block editor is no longer declared as compatible
* Dev: Added cwpt_price_title_html filter
* Dev: Code cleanup and WooCommerce CRUD for product settings
* Dev: Added translations for 19 locales and load them from i18n/languages

= 3.0 =
* Tested with WordPress 7.1.3 and WooCommerce 11.2.1

= 2.9 =
* Tested with WordPress 6.9.4 and WooCommerce 10.6.2

= 2.8 =
* Settings moved to WooCommerce → Settings → Price Title
* Code changes according to WooCommerce Marketplace

= 2.7 =
* Tested with WordPress 6.8.2 and WooCommerce 10.1.2

= 2.6 =
* Tested with WordPress 6.6.2 and WooCommerce 9.3.3
* Code improvements and PHPCS compliance updates

= 2.5 =
* Fix: JavaScript file URL issue
* Updated translation (.pot) files
* Tested with WordPress 6.5 and WooCommerce 8.7.0

= 2.4 =
* Fix: Resolved a potential Cross-Site Scripting (XSS) vulnerability

= 2.3 =
* Fix: Incompatibility notice was incorrectly displayed on WooCommerce’s Features page

= 2.2 =
* Added support for WooCommerce High-Performance Order Storage (HPOS)
* Tested with WordPress 6.2 and WooCommerce 7.8

The full changelog is available in `changelog.txt`.

== Upgrade Notice ==

= 3.1 =
Fixes hidden prices being shown by the quantity multiplier and per product settings on variable products. Recommended update for all users.

= 2.8 =
Plugin settings have moved to WooCommerce → Settings → Price Title.

= 2.4 =
Security fix. Recommended update for all users.

== Upgrade to Pro ==

Need more than per product and global price titles?
👉 **[Get Change Price Title Pro for WooCommerce](https://woocommerce.com/products/change-price-title-pro/)** — Starting at just $39 (one-time payment, 1 year updates & support).

**Pro users can:**
* Target by user role (Wholesaler, Retail, Guest, Admin)
* Add prefixes/suffixes
* Apply rules based on price range or product type
* Get priority email support

[Buy Pro Now on WooCommerce.com →](https://woocommerce.com/products/change-price-title-pro/)

== Support ==

* **Free version support:** WordPress.org forums
* **Pro version support:** Direct via WooCommerce.com with 24 hour response time

== Credits ==

Developed by [Kartechify](https://woocommerce.com/vendor/kartechify/)
