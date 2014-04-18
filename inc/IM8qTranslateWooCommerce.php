<?php # -*- coding: utf-8 -*-
/**
 * Class IM8qTranslateWooCommerce
 */
class IM8qTranslateWooCommerce {

	/**
	 * Plugin file
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Plugin option name
	 *
	 * @var string
	 */
	private $option_name = 'im8_qtranslate_woocommerce';

	/**
	 * basename() of global {$pagenow}
	 *
	 * @var string
	 */
	private $page_base;

	/**
	 * Plugin repository
	 *
	 * @var string
	 */
	private $repository = 'im8-qtranslate-woocommerce';

	/**
	 * Plugin transient name
	 *
	 * @var string
	 */
	private $transient_name = 'im8qw_custom_admin_language';

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor: Set up class data and register activation routine
	 *
	 * @param object $data Class data
	 */
	public function __construct( $data ) {

		$this->file = $data->file;

		$headers = array(
			'version' => 'Version',
		);
		$file_data = get_file_data( $data->file, $headers );
		foreach ( $file_data as $key => $value ) {
			$this->{$key} = $value;
		}

		register_activation_hook( $this->file, array( $this, 'activate' ) );
	}

	/**
	 * Delete plugin data on uninstall
	 *
	 * @wp-hook uninstall
	 */
	public static function uninstall() {

		delete_option( 'im8_qtranslate_woocommerce' );
	}

	/**
	 * Register uninstall routine
	 *
	 * @wp-hook activation
	 */
	public function activate() {

		register_uninstall_hook( $this->file, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Maybe register initialization routine
	 *
	 * Check for both WooCommerce and qTranslate.
	 * Handle transient for default admin language.
	 * Set page base.
	 *
	 * @wp-hook plugins_loaded
	 */
	public function init_on_demand() {

		global $pagenow;

		if ( empty( $pagenow ) ) {
			return;
		}

		if (
			! did_action( 'woocommerce_loaded' )
			|| ! defined( 'QTRANS_INIT' )
		) {
			return;
		}

		if (
			is_admin()
			&& get_transient( $this->transient_name )
		) {
			$_COOKIE[ 'qtrans_admin_language' ] = apply_filters(
				'im8qw_default_admin_language', get_option( 'qtrans_default_language' )
			);
			delete_transient( $this->transient_name );
		}

		$this->page_base = basename( $pagenow, '.php' );

		$pages = array(
			'admin-ajax',
			'plugins',
			'update-core',
		);

		if ( ! is_admin() || in_array( $this->page_base, $pages ) ) {
			add_action( 'wp_loaded', array( $this, 'init' ) );
		}
	}

	/**
	 * Register plugin actions and filters
	 *
	 * @wp-hook wp_loaded
	 */
	public function init() {

		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'autoupdate' ) );

			if (
				$this->page_base === 'admin-ajax'
				&& $this->is_ajax_woocommerce()
				&& apply_filters( 'im8qw_use_default_admin_language', TRUE )
			) {
				add_action( 'shutdown', array( $this, 'set_transient' ) );
			}

			if ( $this->page_base === 'plugins' ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

				$file = plugin_basename( $this->file );
				add_action( 'in_plugin_update_message-' . $file, array( $this, 'update_message' ) );
			}
		}

