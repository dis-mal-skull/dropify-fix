<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/Constants.php');
include_once(dirname(__DIR__) . '/Helper.php');
include_once(dirname(__DIR__) . '/models/TokenModel.php');


class JPIODFW_OrdersModel
{
    private $helper;
    private $constants;
    public $tokenModel;

    public $logger;
    private static $instance;
    /*......*/

    /*......*/
    // class constructor
    public function __construct()
    {
        $this->helper = JPIODFW_Helper::GetInstance();
        $this->constants = JPIODFW_Constants::GetInstance();
        $this->logger = wc_get_logger();
        $this->tokenModel = JPIODFW_TokenModel::GetInstance();
    }


    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function makeProductsArray($order)
    {
        $create_product_if_not_exist = sanitize_text_field(get_option('dropi-woocomerce-create_product_if_no_exist'));
        $notes = '';
        $products = [];
        $total = 0;
        $logger = wc_get_logger();
        $shipping = $order->get_total_shipping();

        $taxes = $order->get_items('tax');
        $total_taxes = 0;

        foreach ($taxes as $tax) {
            $total_taxes += $tax->get_tax_total();
        }

        $fee = 0; // extra fee onpayuments methods

        $paymentMethod = $order->get_payment_method();
        $payment_gateway = WC()->payment_gateways->payment_gateways()[$paymentMethod];

        global $wpdb;
        $table_name = $wpdb->base_prefix . 'dropi_tokens';
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

        if (!$wpdb->get_var($query) == $table_name) {

            JPIODFW_DropiTokensTable::create_dropi_tokens_table();
            $token = sanitize_text_field(get_option('dropi-woocomerce-token'));
            $sync = get_option('dropi-woocomerce-autosync_orders');
            $create_prod = get_option('dropi-woocomerce-create_product_if_no_exist');
            $sync_token = '';

            if ($sync == 1) {
                $sync_token = $this->constants->SINC_AUTOM;
            } else {
                $sync_token = $this->constants->SINC_MANUAL;
            }
            $this->tokenModel->setNewToken($token, 'Tienda 1', $sync_token, $create_prod);
            $imported_products = $this->tokenModel->getOldImportedProducts();
            foreach ($imported_products as $product) {
                update_post_meta($product->post_id, '_dropi_token_store', 'Tienda 1');
                update_post_meta($product->post_id, '_dropi_token', $token);
            }
        }

        if (is_object($payment_gateway) && isset($payment_gateway->settings) && isset($payment_gateway->settings['fee'])) {
            $fee = $payment_gateway->settings['fee'];
            $shipping = $shipping + $fee;
            $logger->info('new shipping + fee' . $shipping, array('source' => 'dropi-orders'));
        }

        $order_items = $order->get_items();


        $contadordeproductosdropi = 0;
        foreach ($order_items as $item_key => $item) {
            $item = (object) $item;
            $item_data = $item->get_data();
            $product_id = $item_data['product_id'];
            $dropi_product = get_post_meta($product_id, '_dropi_product', true);


            $logger->info('_dropi_product ', array('source' => 'dropi-orders'));
            $logger->info(print_r($dropi_product, true), array('source' => 'dropi-orders'));


            if (!empty($dropi_product)) {
                $unserialized = unserialize($dropi_product);

                if (empty($unserialized) || $unserialized == false) {

                    $unserialized = json_decode($dropi_product);
                }
                $dropi_product = $unserialized;
            }

            if ((!empty($dropi_product) && (is_object($dropi_product) || is_array($dropi_product))) || $create_product_if_not_exist === '1') {
                $contadordeproductosdropi++;
            }
        }

        $logger->info('contador de productos: ', array('source' => 'dropi-orders'));
        $logger->info($contadordeproductosdropi, array('source' => 'dropi-orders'));

        if ($contadordeproductosdropi > 0) {

            $amountToAdd = (floatval($shipping) + floatval($total_taxes)) / $contadordeproductosdropi;
            $logger->info('amountToAdd', array('source' => 'dropi-orders'));
            $logger->info(print_r($amountToAdd, true), array('source' => 'dropi-orders'));
        }


        foreach ($order_items as $item_key => $item) {

            $item_name = $item->get_name(); // Name of the product*/
            $quantity = $item->get_quantity();
            $item_data = $item->get_data();
            $product_id = $item_data['product_id'];
            $variation_id = $item_data['variation_id'];
            $item_total = $item->get_total();
            $logger->info(print_r($item_data, true), array('source' => 'dropi-orders'));
            //var_dump( $product_id);
            $dropi_product = get_post_meta($product_id, '_dropi_product', true);
            $token_product = get_post_meta($product_id, '_dropi_token', true);

            //var_dump( $dropi_product);

            if (empty($dropi_product) && $create_product_if_not_exist === '1') {
                /**entonces creo un objecto por defecto */
                $dropi_product = (object) ['name' => $item_name];
                $dropi_product->name = $item_name;
                $token_product = $this->tokenModel->getTokens()[0]->token; // here I have to bring a token to create the product in Dropi... how to know if its supplier and i can create product?
            } else {

                $unserialized = unserialize($dropi_product);

                if (empty($unserialized) || $unserialized == false) {

                    $unserialized = json_decode($dropi_product);
                }
                $dropi_product = $unserialized;
            }

            if (!empty($variation_id)) {
                $notes .= ' -- ' . $item_name . ": " . $this->get_variation_data_from_variation_id($variation_id);
            }


            if ((!empty($dropi_product) && (is_object($dropi_product) || is_array($dropi_product))) || $create_product_if_not_exist === '1') {

                $subtotalpreciolinea = $amountToAdd;
                $item_total = $item_total + $subtotalpreciolinea;

                $dropi_product->name = $item_name;
                $dropi_product->quantity = intval($quantity);
                $dropi_product->stock = intval($dropi_product->stock);
                $dropi_product->price = floatval($item_total / $quantity);
                $dropi_product->token = $token_product;

                $total = $total + ($dropi_product->price * $dropi_product->quantity);

                $dropi_variation = get_post_meta($variation_id, '_dropi_variation', true);
                $logger->info('1 - variacion: ' . $variation_id . " " . $dropi_product->name, array('source' => 'dropi-orders'));
                $logger->info($dropi_variation, array('source' => 'dropi-orders'));


                // Registrar la cadena serializada
                $logger->info("Serialized data: " . var_export($dropi_variation, true), array('source' => 'dropi-orders'));

                try {
                    // Intentar deserializar la cadena
                    $dropi_variation = unserialize($dropi_variation);

                    // Verificar si la deserialización fue exitosa
                    if ($dropi_variation === false) {
                        throw new Exception('Failed to unserialize data');
                    }
                    // Imprimir el objeto deserializado
                    //$logger->info(print_r($dropi_variation, true), array('source' => 'dropi-orders'));
                } catch (Exception $e) {
                    $logger->error("Error during unserialization: " . $e->getMessage(), array('source' => 'dropi-orders'));
                }

                // $dropi_variation = json_decode($dropi_variation); 

                if ($dropi_product->type == 'VARIABLE' && !empty($dropi_variation) && (is_object($dropi_variation) || is_array($dropi_variation))) {


                    $dropi_product->variation_id = $dropi_variation->id;
                }




                $products[] = $dropi_product;
                $logger->info($total, array('source' => 'dropi-orders'));
            }
        }

        return ['products' => $products, 'notes' => $notes, 'total' => $total];
    }



