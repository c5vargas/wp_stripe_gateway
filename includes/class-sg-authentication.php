<?php
/**
 * WPAlmomento Auth
 *
 * @class    SG_Auth
 * @package  WPAlmomento\Auth
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SG_Auth class.
 */
class SG_Auth {
	public function __construct() {
		add_filter('jwt_auth_token_before_dispatch', array($this, 'add_data_to_jwt'), 10, 2);
        add_action('rest_api_init', array($this, 'add_custom_fields'));
    }

    function add_data_to_jwt($data, $user) {
        if(!$user)
            return $data;

        $customer = new WC_Customer($user->ID);
        
        // Woo
        $data['email'] = $customer->get_email();
        $data['billing'] = $customer->get_billing();
        $data['shipping'] = $customer->get_shipping();
        $data['user_first_name'] = $customer->get_first_name();
        $data['user_last_name'] = $customer->get_last_name();

        $data['user_id'] = $user->ID;
        $data['user_login'] = $user->user_login;
        $data['user_roles'] = $user->roles;
        $data['user_role'] = implode(', ', $user->roles);
        $data['user_registered'] = $user->user_registered;
        $data['user_url'] = $user->user_url;
        $data['user_status'] = $user->user_status;
        $data['user_avatar_url'] = get_avatar_url($user->ID);

        return $data;
    }

    function add_custom_fields() {
        register_rest_field('user','profile', array(
                'get_callback'    => array($this, 'get_custom_fields'),
                'update_callback' => null,
                'schema'          => null,
        ));
    }

    function get_custom_fields( $object, $field_name, $request ) {
        $data = array();
        $user = get_user_by('id', $object['id'] );

        if(!$user)
            return $data;

        $customer = new WC_Customer($object['id']);
        
        // Woo
        $data['email'] = $customer->get_email();
        $data['billing'] = $customer->get_billing();
        $data['shipping'] = $customer->get_shipping();
        $data['user_first_name'] = $customer->get_first_name();
        $data['user_last_name'] = $customer->get_last_name();

        $data['user_id'] = $user->ID;
        $data['user_login'] = $user->user_login;
        $data['user_roles'] = $user->roles;
        $data['user_role'] = implode(', ', $user->roles);
        $data['user_registered'] = $user->user_registered;
        $data['user_url'] = $user->user_url;
        $data['user_status'] = $user->user_status;
        $data['user_avatar_url'] = get_avatar_url($user->ID);

        return $data;
    }
}
