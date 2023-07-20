<?php
/**
 * Stripe Gateway for PWA Plugin
 *
 * @copyright Copyright (C) 2023, Jaestic - jaestic@jaestic.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: WP Stripe Gateway for PWA
 * Version:     1.1
 * Plugin URI:  https://github.com/c5vargas/wp_secret-love-hotels
 * Description: Description: Este plugin te permite integrar la pasarela de pago Stripe en tu tienda WooCommerce para aceptar pagos con tarjetas de crédito y débito.
 * Author:      Jaestic
 * Author URI:  https://jaestic.com
 * Text Domain: wpstripegateway
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

require_once plugin_dir_path(__FILE__) . 'stripe-php/init.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sg-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sg-endpoints.php';

add_action('init', 'sg_init');

function sg_init() {

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'mi_plugin_missing_wc_notice');
    }

    new SG_Admin();
    new SG_Endpoints();
}

function mi_plugin_missing_wc_notice() {
    echo '<div class="notice notice-error is-dismissible">
        <p>Stripe Gateway requiere que WooCommerce esté instalado y activo. Por favor, asegúrate de activar WooCommerce antes de activar este plugin.</p>
    </div>';
}