    private function getVariationData($id)
    {
        $dropi_product = get_post_meta($id, '_dropi_variation', true);
        return $dropi_product;
    }


    /**obtener datos de un porducto variable */
    private function get_variation_data_from_variation_id($item_id)
    {
        $_product = new WC_Product_Variation($item_id);
        $variation_data = $_product->get_variation_attributes();
        $variation_detail = woocommerce_get_formatted_variation($variation_data, true); // this will give all variation detail in one line
        // $variation_detail = woocommerce_get_formatted_variation( $variation_data, false);  // this will give all variation detail one by one
        return $variation_detail; // $variation_detail will return string containing variation detail which can be used to print on website
        // return $variation_data; // $variation_data will return only the data which can be used to store variation data
    }
    /**
     * Creo una orden
     */
    public function save($order)
    {
        $result = false;
        try {
            $makeProductsArray = $this->makeProductsArray($order);

            //solo si tengo productos que sean de dropi
            if (sizeof($makeProductsArray['products']) > 0) {
                $listProducts = $makeProductsArray['products'];
                $tempToken = $listProducts[0]->token;
                $is_multitoken = false;
                $id_token = '';

                foreach ($listProducts as $item_key => $item) {
                    if ($item->token == $tempToken) {
                        $tempToken = $item->token;
                    } else {
                        $tempToken = '';
                        $is_multitoken = true;
                        break;
                    }
                }
                if ($is_multitoken == false) {
                    $id_token = $listProducts[0]->token;
                }

                $order_data = $order->get_data(); // The Order data

                $logger = wc_get_logger();
                // LOG ORDER TO CUSTOM "dropi-orders" LOG
                // $logger->info(wc_print_r($makeProductsArray, true), array('source' => 'dropi-orders'));

                $endpoint = $this->constants->API_URL . "orders/myorders";

                $order_type = $this->constants->SIN_RECAUDO;
                $paymentMethod = $order->get_payment_method();
                //VALIDO SI EL METODO DE PAGO ES CONTRA ENTREGA
                if (in_array($paymentMethod, array('cod'))) {
                    $order_type = $this->constants->CON_RECAUDO;
                }
                $this->logger->info('estado', array('source' => 'dropi-orders'));

                $logger->info(wc_print_r($order_data['shipping']['state'], true), array('source' => 'dropi-orders'));
                $create_product_if_not_exist = sanitize_text_field(get_option('dropi-woocomerce-create_product_if_no_exist'));
                $shop_country = wc_get_base_location()['country'];

                $address = !empty($order_data['shipping']['address_1']) ? $order_data['shipping']['address_1'] : $order_data['billing']['address_1'];
                $address .= ' ';
                $address .= !empty($order_data['shipping']['address_2']) ? $order_data['shipping']['address_2'] : $order_data['billing']['address_2'];

                $data = array(
                    //'id' => 'idorden',
                    "total_order" => $makeProductsArray['total'],
                    "notes" => $order_data['customer_note'] . $makeProductsArray['notes'],
                    "name" => !empty($order_data['shipping']['first_name']) ? $order_data['shipping']['first_name'] : $order_data['billing']['first_name'],
                    "surname" => !empty($order_data['shipping']['last_name']) ? $order_data['shipping']['last_name'] : $order_data['billing']['last_name'],
                    "dir" => $address,
                    "country" => !empty($order_data['shipping']['country']) ? $order_data['shipping']['country'] : $order_data['billing']['country'],
                    //todo traer 
                    "state" => !empty($order_data['shipping']['state']) ? $order_data['shipping']['state'] : $order_data['billing']['state'],
                    //todo traer 
                    "city" => !empty($order_data['shipping']['city']) ? $order_data['shipping']['city'] : $order_data['billing']['city'],
                    //todo traer 
                    "phone" => !empty($order_data['shipping']['phone']) ? $order_data['shipping']['phone'] : $order_data['billing']['phone'],
                    "client_email" => !empty($order_data['shipping']['email']) ? $order_data['shipping']['email'] : $order_data['billing']['email'],
                    "payment_method_id" => 1,
                    "status" => $this->constants->STATUS_BORRADOR,
                    "type" => "FINAL_ORDER",
                    "rate_type" => $order_type,
                    "products" => $makeProductsArray['products'],
                    "calculate_costs_and_shiping" => true,
                    "supplier_id" => $makeProductsArray['products'][0]->user_id,
                    'shop_order_id' => $order->get_id(),
                    "create_product_if_not_exist" => $create_product_if_not_exist === '1' ? true : false,
                );

                if ($shop_country == 'MX') {
                    $data['zip_code'] = !empty($order_data['shipping']['postcode']) ? $order_data['shipping']['postcode'] : $order_data['billing']['postcode'];
                }

                $logger->info(wc_print_r('Creating dropi order ' . $order->get_id(), true), array('source' => 'dropi-orders'));

                $logger->info(print_r($data, true));
                $args = array(
                    'body' => json_encode($data),
                    'timeout' => '100000',
                    'redirection' => '5',
                    'httpversion' => '1.0',
                    'method' => 'POST',
                    'blocking' => true,
                    'headers' => array(
                        'Content-Type' => 'application/json;charset=UTF-8',
                        'dropi-integration-key' =>  $id_token,

                    ),
                    'cookies' => array(),
                    'sslverify' => false,

                );

                $response = wp_remote_post(
                    $endpoint,
                    $args
                );

                //$logger->error(wc_print_r($response, true), array('source' => 'dropi-orders'));
                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    $order->update_meta_data('_is_dropi_order', __('Dropi sync error: ' . $error_message, 'wc-dropi-integration'));
                    $logger->error(wc_print_r('Error creating dropi order - wp_error ' . $order->get_id(), true), array('source' => 'dropi-orders'));
                    $logger->error(wc_print_r($error_message, true), array('source' => 'dropi-orders'));
                } else {
                    $response_body = (array) json_decode($response['body']);

                    //
                    //var_dump($response_body);
                    if ($response_body['isSuccess'] == false) {

                        $message = $response_body['message'];
                        if (empty($message)) {
                            $message = $response_body['status'];
                        }


                        $result = __('Error creating order, ' . $message);
                        // $this->helper->showAdminNotice(__('Error creating order, show woocomerce logs for more info.', 'wc-dropi-integration') . ' ' . $response_body['message'], 'error');
                        $order->update_meta_data('_is_dropi_order', __('Dropi sync error: ' . $response_body['message'], 'dropi'));
                        $logger->error(wc_print_r('Error creating dropi order ' . $order->get_id() . " " . $response_body['message'], true), array('source' => 'dropi-orders'));
                        $logger->error(wc_print_r((array) $order, true), array('source' => 'dropi-orders'));
                        $logger->error(wc_print_r($response_body, true), array('source' => 'dropi-orders'));
                        $logger->error(wc_print_r($response_body['message'], true), array('source' => 'dropi-orders'));
                        $logger->error(wc_print_r($response_body['file'], true), array('source' => 'dropi-orders'));
                        $logger->error(wc_print_r($response_body['line'], true), array('source' => 'dropi-orders'));;
                    } else {

                        $order->update_meta_data('_is_dropi_order', 'Yes');
                        if (isset($response_body['objects'])) {


                            $order_dropi = $response_body['objects'];
                            if (isset($order_dropi->id)) {


                                $order->update_meta_data('_dropi_order_id', $order_dropi->id);
                            }
                        }

                        $result = true;
                        $logger->info(wc_print_r(__('Dropi order created ', 'wc-dropi-integration') . " " . $order->get_id(), true), array('source' => 'dropi-orders'));
                    }
                }
            } else {
                $order->update_meta_data('_is_dropi_order', __('This order do not have dropi products', 'wc-dropi-integration'));

                $result = __('This order do not have dropi products', 'wc-dropi-integration');

                // $this->helper->showAdminNotice(__('This order do not have dropi products', 'wc-dropi-integration') , 'warning'); 
            }
            $order->save();
        } catch (Exception $e) {

            $result = 'Error';

            echo $e->getMessage();
            $this->logger->error(wc_print_r($e, true), array('source' => 'dropi-orders'));
        }

        return $result;
    }
}
