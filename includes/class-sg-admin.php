<?php
/**
 * StripeGateway Admin
 *
 * @class    SG_Admin
 * @package  StripeGateway\Admin
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SG_Admin class.
 */
class SG_Admin {
	public function __construct() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'settings_init'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
	}

	function add_admin_menu() {
		add_menu_page(
			'Stripe Gateway integration',
			'Sripe Gateway',        	// Título del menú
			'manage_options',   		// Capacidad requerida para acceder al menú
			'stripe_gateway_admin',   	// Identificador único de la página
			array($this, 'display_settings_page'), // Función que muestra el contenido de la página
			'dashicons-store'
		);
	}

	public function settings_init() {
        register_setting('stripe_gateway_options', 'stripe_public_key');
        register_setting('stripe_gateway_options', 'stripe_private_key');

        add_settings_section(
            'stripe_gateway_section',
            'Configuración de Stripe Gateway',
            array($this, 'section_callback'),
            'stripe_gateway_settings'
        );

		add_settings_field(
            'stripe_public_key',
            'Public Key de Stripe',
            array($this, 'public_key_callback'),
            'stripe_gateway_settings',
            'stripe_gateway_section'
        );

        add_settings_field(
            'stripe_private_key',
            'Private Key de Stripe',
            array($this, 'private_key_callback'),
            'stripe_gateway_settings',
            'stripe_gateway_section'
        );
    }

	public function section_callback() {
        echo 'Introduce la clave privada (Private Key) de tu cuenta de Stripe:';
    }

	public function private_key_callback() {
        $private_key = get_option('stripe_private_key');
        echo '<input type="text" class="form-control" name="stripe_private_key" value="' . esc_attr($private_key) . '" />';
    }

	public function public_key_callback() {
        $public_key = get_option('stripe_public_key');
        echo '<input type="text" class="form-control" name="stripe_public_key" value="' . esc_attr($public_key) . '" />';
    }

	public function display_settings_page() {
		?>
		<div class="wrap">
			<div class="card shadow-lg">
				<div class="card-body">
					<h5 class="card-title">Configuración de Stripe Gateway</h5>
					<form method="post" action="options.php">
						<?php
						// Muestra los campos de configuración
						settings_fields('stripe_gateway_options');
						do_settings_sections('stripe_gateway_settings');
						submit_button('Guardar cambios', 'primary', 'submit', false);
						?>
					</form>
				</div>
			</div>
        </div>
		<?php
	}

	function enqueue_styles() {
		if (isset($_GET['page']) && $_GET['page'] === 'stripe_gateway_admin') {
			wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
		}
	}

}
