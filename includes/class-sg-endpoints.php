<?php
/**
 * WPAlmomento Endpoints
 *
 * @class    SG_Endpoints
 * @package  WPAlmomento\Endpoints
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SG_Endpoints class.
 */
class SG_Endpoints {

	public function __construct() {
        \Stripe\Stripe::setApiKey(get_option('stripe_private_key'));

		add_action('rest_api_init', array($this, 'sg_register_api_route'));
	}

    function sg_check_stripe_customer_id($request) {
        $user_id = get_current_user_id();

        if(empty($user_id)){
            return array(
                'status' => false,
                'client_secret' => null,
                'message' => __( "User ID 'user_id' is required.", 'wc-rest-payment' )
            );
        }

        $stripeCustomerId = get_user_meta($user_id, 'customer_stripe_id');

        if(empty($stripeCustomerId)) {
            try {
                $customer = new WC_Customer( $user_id );
                $customerData = [
                    'email' => $customer->get_email(),
                    'name' => $customer->get_first_name().' '.$customer->get_last_name(),
                    'phone' => $customer->get_billing_phone(),
                    'address' => [
                        'city' => $customer->get_billing_city(),
                        'country' => $customer->get_billing_country(),
                        'line1' => $customer->get_billing_address_1(),
                        'line2' => $customer->get_billing_address_2(),
                        'postal_code' => $customer->get_billing_postcode(),
                        'state' => $customer->get_billing_state(),
                    ],
                ];

                $stripeCustomer = \Stripe\Customer::create($customerData);
                update_user_meta($user_id, 'customer_stripe_id', $stripeCustomer->id);
    
                return array(
                    'status' => true,
                    'message' => __( "The stripe client has been generated successfully.", 'wc-rest-payment' )
                );
            } catch (\Stripe\Exception\ApiErrorException $e) {
                return array(
                    'status' => false,
                    'message' => $e->getMessage()
                );
            }
        }

        return array(
            'status' => true,
            'message' => __( "The stripe client has already been previously generated.", 'wc-rest-payment' )
        );
    }

    function sg_create_payment_intent($request) {
        try {
            $parameters 	= $request->get_params();
            $order_id       = sanitize_text_field($parameters['order_id']);

            if(empty($order_id)){
                return array(
                    'status' => false,
                    'client_secret' => null,
                    'message' => __( "Order ID 'order_id' is required.", 'wc-rest-payment' )
                );
            }
    
            $order = new WC_Order($order_id);
    
            if(!$order) {
                return array(
                    'status' => false,
                    'client_secret' => null,
                    'message' => __( 'Order is empty.', 'wc-rest-payment' )
                );
            }
            
            $userId = get_current_user_id();
            $stripeCustomerId = get_user_meta($userId, 'customer_stripe_id');

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount'        => floatval($order->get_total()) * 100,
                'currency'      => 'EUR',
                'customer'      => $stripeCustomerId ? $stripeCustomerId[0] : null,
                'metadata'      => ['order_id' => $order_id],
                'description'   => "WC Pedido #". $order_id,
                'shipping'      => [
                    'address'       => [
                        'city' => $order->get_shipping_city(),
                        'country' => $order->get_shipping_country(),
                        'line1' => $order->get_shipping_address_1(),
                        'line2' => $order->get_shipping_address_2(),
                        'postal_code' => $order->get_shipping_postcode(),
                        'state' => $order->get_shipping_state(),
                    ],
                    'name'          => $order->get_shipping_first_name()." ". $order->get_shipping_last_name(),
                    'phone'         => $order->get_shipping_phone()
                ],
            ]);
    
            return array(
                'status' => true,
                'client_secret' => $paymentIntent->client_secret,
                'message' => null
            );
    
        } catch (\Stripe\Exception\ApiErrorException $e) {    
            return array(
                'status' => false,
                'client_secret' => null,
                'message' => $e->getMessage()
            );
        }
    }
    
    function sg_create_user_account($request) {
        try {
            $parameters 	= $request->get_params();
            $username       = sanitize_text_field($parameters['username']);
            $email          = sanitize_text_field($parameters['email']);
            $password       = sanitize_text_field($parameters['password']);
            $repassword     = sanitize_text_field($parameters['repassword']);

            if(empty($username) || empty($email) || empty($password) || empty($repassword)){
                return array(
                    'status' => false,
                    'message' => __( "All form fields are required.", 'wc-rest-payment' )
                );
            }

            if($password != $repassword) {
                return array(
                    'status' => false,
                    'message' => __( "Password confirmation does not match.", 'wc-rest-payment' )
                );
            }

            try {
                $userId = wc_create_new_customer($email, $username, $password);
                
                if($userId) {
                    return array(
                        'status' => true,
                        'response' => $user,
                        'message' => null
                    );
                }
            } catch (Exception $e) {
                return array(
                    'status' => false,
                    'message' => $e->getMessage()
                );
            }
        } catch (Exception $e) {    
            return array(
                'status' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    function sg_register_api_route() {
        register_rest_route('stripe-payment-gateway/v1', '/create-payment-intent', array(
            'methods' => 'POST',
            'callback' => array($this, 'sg_create_payment_intent'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('stripe-payment-gateway/v1', '/check-authentication', array(
            'methods' => 'GET',
            'callback' => array($this, 'sg_check_stripe_customer_id'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('stripe-payment-gateway/v1', '/users', array(
            'methods' => 'POST',
            'callback' => array($this, 'sg_create_user_account'),
            'permission_callback' => '__return_true'
        ));
    }

}
