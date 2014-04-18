<?php # -*- coding: utf-8 -*-
/**
 * Plugin Name: IM8 qTranslate WooCommerce
 * Plugin URI: http://wordpress.org/plugins/im8-qtranslate-woocommerce/
 * Description: Front-end integration of qTranslate into WooCommerce.
 * Version: 1.5.4
 * Author: ipm-frommen, intermedi8
 * Author URI: http://intermedi8.de
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 */

// Exit on direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/inc/IM8qTranslateWooCommerce.php';
$data = new stdClass();
$data->file = __FILE__;
$IM8qTranslateWooCommerce = new IM8qTranslateWooCommerce( $data );
add_action( 'plugins_loaded', array( $IM8qTranslateWooCommerce, 'init_on_demand' ) );
