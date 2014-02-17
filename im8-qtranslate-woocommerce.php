<?php
/**
 * Plugin Name: IM8 qTranslate WooCommerce
 * Plugin URI: http://wordpress.org/plugins/im8-qtranslate-woocommerce/
 * Description: Front-end integration of qTranslate into WooCommerce.
 * Version: 1.1
 * Author: intermedi8
 * Author URI: http://intermedi8.de
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 */


// Exit on direct access
if (! defined('ABSPATH'))
	exit;


if (! class_exists('IM8qTranslateWooCommerce')) :


/**
 * Main (and only) class.
 */
class IM8qTranslateWooCommerce {

	/**
	 * Plugin instance.
	 *
	 * @type	object
	 */
	protected static $instance = null;


	/**
	 * Plugin version.
	 *
	 * @type	string
	 */
	protected $version = '1.1';


	/**
	 * basename() of global $pagenow.
	 *
	 * @type	string
	 */
	protected static $page_base;


	/**
	 * Plugin option name.
	 *
	 * @type	string
	 */
	protected $option_name = 'im8_qtranslate_woocommerce';


	/**
	 * Plugin repository.
	 *
	 * @type	string
	 */
	protected $repository = 'im8-qtranslate-woocommerce';


	/**
	 * Constructor. Register activation routine.
	 *
	 * @see		get_instance()
	 * @return	void
	 */
	public function __construct() {
		register_activation_hook(__FILE__, array(__CLASS__, 'activation'));
	} // function __construct


	/**
	 * Get plugin instance.
	 *
	 * @hook	plugins_loaded
	 * @return	object IM8qTranslateWooCommerce
	 */
	public static function get_instance() {
		if (null === self::$instance)
			self::$instance = new self;

		return self::$instance;
	} // function get_instance


	/**
	 * Register uninstall routine.
	 *
	 * @hook	activation
	 * @return	void
	 */
	public static function activation() {
		register_uninstall_hook(__FILE__, array(__CLASS__, 'uninstall'));
	} // function activation


	/**
	 * Check if the plugin has to be initialized.
	 *
	 * @hook	plugins_loaded
	 * @return	boolean
	 */
	public static function init_on_demand() {
		global $pagenow;

		if (empty($pagenow))
			return;

		$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
		if (
			! in_array('woocommerce/woocommerce.php', $active_plugins)
			|| ! in_array('qtranslate/qtranslate.php', $active_plugins)
		)
			return;

		self::$page_base = basename($pagenow, '.php');

		$admin_pages = array(
			'admin-ajax',
			'plugins',
			'update-core',
		);

		if (! is_admin() || in_array(self::$page_base, $admin_pages))
			add_action('wp_loaded', array(self::$instance, 'init'));
	} // function init_on_demand


	/**
	 * Register plugin actions and filters.
	 *
	 * @hook	wp_loaded
	 * @return	void
	 */
	public function init() {
		if (is_admin()) {
			add_action('admin_init', array($this, 'autoupdate'));

			if ('plugins' === self::$page_base)
				add_action('in_plugin_update_message-'.basename(dirname(__FILE__)).'/'.basename(__FILE__), array($this, 'update_message'), 10, 2);
		}

		$this->add_filters();
	} // function init


	/**
	 * Check for and perform necessary updates.
	 *
	 * @hook	admin_init
	 * @return	void
	 */
	public function autoupdate() {
		$options = $this->get_option();
		$update_successful = true;

		if (version_compare($options['version'], '1.0', '<')) {
			$new_options = array();
			$new_options['version'] = '1.0';

			if (update_option($this->option_name, $new_options))
				$options = $new_options;

			unset($new_options);
		}

		if ($update_successful) {
			$options['version'] = $this->version;
			update_option($this->option_name, $options);
		}
	} // function autoupdate


	/**
	 * Wrapper for get_option().
	 *
	 * @param	string $key Option name.
	 * @param	mixed $default Return value for missing key.
	 * @return	mixed|$default Option value.
	 */
	protected function get_option($key = null, $default = false) {
		static $option = null;
		if (null === $option) {
			$option = get_option($this->option_name, false);
			if (false === $option)
				$option = array(
					'version' => 0,
				);
		}

		if (null === $key)
			return $option;

		if (! isset($option[$key]))
			return $default;

		return $option[$key];
	} // function get_option


	/**
	 * Print update message based on current plugin version's readme file.
	 *
	 * @hook	in_plugin_update_message-{$file}
	 * @param	array $plugin_data Plugin metadata.
	 * @param	array $r Metadata about the available plugin update.
	 * @return	void
	 */
	public function update_message($plugin_data, $r) {
		if ($plugin_data['update']) {
			$readme = wp_remote_fopen('http://plugins.svn.wordpress.org/'.$this->repository.'/trunk/readme.txt');
			if (! $readme)
				return;

			$pattern = '/==\s*Changelog\s*==(.*)=\s*'.preg_quote($this->version).'\s*=/s';
			if (
				false === preg_match($pattern, $readme, $matches)
				|| ! isset($matches[1])
			)
				return;

			$changelog = (array) preg_split('/[\r\n]+/', trim($matches[1]));
			if (empty($changelog))
				return;

			$output = '<div style="margin: 8px 0 0 26px;">';
			$output .= '<ul style="margin-left: 14px; line-height: 1.5; list-style: disc outside none;">';

			$item_pattern = '/^\s*\*\s*/';
			foreach ($changelog as $line)
				if (preg_match($item_pattern, $line))
					$output .= '<li>'.preg_replace('/`([^`]*)`/', '<code>$1</code>', htmlspecialchars(preg_replace($item_pattern, '', trim($line)))).'</li>';

			$output .= '</ul>';
			$output .= '</div>';

			echo $output;
		}
	} // function update_message


