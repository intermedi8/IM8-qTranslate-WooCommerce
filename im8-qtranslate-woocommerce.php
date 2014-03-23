<?php # -*- coding: utf-8 -*-
/**
 * Plugin Name: IM8 qTranslate WooCommerce
 * Plugin URI: http://wordpress.org/plugins/im8-qtranslate-woocommerce/
 * Description: Front-end integration of qTranslate into WooCommerce.
 * Version: 1.4.3
 * Author: ipm-frommen, intermedi8
 * Author URI: http://intermedi8.de
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 */


// Exit on direct access
if ( ! defined( 'ABSPATH' ) )
	exit;


if ( ! class_exists( 'IM8qTranslateWooCommerce' ) ) :


/**
 * Main (and only) class.
 */
class IM8qTranslateWooCommerce {

	/**
	 * Plugin instance.
	 *
	 * @type	object
	 */
	protected static $instance = NULL;


	/**
	 * Plugin version.
	 *
	 * @type	string
	 */
	protected $version = '1.4.3';


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
	 * Plugin transient name.
	 *
	 * @type	string
	 */
	protected static $transient_name = 'im8qw_custom_admin_language';


	/**
	 * Constructor. Register activation routine.
	 *
	 * @see		get_instance()
	 * @return	void
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
	} // function __construct


	/**
	 * Get plugin instance.
	 *
	 * @hook	plugins_loaded
	 * @return	object IM8qTranslateWooCommerce
	 */
	public static function get_instance() {
		if ( NULL === self::$instance )
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
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	} // function activation


	/**
	 * Check if the plugin has to be initialized.
	 *
	 * @hook	plugins_loaded
	 * @return	boolean
	 */
	public static function init_on_demand() {
		global $pagenow;

		if ( empty( $pagenow ) )
			return;

		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		if (
			! in_array( 'woocommerce/woocommerce.php', $active_plugins )
			|| ! in_array( 'qtranslate/qtranslate.php', $active_plugins )
		)
			return;

		self::$page_base = basename( $pagenow, '.php' );

		$admin_pages = array(
			'admin-ajax',
			'plugins',
			'update-core',
		);

		if (
			is_admin()
			&& get_transient( self::$transient_name )
		) {
			$_COOKIE[ 'qtrans_admin_language' ] = get_option( 'qtrans_default_language' );
			delete_transient( self::$transient_name );
		}

		 if ( ! is_admin() || in_array( self::$page_base, $admin_pages ) )
			add_action( 'wp_loaded', array( self::$instance, 'init' ) );
	} // function init_on_demand


	/**
	 * Register plugin actions and filters.
	 *
	 * @hook	wp_loaded
	 * @return	void
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'autoupdate' ) );

			if ( 'plugins' === self::$page_base )
				add_action( 'in_plugin_update_message-'.basename( dirname( __FILE__ ) ).'/'.basename( __FILE__ ), array( $this, 'update_message' ), 10, 2 );

			if (
				'admin-ajax' === self::$page_base
				&& $this->is_ajax_woocommerce()
			)
				add_action( 'shutdown', array( $this, 'set_transient' ) );
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
		$update_successful = TRUE;

		if ( version_compare( $options[ 'version' ], '1.0', '<' ) ) {
			$new_options = array();
			$new_options[ 'version' ] = '1.0';

			if ( update_option( $this->option_name, $new_options ) )
				$options = $new_options;

			unset( $new_options );
		}

		if ( $update_successful ) {
			$options[ 'version' ] = $this->version;
			update_option( $this->option_name, $options );
		}
	} // function autoupdate


	/**
	 * Wrapper for get_option().
	 *
	 * @param	string $key Option name
	 * @param	mixed $default Return value for missing key
	 * @return	mixed|$default Option value
	 */
	protected function get_option( $key = NULL, $default = FALSE ) {
		static $option = NULL;
		if ( NULL === $option ) {
			$option = get_option( $this->option_name, FALSE );
			if ( FALSE === $option )
				$option = array(
					'version' => 0,
				);
		}

		if ( NULL === $key )
			return $option;

		if ( ! isset( $option[ $key ] ) )
			return $default;

		return $option[ $key ];
	} // function get_option


