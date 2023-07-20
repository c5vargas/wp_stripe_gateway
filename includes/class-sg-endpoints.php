<?php
/**
 * StripeGateway Endpoints
 *
 * @class    SG_Endpoints
 * @package  StripeGateway\Endpoints
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
		add_action('rest_api_init', 'sg_register_api_route');
	}

    function sg_create_payment_intent($request) {
        try {
            $parameters 	= $request->get_params();
            $order_id       = sanitize_text_field($parameters['order_id']);
            $private_key    = get_option('stripe_private_key');
    
            if(empty($order_id)){
                return array(
                    'status' => false,
                    'client_secret' => null,
                    'message' => __( "Order ID 'order_id' is required.", 'wc-rest-payment' )
                );
            }

            if(empty($private_key)){
                return array(
                    'status' => false,
                    'client_secret' => null,
                    'message' => __( "The Stripe private key has not been configured, access your administrator panel and configure it.", 'wc-rest-payment' )
                );
            }
    
            $order = wc_get_order($order_id);
    
            if(!$order) {
                return array(
                    'status' => false,
                    'client_secret' => null,
                    'message' => __( 'Order is empty.', 'wc-rest-payment' )
                );
            }
    
            \Stripe\Stripe::setApiKey($private_key);
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => floatval($order->get_total()) * 100,
                'currency' => 'EUR',
                'metadata' => ['order_id' => $order_id],
                'description' => "WC Pedido #". $order_id,
            ]);
    
            return array(
                'status' => true,
                'client_secret' => $paymentIntent->client_secret,
                'message' => null
            );
    
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Error al crear el Payment Intent: ' . $e->getMessage());
    
            return array(
                'status' => false,
                'client_secret' => null,
                'message' => $e->getMessage()
            );
        }
    }
    
    
    function sg_register_api_route() {
        register_rest_route('stripe-payment-gateway/v1', '/create-payment-intent', array(
            'methods' => 'POST',
            'callback' => 'sg_create_payment_intent',
            'permission_callback' => function () {
                return true;
            },
        ));
    }

}
