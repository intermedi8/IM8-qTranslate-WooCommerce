=== IM8 qTranslate WooCommerce ===
Contributors: intermedi8
Donate link: http://intermedi8.de
Tags: qtranslate, woocommerce, i18n, l10n, language, multilanguage, multilingual, translation
Requires at least: 3.8.1
Tested up to: 3.8.1
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
* **Automatically adapt new WooCommerce endpoints** (since 2.1.0) and redirects to the current language
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

= 1.2 =
* bugfix: refactored `get_term` and `wp_get_object_terms` filters to get rid of PHP warnings (thanks to _stevenvd_ and _miso00_ for reporting this)

= 1.1 =
* added some missing filters for product/item names (thanks to _Dobbydoo_ for the hint)
* compatible up to WooCommerce 2.1.2

= 1.0 =
* initial release
* compatible up to WordPress 3.8.1
* compatible up to WooCommerce 2.1.0