	/**
	 * Print update message based on current plugin version's readme file.
	 *
	 * @hook	in_plugin_update_message-{$file}
	 * @param	array $plugin_data Plugin metadata
	 * @param	array $r Metadata about the available plugin update
	 * @return	void
	 */
	public function update_message( $plugin_data, $r ) {
		if ( $plugin_data[ 'update' ] ) {
			$readme = wp_remote_fopen( 'http://plugins.svn.wordpress.org/'.$this->repository.'/trunk/readme.txt' );
			if ( ! $readme )
				return;

			$pattern = '/==\s*Changelog\s*==( .* )=\s*'.preg_quote( $this->version ).'\s*=/s';
			if (
				FALSE === preg_match( $pattern, $readme, $matches )
				|| ! isset( $matches[ 1 ] )
			)
				return;

			$changelog = ( array ) preg_split( '/[\r\n]+/', trim( $matches[ 1 ] ) );
			if ( empty( $changelog ) )
				return;

			$output = '<div style="margin: 8px 0 0 26px;">';
			$output .= '<ul style="margin-left: 14px; line-height: 1.5; list-style: disc outside none;">';

			$item_pattern = '/^\s*\*\s*/';
			foreach ( $changelog as $line )
				if ( preg_match( $item_pattern, $line ) )
					$output .= '<li>'.preg_replace( '/`( [^`]* )`/', '<code>$1</code>', htmlspecialchars( preg_replace( $item_pattern, '', trim( $line ) ) ) ).'</li>';

			$output .= '</ul>';
			$output .= '</div>';

			echo $output;
		}
	} // function update_message


	/**
	 * Check if this is a WooCommerce AJAX request
	 *
	 * @see		init()
	 * @return	bool
	 */
	private function is_ajax_woocommerce() {
		if ( ! isset( $_REQUEST[ 'action' ] ) )
			return FALSE;

		return in_array( substr( $_REQUEST[ 'action' ], 12 ), array(
			'add_new_attribute',
			'add_order_fee',
			'add_order_item',
			'add_order_item_meta',
			'add_order_note',
			'add_to_cart',
			'add_variation',
			'apply_coupon',
			'calc_line_taxes',
			'checkout',
			'delete_order_note',
			'feature_product',
			'get_customer_details',
			'get_refreshed_fragments',
			'grant_access_to_download',
			'increase_order_item_stock',
			'json_search_customers',
			'json_search_downloadable_products_and_variations',
			'json_search_products',
			'json_search_products_and_variations',
			'link_all_variations',
			'mark_order_complete',
			'mark_order_processing',
			'product_ordering',
			'reduce_order_item_stock',
			'remove_order_item',
			'remove_order_item_meta',
			'remove_variation',
			'remove_variations',
			'revoke_access_to_download',
			'save_attributes',
			'term_ordering' ,
			'update_order_review',
			'update_shipping_method',
		) );
	} // function is_ajax_woocommerce


	/**
	 * Sets a transient hook in order to switch back to the default admin language on the next admin page call.
	 *
	 * @wp-hook	shutdown
	 */
	public function set_transient() {
		set_transient( 'im8qw_custom_admin_language', TRUE, 60 * 10 );
	} // function set_transient


	/**
	 * Add qTranslate filters to WooCommerce.
	 *
	 * @see		init()
	 * @return	void
	 */
	private function add_filters() {
		if ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$filters = array(
				'the_title_attribute' => 10,
				'woocommerce_attribute' => 10,
				'woocommerce_attribute_label' => 10,
				'woocommerce_cart_item_name' => 10,
				'woocommerce_cart_tax_totals' => 10,
				'woocommerce_gateway_description' => 10,
				'woocommerce_gateway_title' => 10,
				'woocommerce_in_cart_product_title' => 10,
				'woocommerce_page_title' => 10,
				'woocommerce_order_item_name' => 10,
				'woocommerce_order_product_title' => 10,
				'woocommerce_order_shipping_to_display' => 10,
				'woocommerce_order_subtotal_to_display' => 10,
				'woocommerce_cart_shipping_method_full_label' => 10,
				'woocommerce_variation_option_name' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_string_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );

			$filters = array(
				'get_term' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_term_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'translate_term' ), $priority );

