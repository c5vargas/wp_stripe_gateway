<?php
/**
 * WPAlmomento Admin
 *
 * @class    SG_Admin
 * @package  WPAlmomento\Admin
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
			'WP Almomento integration',
			'WP Almomento',        	// Título del menú
			'manage_options',   		// Capacidad requerida para acceder al menú
			'wp_almomento_admin',   	// Identificador único de la página
			array($this, 'display_settings_page'), // Función que muestra el contenido de la página
			'dashicons-store'
		);
	}

	public function settings_init() {
        register_setting('wp_almomento_options', 'stripe_public_key');
        register_setting('wp_almomento_options', 'stripe_private_key');

        add_settings_section(
            'wp_almomento_section',
            'Configuración de WP Almomento',
            array($this, 'section_callback'),
            'wp_almomento_settings'
        );

		add_settings_field(
            'stripe_public_key',
            'Public Key de Stripe',
            array($this, 'public_key_callback'),
            'wp_almomento_settings',
            'wp_almomento_section'
        );

        add_settings_field(
            'stripe_private_key',
            'Private Key de Stripe',
            array($this, 'private_key_callback'),
            'wp_almomento_settings',
            'wp_almomento_section'
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
					<h5 class="card-title">WP Almomento for PWA</h5>
					<form method="post" action="options.php">
						<?php
						// Muestra los campos de configuración
						settings_fields('wp_almomento_options');
						do_settings_sections('wp_almomento_settings');
						submit_button('Guardar cambios', 'primary', 'submit', false);
						?>
					</form>
				</div>
			</div>
        </div>
		<?php
	}

	function enqueue_styles() {
		if (isset($_GET['page']) && $_GET['page'] === 'wp_almomento_admin') {
			wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
		}
	}

}