	/**
	 * Add qTranslate filters to WooCommerce.
	 *
	 * @see		init()
	 * @return	void
	 */
	protected function add_filters() {
		if (function_exists('__')) {
			$filters = array(
				'get_term' => 10,
				'the_title_attribute' => 10,
				'woocommerce_attribute' => 10,
				'woocommerce_attribute_label' => 10,
				'woocommerce_cart_tax_totals' => 10,
				'woocommerce_gateway_description' => 10,
				'woocommerce_gateway_title' => 10,
				'woocommerce_in_cart_product_title' => 10,
				'woocommerce_order_tax_totals' => 10,
				'woocommerce_page_title' => 10,
				'woocommerce_order_product_title' => 10,
				'woocommerce_variation_option_name' => 10,
				'wp_get_object_terms' => 10,
				// since WooCommerce 2.1
				'woocommerce_cart_item_name' => 10,
				'woocommerce_order_item_name' => 10,
			);
			$filters = apply_filters('im8qtranslatewoocommerce_gettext_filters', $filters);
			foreach ($filters as $id => $priority)
				add_filter($id, '__', $priority);
		} // if (function_exists('__'))

		if (function_exists('qtrans_convertURL')) {
			$filters = array(
				'post_type_archive_link' => 10,
				'post_type_link' => 10,
				'woocommerce_add_to_cart_url' => 10,
				'woocommerce_product_add_to_cart_url' => 10,
				'woocommerce_breadcrumb_home_url' => 10,
				'woocommerce_checkout_no_payment_needed_redirect' => 10,
				'woocommerce_get_cancel_order_url' => 10,
				'woocommerce_get_checkout_payment_url' => 10,
				'woocommerce_get_checkout_url' => 10,
				'woocommerce_get_return_url' => 10,
			);
			$filters = apply_filters('im8qtranslatewoocommerce_convertURL_filters', $filters);
			foreach ($filters as $id => $priority)
				add_filter($id, 'qtrans_convertURL', $priority);
		} // if (function_exists('qtrans_convertURL'))

		if (function_exists('qtrans_getLanguage')) {
			$filters = array(
				// since WooCommerce 2.1
				'woocommerce_get_endpoint_url' => 10,
			);
			$filters = apply_filters('im8qtranslatewoocommerce_url_filters', $filters);
			foreach ($filters as $id => $priority)
				add_filter($id, array($this, 'add_lang_query_var_to_url'), $priority);

			$filters = array(
				'woocommerce_params' => 10,
				// since WooCommerce 2.1
				'wc_add_to_cart_params' => 10,
				'wc_cart_fragments_params' => 10,
				'wc_cart_params' => 10,
				'wc_checkout_params' => 10,
			);
			$filters = apply_filters('im8qtranslatewoocommerce_woocommerce_params_filters', $filters);
			foreach ($filters as $id => $priority)
				add_filter($id, array($this, 'add_lang_query_var_to_woocommerce_params'), $priority);

			$filters = array(
				'woocommerce_payment_successful_result' => 10,
			);
			$filters = apply_filters('im8qtranslatewoocommerce_payment_redirect_filters', $filters);
			foreach ($filters as $id => $priority)
				add_filter($id, array($this, 'add_lang_query_var_to_payment_redirect_url'), $priority);
		} // if (function_exists('qtrans_getLanguage'))
	} // function add_filters


	/**
	 * Add `lang` query var to given URL.
	 *
	 * @see		add_filters()
	 * @param	string $url URL
	 * @return	string
	 */
	function add_lang_query_var_to_url($url) {
		return add_query_arg('lang', qtrans_getLanguage(), untrailingslashit($url));
	} // function add_lang_query_var_to_url


	/**
	 * Add `lang` query var to specific WooCommerce params URLs.
	 *
	 * @see		add_filters()
	 * @param	array $params WooCommerce params
	 * @return	array
	 */
	function add_lang_query_var_to_woocommerce_params($params) {
		if (! is_array($params))
			return $params;

		$keys = array(
			'ajax_url',
			'checkout_url',
		);
		foreach ($keys as $key)
			if (isset($params[$key]))
				$params[$key] = add_query_arg('lang', qtrans_getLanguage(), $params[$key]);

		return $params;
	} // function add_lang_query_var_to_woocommerce_params


	/**
	 * Add `lang` query var to array element with key 'redirect'.
	 *
	 * @see		add_filters()
	 * @param	array $data Payment data
	 * @return	array
	 */
	function add_lang_query_var_to_payment_redirect_url($data) {
		$key = 'redirect';
		if (isset($data[$key]))
			$data[$key] = add_query_arg('lang', qtrans_getLanguage(), $data[$key]);

		return $data;
	} // function add_lang_query_var_to_payment_redirect_url


	/**
	 * Delete plugin data on uninstall.
	 *
	 * @hook	uninstall
	 * @return	void
	 */
	public static function uninstall() {
		delete_option(self::get_instance()->option_name);
	} // function uninstall

} // class IM8qTranslateWooCommerce


add_action('plugins_loaded', array(IM8qTranslateWooCommerce::get_instance(), 'init_on_demand'));


endif; // if (! class_exists('IM8qTranslateWooCommerce'))