			$filters = array(
				'wp_get_object_terms' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_terms_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'translate_terms' ), $priority );

			$filters = array(
				'woocommerce_order_tax_totals' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_tax_totals_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'translate_tax_totals' ), $priority );

			$filters = array(
				'option_woocommerce_bacs_settings' => 10,
				'option_woocommerce_cheque_settings' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_gateway_settings_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'translate_gateway_settings' ), $priority );
		} // if ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) )

		if ( function_exists( 'qtrans_convertURL' ) ) {
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
			$filters = apply_filters( 'im8qtranslatewoocommerce_convertURL_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, 'qtrans_convertURL', $priority );
		} // if ( function_exists( 'qtrans_convertURL' ) )

		if ( function_exists( 'qtrans_getLanguage' ) ) {
			$filters = array(
				'woocommerce_get_endpoint_url' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_url_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'add_lang_query_var_to_url' ), $priority );

			$filters = array(
				'wc_add_to_cart_params' => 10,
				'wc_cart_fragments_params' => 10,
				'wc_cart_params' => 10,
				'wc_checkout_params' => 10,
				'woocommerce_params' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_woocommerce_params_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'add_lang_query_var_to_woocommerce_params' ), $priority );

			$filters = array(
				'woocommerce_payment_successful_result' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_payment_redirect_filters', $filters );
			foreach ( $filters as $id => $priority )
				add_filter( $id, array( $this, 'add_lang_query_var_to_payment_redirect_url' ), $priority );
		} // if ( function_exists( 'qtrans_getLanguage' ) )
	} // function add_filters


	/**
	 * Translate term name into current ( or default ) language.
	 *
	 * @see		add_filters()
	 * @param	object $term Term object
	 * @return	object
	 */
	function translate_term( $term ) {
		if (
			isset( $term )
			&& isset( $term->name )
		)
			$term->name = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $term->name );

		return $term;
	} // function translate_term


	/**
	 * Translate term names into current ( or default ) language.
	 *
	 * @see		add_filters()
	 * @param	array $terms Term objects
	 * @return	array
	 */
	function translate_terms( $terms ) {
		if ( is_array( $terms ) && count( $terms ) )
			foreach ( $terms as $key => $term )
				$terms[ $key ] = $this->translate_term( $term );

		return $terms;
	} // function translate_terms


	/**
	 * Translate tax labels into current ( or default ) language.
	 *
	 * @see		add_filters()
	 * @param	array $tax Tax totals array
	 * @return	array
	 */
	function translate_tax_totals( $tax_totals ) {
		foreach ( $tax_totals as $key => $tax_total )
			if ( isset( $tax_total->label ) )
				$tax_totals[ $key ]->label = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $tax_total->label );

		return $tax_totals;
	} // function translate_taxt_totals


	/**
	 * Translate gateway settings into current ( or default ) language.
	 *
	 * @see		add_filters()
	 * @param	array $settings Settings array
	 * @return	array
	 */
	function translate_gateway_settings( $settings ) {
		if ( is_array( $settings ) )
			foreach ( array(
				'title',
				'description',
				'instructions',
			) as $key )
				if ( isset( $settings[ $key ] ) )
					$settings[ $key ] = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $settings[ $key ] );

		return $settings;
	} // function translate_gateway_settings


	/**
	 * Add `lang` query var to given URL.
	 *
	 * @see		add_filters()
	 * @param	string $url URL
	 * @return	string
	 */
	function add_lang_query_var_to_url( $url ) {
		return add_query_arg( 'lang', qtrans_getLanguage(), untrailingslashit( $url ) );
	} // function add_lang_query_var_to_url


	/**
	 * Add `lang` query var to specific WooCommerce params URLs.
	 *
	 * @see		add_filters()
	 * @param	array $params WooCommerce params
	 * @return	array
	 */
	function add_lang_query_var_to_woocommerce_params( $params ) {
		if ( ! is_array( $params ) )
			return $params;

		$keys = array(
			'ajax_url',
			'checkout_url',
		);
		foreach ( $keys as $key )
			if ( isset( $params[ $key ] ) )
				$params[ $key ] = add_query_arg( 'lang', qtrans_getLanguage(), $params[ $key ] );

		return $params;
	} // function add_lang_query_var_to_woocommerce_params


	/**
	 * Add `lang` query var to array element with key 'redirect'.
	 *
	 * @see		add_filters()
	 * @param	array $data Payment data
	 * @return	array
	 */
	function add_lang_query_var_to_payment_redirect_url( $data ) {
		$key = 'redirect';
		if ( isset( $data[ $key ] ) )
			$data[ $key ] = add_query_arg( 'lang', qtrans_getLanguage(), $data[ $key ] );

		return $data;
	} // function add_lang_query_var_to_payment_redirect_url


	/**
	 * Delete plugin data on uninstall.
	 *
	 * @hook	uninstall
	 * @return	void
	 */
	public static function uninstall() {
		delete_option( self::get_instance()->option_name );
	} // function uninstall

} // class IM8qTranslateWooCommerce


add_action( 'plugins_loaded', array( IM8qTranslateWooCommerce::get_instance(), 'init_on_demand' ), 0 );


endif; // if ( ! class_exists( 'IM8qTranslateWooCommerce' ) )