=== IM8 qTranslate WooCommerce ===
Contributors: ipm-frommen, intermedi8
Donate link: http://intermedi8.de
Tags: qtranslate, woocommerce, i18n, l10n, language, multilanguage, multilingual, translation
Requires at least: 3.8.1
Tested up to: 3.9
Stable tag: trunk
License: MIT
License URI: http://opensource.org/licenses/MIT

Front-end integration of qTranslate into WooCommerce.

== Description ==

**Front-end integration of <a href="http://wordpress.org/plugins/qtranslate/" target="_blank">qTranslate</a> into <a href="http://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>.**

* Shortcode translation for taxes, payment gateways, shipping methods, etc.
* Shortcode translation for product category names and descriptions
* Shortcode translation for product attribute names and terms
* Automatically adapt breadcrumbs to the current language
* Automatically keep the current language when doing AJAX requests
* Automatically send emails in the current language
* Automatically adapt WooCommerce endpoints and redirects to the current language
* Ad-free (of course, donations are welcome)

If you would like to **contribute** to this plugin, see its <a href="https://github.com/intermedi8/im8-qtranslate-woocommerce" target="_blank">**GitHub repository**</a>.

== Installation ==

1. Upload the `im8-qtranslate-woocommerce` folder to the `/wp-content/plugins` directory on your web server.
2. Activate the plugin through the _Plugins_ menu in WordPress.
3. Go ahead and use shortcodes like `[:en]Product[:de]Produkt[:es]Producto` for almost anything that is visible for your customers (i.e., visible on the front-end).

== Screenshots ==

1. **WooCommerce Attributes page** - Simply use shortcodes for both attribute names and attribute terms.
2. **Front-end example** - Direct comparison of how a single product looks like in English and German.

== Changelog ==

= 1.5.4 =
* reset plugin init priority from 0 to default (10)
* rewrite conditional in `init_on_demand` function to make plugin work with qTranslate forks
* compatible up to WordPress 3.9
* compatible up to WooCommerce 2.1.7

= 1.5.3 =
* added `site_url` filter for comment form to keep the current language when submitting a product review
* added filters for cart and checkout buttons in cart widget
* removed deprecated `woocommerce_in_cart_product_title` filter
* compatible up to WordPress 3.8.2

= 1.5.2 =
* added filters for _Email "From" Name_ and _Email Footer Text_
* added filter for `get_terms` calls to fix some widgets, amongst other things (thanks to _arejay_ for the hint)

= 1.5.1 =
* fixed fatal error for PHP version older than 5.3 (thanks to _3Lancer_ for reporting the error as well as providing the bugfix)

= 1.5 =
* split plugin into several files
* removed static functions and properties (except for uninstall, of course)
* introduced filters `im8qw_default_admin_language` and `im8qw_use_default_admin_language` in order to customize or deactivate the default admin language
* code reformat
* compatible up to WooCommerce 2.1.6

= 1.4.3 =
* bugfixed last version, again (third time's a charm)

= 1.4.2 =
* bugfixed/improved last version (`set_transient` function is now a lot more restricted to WooCommerce)

= 1.4.1 =
* the plugin now does not affect the admin language anymore (thanks to _WISTFUL_, again, for reporting this)

= 1.4 =
* added filters for shipping method label (thanks to _WISTFUL_ for reporting this)
* compatible up to qTranslate 2.5.39
* compatible up to WooCommerce 2.1.5

= 1.3 =
* added filters for shipping and subtotal display
* added filters for gateway texts (also used for emails)
* adapted filters for tax texts
* compatible up to WooCommerce 2.1.3

= 1.2 =
* bugfix: refactored `get_term` and `wp_get_object_terms` filters to get rid of PHP warnings (thanks to _stevenvd_ and _miso00_ for reporting this)

= 1.1 =
* added some missing filters for product/item names (thanks to _Dobbydoo_ for the hint)
* compatible up to WooCommerce 2.1.2

= 1.0 =
* initial release
* compatible up to WordPress 3.8.1
* compatible up to WooCommerce 2.1.0
