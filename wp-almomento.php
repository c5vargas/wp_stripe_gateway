<?php

/**
 * Plugin Name: WP Almomento for PWA Plugin
 * Description: Este plugin te permite integrar una PWA enfocada al ecommerce utilizando tu Wordpress como API para obtener los productos.
 * Version: 2.0.9
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

function bbloomer_order_pay_without_login( $allcaps, $caps, $args ) {
    if ( isset( $caps[0], $_GET['key'] ) ) {
        if ( $caps[0] == 'pay_for_order' ) {
            $order_id = isset( $args[2] ) ? $args[2] : null;
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $allcaps['pay_for_order'] = true;
            }
        }
    }
    return $allcaps;
}

function change_cod_payment_order_status( $order_status, $order ) {
    return 'on-hold';
}

function wc_thank_you_redirect() { 
  if( isset( $_GET['key'] ) && is_wc_endpoint_url( 'order-received' ) ) {
    wp_redirect('https://app.almomento.cat/account/orders');
  }
}

add_action( 'woocommerce_api_loaded', function(){
	include_once( 'class-wc-api-custom.php' );
});

add_filter( 'woocommerce_api_classes', function( $classes ){
	$classes[] = 'WC_API_Custom';
	return $classes;
});

add_action( 'template_redirect', 'wc_thank_you_redirect' );
add_filter( 'woocommerce_cod_process_payment_order_status', 'change_cod_payment_order_status', 10, 2 );
add_filter( 'user_has_cap', 'bbloomer_order_pay_without_login', 9999, 3 );
add_filter( 'woocommerce_order_email_verification_required', '__return_false', 9999 );
add_action( 'init', 'sg_init' );