		$this->add_filters();
	}

	/**
	 * Check if this is a WooCommerce AJAX request
	 *
	 * @see init()
	 *
	 * @return bool
	 */
	private function is_ajax_woocommerce() {

		if ( ! isset( $_REQUEST[ 'action' ] ) ) {
			return FALSE;
		}

		$actions = array(
			'woocommerce_add_new_attribute',
			'woocommerce_add_order_fee',
			'woocommerce_add_order_item',
			'woocommerce_add_order_item_meta',
			'woocommerce_add_order_note',
			'woocommerce_add_to_cart',
			'woocommerce_add_variation',
			'woocommerce_apply_coupon',
			'woocommerce_calc_line_taxes',
			'woocommerce_checkout',
			'woocommerce_delete_order_note',
			'woocommerce_feature_product',
			'woocommerce_get_customer_details',
			'woocommerce_get_refreshed_fragments',
			'woocommerce_grant_access_to_download',
			'woocommerce_increase_order_item_stock',
			'woocommerce_json_search_customers',
			'woocommerce_json_search_downloadable_products_and_variations',
			'woocommerce_json_search_products',
			'woocommerce_json_search_products_and_variations',
			'woocommerce_link_all_variations',
			'woocommerce_mark_order_complete',
			'woocommerce_mark_order_processing',
			'woocommerce_product_ordering',
			'woocommerce_reduce_order_item_stock',
			'woocommerce_remove_order_item',
			'woocommerce_remove_order_item_meta',
			'woocommerce_remove_variation',
			'woocommerce_remove_variations',
			'woocommerce_revoke_access_to_download',
			'woocommerce_save_attributes',
			'woocommerce_term_ordering',
			'woocommerce_update_order_review',
			'woocommerce_update_shipping_method',
		);

		return in_array( $_REQUEST[ 'action' ], $actions );
	}

	/**
	 * Add qTranslate filters to WooCommerce functions
	 *
	 * @see init()
	 */
	private function add_filters() {

		if ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$filters = array(
				'option_woocommerce_email_from_name'          => 10,
				'the_title_attribute'                         => 10,
				'woocommerce_attribute'                       => 10,
				'woocommerce_attribute_label'                 => 10,
				'woocommerce_cart_item_name'                  => 10,
				'woocommerce_cart_shipping_method_full_label' => 10,
				'woocommerce_cart_tax_totals'                 => 10,
				'woocommerce_email_footer_text'               => 10,
				'woocommerce_gateway_description'             => 10,
				'woocommerce_gateway_title'                   => 10,
				'woocommerce_page_title'                      => 10,
				'woocommerce_order_item_name'                 => 10,
				'woocommerce_order_product_title'             => 10,
				'woocommerce_order_shipping_to_display'       => 10,
				'woocommerce_order_subtotal_to_display'       => 10,
				'woocommerce_variation_option_name'           => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_string_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
			}

			$filters = array(
				'get_term' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_term_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'translate_term' ), $priority );
			}

			$filters = array(
				'get_terms'           => 10,
				'wp_get_object_terms' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_terms_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'translate_terms' ), $priority );
			}

			$filters = array(
				'woocommerce_order_tax_totals' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_tax_totals_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'translate_tax_totals' ), $priority );
			}

			$filters = array(
				'option_woocommerce_bacs_settings'   => 10,
				'option_woocommerce_cheque_settings' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_translate_gateway_settings_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'translate_gateway_settings' ), $priority );
			}
		}

		if ( function_exists( 'qtrans_convertURL' ) ) {
			$filters = array(
				'post_type_archive_link'                          => 10,
				'post_type_link'                                  => 10,
				'woocommerce_add_to_cart_url'                     => 10,
				'woocommerce_breadcrumb_home_url'                 => 10,
				'woocommerce_checkout_no_payment_needed_redirect' => 10,
				'woocommerce_get_cancel_order_url'                => 10,
				'woocommerce_get_checkout_payment_url'            => 10,
				'woocommerce_get_return_url'                      => 10,
				'woocommerce_product_add_to_cart_url'             => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_convertURL_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, 'qtrans_convertURL', $priority );
			}
		}

		if ( function_exists( 'qtrans_getLanguage' ) ) {
			$filters = array(
				'woocommerce_get_cart_url'     => 10,
				'woocommerce_get_checkout_url' => 10,
				'woocommerce_get_endpoint_url' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_url_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'add_lang_query_var_to_url' ), $priority );
			}

			$filters = array(
				'site_url' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_site_url_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'add_lang_query_var_to_site_url' ), $priority, 2 );
			}

			$filters = array(
				'woocommerce_payment_successful_result' => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_payment_redirect_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'add_lang_query_var_to_payment_redirect_url' ), $priority );
			}

			$filters = array(
				'wc_add_to_cart_params'    => 10,
				'wc_cart_fragments_params' => 10,
				'wc_cart_params'           => 10,
				'wc_checkout_params'       => 10,
				'woocommerce_params'       => 10,
			);
			$filters = apply_filters( 'im8qtranslatewoocommerce_woocommerce_params_filters', $filters );
			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'add_lang_query_var_to_woocommerce_params' ), $priority );
			}
		}
	}

	/**
	 * Check for and perform necessary updates
	 *
	 * @wp-hook admin_init
	 */
	public function autoupdate() {

		$option = $this->get_option();
		$update_successful = TRUE;

		if ( version_compare( $option[ 'version' ], '1.0', '<' ) ) {
			$new_option = array();
			$new_option[ 'version' ] = '1.0';

			if ( update_option( $this->option_name, $new_option ) ) {
				$option = $new_option;
			}

			unset( $new_option );
		}

		if ( $update_successful ) {
			$option[ 'version' ] = $this->version;
			update_option( $this->option_name, $option );
		}
	}

	/**
	 * Wrapper for get_option()
	 *
	 * @param string $key     Option name
	 * @param mixed  $default Default value if option not set for given key
	 *
	 * @return mixed
	 */
	private function get_option( $key = NULL, $default = FALSE ) {

		$option = get_option( $this->option_name, FALSE );
		if ( $option === FALSE ) {
			$option = array(
				'version' => 0,
			);
		}

		if ( $key === NULL ) {
			return $option;
		}

		if ( ! isset( $option[ $key ] ) ) {
			return $default;
		}

		return $option[ $key ];
	}

	/**
	 * Set a transient in order to switch back to the default admin language on the next admin page call
	 *
	 * @wp-hook shutdown
	 */
	public function set_transient() {

		set_transient( 'im8qw_custom_admin_language', TRUE, 60 * 10 );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @wp-hook admin_enqueue_scripts
	 */
	public function enqueue_admin_scripts() {

		$handle = 'im8-update-message';
		if ( ! wp_style_is( $handle ) ) {
			$file = 'assets/css/' . $handle . '.css';
			wp_enqueue_style(
				$handle,
				plugin_dir_url( $this->file ) . $file,
				array(),
				filemtime( plugin_dir_path( $this->file ) . $file )
			);
		}
	}

	/**
	 * Print update message based on current plugin version's readme file
	 *
	 * @wp-hook in_plugin_update_message-{@link $this->file}
	 *
	 * @param array $plugin_data Plugin metadata
	 */
	public function update_message( $plugin_data ) {

		if ( $plugin_data[ 'update' ] ) {

			$readme = wp_remote_fopen(
				'http://plugins.svn.wordpress.org/' . $this->repository . '/trunk/readme.txt'
			);
			if ( ! $readme ) {
				return;
			}

			$pattern = '/==\s*Changelog\s*==(.*)=\s*' . preg_quote( $this->version ) . '\s*=/s';
			if (
				FALSE === preg_match( $pattern, $readme, $matches )
				|| ! isset( $matches[ 1 ] )
			) {
				return;
			}

			$changes = trim( $matches[ 1 ] );
			$changelog = preg_split( '/[\r\n]+/', $changes );
			if ( empty( $changelog ) ) {
				return;
			}

			$output = '<div class="im8-update-message">';
			$output .= '<ul>';

			$item_pattern = '/^\s*\*\s*/';
			foreach ( $changelog as $line ) {
				if ( preg_match( $item_pattern, $line ) ) {
					$line = preg_replace( $item_pattern, '', $line );
					$line = htmlspecialchars( $line );
					$line = preg_replace( '/`([^`]*)`/', '<code>$1</code>', $line );
					$line = preg_replace(
						'/([^a-zA-Z0-9])__([^\s_]*)__([^a-zA-Z0-9])/', '$1<strong>$2</strong>$3', $line
					);
					$line = preg_replace( '/([^a-zA-Z0-9])_([^\s_]*)_([^a-zA-Z0-9])/', '$1<em>$2</em>$3', $line );
					$output .= '<li>' . $line . '</li>';
				}
			}

			$output .= '</ul>';
			$output .= '</div>';

			echo $output;
		}
	}

	/**
	 * Translate term names into current (or default) language
	 *
	 * @see add_filters()
	 *
	 * @param array $terms Term objects
	 *
	 * @return array
	 */
	public function translate_terms( $terms ) {

		if (
			is_array( $terms )
			&& count( $terms )
		) {
			foreach ( $terms as $key => $term ) {
				$terms[ $key ] = $this->translate_term( $term );
			}
		}

		return $terms;
	}

	/**
	 * Translate term name into current (or default) language
	 *
	 * @see add_filters()
	 *
	 * @param object $term Term object
	 *
	 * @return object
	 */
	public function translate_term( $term ) {

		if (
			is_object( $term )
			&& isset( $term->name )
		) {
			$term->name = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $term->name );
		}

		return $term;
	}

	/**
	 * Translate tax labels into current (or default) language
	 *
	 * @see add_filters()
	 *
	 * @param array $tax_totals Tax totals array
	 *
	 * @return array
	 */
	public function translate_tax_totals( $tax_totals ) {

		foreach ( $tax_totals as $key => $tax_total ) {
			if ( isset( $tax_total->label ) ) {
				$tax_totals[ $key ]->label = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage(
					$tax_total->label
				);
			}
		}

		return $tax_totals;
	}

	/**
	 * Translate gateway settings into current (or default) language
	 *
	 * @see add_filters()
	 *
	 * @param array $settings Settings array
	 *
	 * @return array
	 */
	public function translate_gateway_settings( $settings ) {

		if ( is_array( $settings ) ) {
			$keys = array(
				'title',
				'description',
				'instructions',
			);
			foreach ( $keys as $key ) {
				if ( isset( $settings[ $key ] ) ) {
					$settings[ $key ] = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $settings[ $key ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Add `lang` query var to given site URL
	 *
	 * @see add_filters()
	 *
	 * @param string $url  Site URL
	 * @param string $path Path
	 *
	 * @return string
	 */
	public function add_lang_query_var_to_site_url( $url, $path ) {

		$paths = array(
			'/wp-comments-post.php',
		);
		if ( in_array( $path, $paths ) ) {
			$url = $this->add_lang_query_var_to_url( $url );
		}

		return $url;
	}

	/**
	 * Add `lang` query var to given URL
	 *
	 * @see add_filters()
	 *
	 * @param string $url URL
	 *
	 * @return string
	 */
	public function add_lang_query_var_to_url( $url ) {

		return add_query_arg( 'lang', qtrans_getLanguage(), untrailingslashit( $url ) );
	}

	/**
	 * Add `lang` query var to array element with key 'redirect'
	 *
	 * @see add_filters()
	 *
	 * @param array $data Payment data
	 *
	 * @return array
	 */
	public function add_lang_query_var_to_payment_redirect_url( $data ) {

		$key = 'redirect';
		if ( isset( $data[ $key ] ) ) {
			$data[ $key ] = $this->add_lang_query_var_to_url( $data[ $key ] );
		}

		return $data;
	}

	/**
	 * Add `lang` query var to specific WooCommerce params URLs
	 *
	 * @see add_filters()
	 *
	 * @param array $params WooCommerce params
	 *
	 * @return array
	 */
	public function add_lang_query_var_to_woocommerce_params( $params ) {

		if ( ! is_array( $params ) ) {
			return $params;
		}

		$keys = array(
			'ajax_url',
			'checkout_url',
		);
		foreach ( $keys as $key ) {
			if ( isset( $params[ $key ] ) ) {
				$params[ $key ] = $this->add_lang_query_var_to_url( $params[ $key ] );
			}
		}

		return $params;
	}

}
