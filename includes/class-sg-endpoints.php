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

        return array(
            'status' => true,
            'message' => __( "The stripe client has already been previously generated.", 'wc-rest-payment' )
        );
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

    function getCatTermById($catId) {
        $product_term_args = array(
            'taxonomy' => 'product_cat',
            'include' => $catId,
            'orderby'  => 'include'
        );
        $product_terms = get_terms($product_term_args);

        $product_term_slugs = [];
        foreach ($product_terms as $product_term) {
            $product_term_slugs[] = $product_term->slug;
        }

        return $product_term_slugs;
    }

    function sg_get_products($request) {
        $parameters 	= $request->get_params();
        $category       = sanitize_text_field($parameters['category']);
        $onSale         = sanitize_text_field($parameters['on_sale']);
        $featured       = sanitize_text_field($parameters['featured']);
        $orderBy        = sanitize_text_field($parameters['orderby']);
        $perPage        = sanitize_text_field($parameters['per_page']);

        $categoryTerm = $this->getCatTermById($category);


        $args = array(
            'category' => $categoryTerm,
            'orderby'  => 'name',
        );

        if($onSale) {
            $sales_ids = wc_get_product_ids_on_sale();
            $args['include'] = $sales_ids;
        }

        if($featured) $args['featured'] = $featured;
        if($orderBy) $args['orderby'] = $orderBy;
        if($perPage) $args['limit'] = $perPage;

        $products = wc_get_products( $args );
        $simplified_data = array();

        foreach ($products as $key => $single_product_data) {
            $data = $single_product_data->get_data();
            $simplified_data[$key] = $data;
            $simplified_data[$key]['image'] = wp_get_attachment_image_url($data['image_id'], 'full');

            if ($single_product_data->is_type('variable')) {
                $variation_ids = $single_product_data->get_children();
                $variations = array();
        
                foreach ($variation_ids as $variation_id) {
                    $variation = new WC_Product_Variation($variation_id);
                    $variation_data = $variation->get_data();
                    $variation_data['image'] = wp_get_attachment_image_url($data['image_id'], 'full');
                    $variations[] = $variation_data;
                }
        
                $simplified_data[$key]['variations'] = $variations;
            }
        }

        return array(
            'status' => true,
            'results' => $simplified_data
        );
    }

    function sg_get_products_by_id($request) {
        // Obtener el parámetro 'productIds' del request.
        $product_ids = $request->get_param('productIds');
    
        // Validar que el parámetro sea un array y no esté vacío.
        if (!is_array($product_ids) || empty($product_ids)) {
            return rest_ensure_response([
                'error' => 'Invalid or missing "productIds" parameter.'
            ], 400);
        }
    
        // Preparar los argumentos para filtrar los productos por IDs.
        $args = [
            'include' => $product_ids,
            'limit'   => count($product_ids),
            'status'  => 'publish', // Solo productos publicados.
        ];
    
        // Obtener los productos usando WooCommerce.
        $products = wc_get_products($args);
    
        // Crear un array para almacenar los datos simplificados.
        $simplified_data = [];
    
        foreach ($products as $single_product) {
            // Obtener los datos básicos del producto.
            $product_data = $single_product->get_data();
    
            // Agregar la URL de la imagen principal del producto.
            $product_data['image'] = wp_get_attachment_image_url($product_data['image_id'], 'full');
    
            // Si el producto es de tipo variable, procesar sus variaciones.
            if ($single_product->is_type('variable')) {
                $variation_ids = $single_product->get_children();
                $variations = [];
    
                foreach ($variation_ids as $variation_id) {
                    $variation = new WC_Product_Variation($variation_id);
                    $variation_data = $variation->get_data();
                    $variation_data['image'] = wp_get_attachment_image_url($variation_data['image_id'], 'full');
                    $variations[] = $variation_data;
                }
    
                // Añadir las variaciones al producto.
                $product_data['variations'] = $variations;
            }
    
            // Agregar el producto procesado al array de resultados.
            $simplified_data[] = $product_data;
        }
    
        // Devolver la respuesta con los productos simplificados.
        return rest_ensure_response([
            'status' => true,
            'results' => $simplified_data
        ]);
    }


    function sg_create_order($request) {
        $parameters 	= $request->get_params();
        $billing        = $parameters['billing'];
        $shipping       = $parameters['shipping'];
        $customerId     = $parameters['customer_id'];
        $lineItems      = $parameters['line_items'];
        $paymentMethod  = $parameters['payment_method'];
        $setPaid        = $parameters['set_paid'];
        $paymentMethodTitle  = $parameters['payment_method_title'];
        $shippingLines  = $parameters['shipping_lines'];
        $couponLines  = $parameters['coupon_lines'];

        $shippingOrder = new WC_Order_Item_Shipping();
        $shippingOrder->set_method_title( $shippingLines[0]['method_title'] );
        $shippingOrder->set_method_id( $shippingLines[0]['method_id'] );
        $shippingOrder->set_total( floatval($shippingLines[0]['total']) );

        $order = wc_create_order();

        for ($i=0; $i < count($lineItems); $i++) { 
            $order->add_product(  get_product($lineItems[$i]['product_id']), $lineItems[$i]['quantity'] );
        }

        $order->set_customer_id( 1 );
        $order->set_address( $billing, 'billing' );
        $order->set_address( $shipping, 'shipping' );
        $order->add_item($shippingOrder);
        $order->set_customer_id($customerId);
        $order->set_payment_method( $paymentMethod );
        $order->set_payment_method_title( $paymentMethodTitle );
        $order->apply_coupon($couponLines[0]['code']);
        $order->calculate_totals();
        
        return json_encode($order->get_data());
    }
    
    function sg_register_api_route() {
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

        register_rest_route('stripe-payment-gateway/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'sg_get_products'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('stripe-payment-gateway/v1', '/products-by-id', array(
            'methods' => 'GET',
            'callback' => array($this, 'sg_get_products_by_id'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('stripe-payment-gateway/v1', '/create-order', array(
            'methods' => 'POST',
            'callback' => array($this, 'sg_create_order'),
            'permission_callback' => '__return_true'
        ));
    }

}
