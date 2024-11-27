<?php
/**
 * Plugin Name: WP Almomento for PWA Plugin
 * Version:     2.0.0
 * Plugin URI:  https://github.com/c5vargas/wp_almomento
 * Description: Este plugin te permite integrar una PWA enfocada al ecommerce utilizando tu Wordpress como API para obtener los productos.
 * Author:      Jaestic
 * Author URI:  https://jaestic.com
 * Text Domain: wp_almomento
 * @copyright Copyright (C) 2023, Jaestic - jaestic@jaestic.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 */

require_once plugin_dir_path(__FILE__) . 'includes/class-sg-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sg-authentication.php';

add_action('init', 'sg_init');

function sg_init() {

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'sg_missing_wc_notice');
    }

    new SG_Endpoints();
    new SG_Auth();
}

function sg_missing_wc_notice() {
    echo '<div class="notice notice-error is-dismissible">
        <p>WP Almomento requiere que WooCommerce esté instalado y activo. Por favor, asegúrate de activar WooCommerce antes de activar este plugin.</p>
    </div>';
}

add_action( 'woocommerce_api_loaded', function(){
	include_once( 'class-wc-api-custom.php' );
});
add_filter( 'woocommerce_api_classes', function( $classes ){
	$classes[] = 'WC_API_Custom';
	return $classes;
});