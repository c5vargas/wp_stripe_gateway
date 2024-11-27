<?php

/**
 * Plugin Name: WP Almomento for PWA Plugin
 * Description: Este plugin te permite integrar una PWA enfocada al ecommerce utilizando tu Wordpress como API para obtener los productos.
 * Version: 2.0.7
 * Author: Jaestic S.L
 * Text Domain: wp-almomento
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-sg-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sg-authentication.php';


function sg_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'sg_missing_wc_notice');
        return;
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

add_action('init', 'sg_init');
