<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/Constants.php');
include_once(dirname(__DIR__) . '/models/TokenModel.php');

class JPIODFW_ProductsModel
{

    private $helper;
    private $constants;
    private $logger;
    private static $instance;
    public $TokenModel;

    /*......*/

    /*......*/
    // class constructor
    public function __construct()
    {
        $this->helper = JPIODFW_Helper::GetInstance();
        $this->constants = JPIODFW_Constants::GetInstance();
        $this->logger = wc_get_logger();
        $this->TokenModel = JPIODFW_TokenModel::GetInstance();
    }


    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 
     * busca un producto en dropi
     */
    public function getProduct($id, $id_token)
    {
        $product = null;

        $endpoint = $this->constants->API_URL . "products/v2/" . $id;
        $data = array();
        //$id_token = $this->helper->getToken();
        $args = array(
            //'body' => json_encode($data),
            'timeout' => '100000',
            'redirection' => '5',
            'httpversion' => '1.0',
            'method' => 'GET',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json;charset=UTF-8',
                'dropi-integration-key' =>  $id_token,
            ),
            'cookies' => array(),
            'sslverify' => false,

        );

        $response = wp_remote_get(
            $endpoint,
            $args
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->helper->showAdminNotice($error_message, 'error');
        } else {
            $response_body = (array)json_decode($response['body']);

            if ($response_body['isSuccess'] == false) {

                $this->helper->showAdminNotice($response_body['message'], 'error');
            } else {
                // var_dump($response_body);
                $product  =  $response_body['objects'];
            }
        }

        return $product;
    }
    public function getProducts($per_page, $current_page, $search, $orderby, $order,   $onlyVerifiedUsers, $filter_have_stock, $filter_have_description, $category_filter, $warehouses_filter, $store_filter, $tokens)
    {


        $products = [];
        $endpoint = $this->constants->API_URL . "products/index";
        $data = array(
            'startData' => $current_page,
            'pageSize' => $per_page,
            'order_type' => $order,
            'order_by' => $orderby,
            'keywords' => $search,
            'active' => true,
            'no_count' => true,
            'integration' => true
        );


        if ($endpoint == "https://api.dropi.com.es/integrations/products/index") {
            // eliminar integration del array $data.
            unset($data['integration']);
        }

        if ($endpoint == "https://api.dropi.co/integrations/products/index" || $endpoint == "https://api.dropi.com.py/integrations/products/index" || $endpoint == "https://api.dropi.pe/integrations/products/index" || $endpoint == "https://api.dropi.pa/integrations/products/index") {
            $data['get_stock'] = false;
        }


        if ($onlyVerifiedUsers != null) {
            $data['userVerified'] = true;
        }
        if ($filter_have_stock != null) {
            $data['stockmayor'] = 1;
        }
        if ($filter_have_description != null) {
            $data['notNulldescription'] = true;
        }
        if ($category_filter != null) {
            $data['category'] = $category_filter;
        }
        if ($warehouses_filter != null && $warehouses_filter != 'undefined') {
            $data['warehouse_id'] = $warehouses_filter;
        }

        $token = null;


        if ($store_filter != null) {
            $token = $this->TokenModel->getTokenById(intval($store_filter));

            $token = $token[0];
        } else {

            if (sizeof($tokens) > 0) {


                $token = $tokens[0];
            }
        }



        $all_products = array();

        $final_response = array();
        $temp_response = array();


        $args = array(
            'body' => json_encode($data),
            'timeout' => '100000',
            'redirection' => '5',
            'httpversion' => '1.0',
            // 'method' => 'GET',
            'blocking' => true,
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json;charset=UTF-8',
                'dropi-integration-key' => $token->token
            ),
            'cookies' => array(),
            'sslverify' => false,

        );


        $response = wp_remote_post(
            $endpoint,
            $args
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error_message;
            $this->helper->showAdminNotice($error_message, 'error');
            $this->logger->info(wc_print_r($response, true), array('source' => 'dropi-products'));
        }
        $temp_response = (array)json_decode($response['body']);

        if ($temp_response['isSuccess'] == true) {
            $final_response = $temp_response;
            $all_products = array_merge($all_products, $this->TokenModel->assignStoreName($temp_response['objects'], $token));
        }

        $final_response = $temp_response;


        $final_response['objects'] = $all_products;

        $this->logger->info(wc_print_r($final_response, true), array('source' => 'dropi-products'));
        if (is_wp_error($final_response)) {
            $error_message = $final_response->get_error_message();
            return $error_message;
            $this->helper->showAdminNotice($error_message, 'error');
            $this->logger->info(wc_print_r($final_response, true), array('source' => 'dropi-products'));
        } else {
            //$response_body = (array)json_decode($final_response['body']);
            $response_body = $final_response;
            $message = '';
            if ($response_body['isSuccess'] == false) {
                if (isset($response_body['message'])) {
                    $message = $response_body['message'];
                }
                if (isset($response_body['error'])) {
                    $message = $response_body['error'];
                }
                if (empty($message)) {
                    $message = $response_body['status'];
                }

                if (isset($response_body['error'])) {
                    $message .= $response_body['error'];
                }
                $this->helper->showAdminNotice($message, 'error');
                $this->logger->info(wc_print_r($response_body, true), array('source' => 'dropi-products'));

                return $message;
            } else {
                // 
                $products  =  $response_body;
            }
        }


        return $products;
    }

    /** get product by sku from woocomerce */
    function get_product_by_sku($sku)
    {

        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));


        if ($product_id) return $product_id;

        return null;
    }



    public function import_product(
        $product,
        $product_name = null,
        $product_description = null,
        $product_price = null,
        $sob_descripcion = null,
        $sob_nombre = null,
        $sob_precio = null,
        $sob_images = null,
        $variationstoimport  = null,
        $productaction = null,
        $productselect  = null,
        $variations = null,
        $chose_variations = null,
        $attributes = null,
        $sob_stock = null,
        $store = null
    ) {



        $success = false;
        $message = '';
        try {



            //busco la data del producto en dropi
            $store_info = null;
            if ($store != null && isset($store[0])) {
                $store_info = $store[0];
                $token = $store_info->token;
            } else {
                $tokens = $this->TokenModel->getTokens();
                if (!empty($tokens) && isset($tokens[0])) {
                    $store_info = $tokens[0];
                    $token = $store_info->token;
                } else {
                    $token = '';
                }
            }

            $product = $this->getProduct($product, $token);

            if ($product->description == null) {
                $product->description = '';
            }

            $name = !empty($product_name) ? $product_name : $product->name;
            $description = !empty($product_description) ? $product_description : $product->description;
            $price = !empty($product_price) ? floatval($product_price) : $product->suggested_price;

            $post = array(
                'post_status' => 'publish',
                'post_type' => "product",
            );


            if ($sob_nombre == 'true' || $sob_nombre == null) {
                $post['post_title'] = $name;
            }
            if ($sob_descripcion == 'true' || $sob_descripcion == null) {
                $post['post_content'] = $description;
            }


            //SI LA ACCION ES SINCRONIZAR CON RPODUCTO EXISTENTE
            if ($productaction === 'SYNC') {

                //post id vendria siendo el id del oprducto en woocmerce
                $post_id = intval($productselect);
            } else {
                $post_id = wp_insert_post($post);
            }



            if (is_int($post_id) && $post_id > 0) {


                //esto es pa crear los atributos si no existen
                if ($attributes != null && sizeof($attributes) > 0) {
                    $this->create_product_attributes($post_id, $attributes);
                }

                //SI TIENE VARIABLES AIMPORTAR
                if ($variationstoimport != null && sizeof($variationstoimport) > 0) {
                    wp_set_object_terms($post_id, 'variable', 'product_type');


                    // The variation data
                    foreach ($variations as $variation) {
                        $variation = (object)$variation;

                        if (in_array($variation->id, $variationstoimport)) {
                            $varianExisttId = false;
                            foreach ($chose_variations as $chose) {
                                if (isset($chose[$variation->id]) && $chose[$variation->id] != null) {

                                    $varianExisttId = $chose[$variation->id];
                                }
                            }
                            // la variable no xiste la creo
                            $variation_data =  array(
                                'sku'           => $variation->sku,
                                'regular_price' => $variation->suggested_price
                            );
                            //sobreescribir stock
                            if ($sob_stock == "true") {
                                $finalStockByWarehouse = 0;

                                if (isset($variation->stock)) {
                                    $variation_data['stock_qty'] = $variation->stock;
                                }

                                if (isset($variation->warehouse_product_variation)) {
                                    foreach ($variation->warehouse_product_variation as $ware) {
                                        $finalStockByWarehouse = $finalStockByWarehouse + $ware->stock;
                                    }
                                    $variation_data['stock_qty'] = $finalStockByWarehouse;
                                }
                            }
                            $attributes = [];
                            $attributes2 = [];
                            foreach ($variation->attribute_values as $attr) {
                                $attr = (object)$attr;
                                $attribute = array(
                                    $attr->attribute_name => $attr->value
                                );
                                $attribute2 = array(
                                    'id' => 0, 'name' =>  $attr->attribute_name, 'option' => $attr->value
                                );
                                $attributes[] = $attribute;
                                $attributes2[] = $attribute2;
                            }
                            $variation_data['attributes'] = $attributes;
                            $variation_data['attributes2'] = $attributes2;


                            $message = $this->create_product_variation($post_id, $variation_data, $variation, $varianExisttId);
                        }
                    }
                } else {
                    wp_set_object_terms($post_id, 'simple', 'product_type');
                }


                // Get term *objects* with name that *matches* "my_name"
                /*$terms = get_terms([
                    'taxonomy' => 'category',
                    'name' => $product->categories[0]->name,
                    'hide_empty' => false,
                ]);*/

                try {
                    $cat_name = $product->categories[0]->name;


                    $category = get_term_by('name', $cat_name, 'product_cat');
                    $category_id = $category->term_id;

                    if (empty($category_id) && !empty($cat_name)) {
                        //creo la categoria si no existe
                        $term = wp_insert_term($cat_name, 'product_cat', array(
                            'description' => $cat_name, // optional
                            'parent' => 0, // optional
                            //'slug' => 'my-new-category' // optional
                        ));

                        if (is_wp_error($term)) {
                            $message .= $term->get_error_message();
                            // $this->helper->showAdminNotice($message, 'error');

                        } else {
                            $category_id = $term['term_id'];
                            wp_set_object_terms($post_id, $category_id, 'product_cat');
                        }
                    }
                } catch (Exception $e) {
                    $message .= $e->getMessage();
                    //$this->helper->showAdminNotice($message, 'error');

                }

                if ($sob_precio == 'true'  || $sob_precio == null) {
                    update_post_meta($post_id, '_price', $price);
                    update_post_meta($post_id, '_regular_price',  $price);
                }


                update_post_meta($post_id, '_sku', $product->sku);


                if ($sob_stock == "true") {

                    if ($variationstoimport != null && sizeof($variationstoimport) > 0) {
                        //no hago nada si es variable para que no me ponga el stock en cero
                        //update_post_meta($post_id, '_manage_stock', false);
                    } else {

                        $stockForSimple = 0;

                        if (isset($product->stock)) {

                            $stockForSimple = $product->stock;
                        }

                        if (isset($product->warehouse_product)) {
                            foreach ($product->warehouse_product as $value) {
                                $stockForSimple = $value->stock + $stockForSimple;
                            }
                        }

                        update_post_meta($post_id, '_stock', $stockForSimple);
                        update_post_meta($post_id, '_manage_stock', true);
                    }
                }


                update_post_meta($post_id, '_dropi_product', serialize($product));
                update_post_meta($post_id, '_dropi_product_id', $product->id);
                $store_name_meta = is_object($store_info) && isset($store_info->store) ? $store_info->store : '';
                $store_token_meta = is_object($store_info) && isset($store_info->token) ? $store_info->token : '';
                update_post_meta($post_id, '_dropi_token_store', $store_name_meta);
                update_post_meta($post_id, '_dropi_token', $store_token_meta);

                // update_post_meta($post_id, '_stock_status', 'instock');
                if ($sob_images == 'true' || $sob_images == null) {

                    try {
                        //mp($product);
                        $this->setPostImages($post_id, $product->photos);
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage(), array('source' => 'dropi-products-images'));
                    }
                }

                $this->setImportedOnImportLits($product, $post_id, $token);
                $success = true;
            } else {

                if (is_wp_error($post_id)) {
                    $error_message = $post_id->get_error_message();
                    $message = $error_message;
                    //$this->helper->showAdminNotice($error_message, 'error');
                    $this->logger->error(wc_print_r($error_message, true), array('source' => 'dropi-products'));
                }

                if (empty($post['post_title'])) {
                    $message = 'El campo nombre es requerido';
                }
            }
        } catch (Exception $e) {
            //$this->helper->showAdminNotice('Error', 'error');
            $message = 'import_product Error ' . $e->getLine() . " " . $e->getMessage();
            $this->logger->error(wc_print_r($e, true), array('source' => 'dropi-products'));
        }
        return ['success' => $success, 'message' => $message];
    }


    private function create_product_attributes($product_id, $attributes)
    {
        try {
            $product = wc_get_product($product_id);
            if (is_object($product)) {
                $attrbiutestoset = [];
                foreach ($attributes as $attr) {

                    $attr = (object)$attr;


                    //busco para ver si el atributo ya existe
                    //$label = wc_attribute_label($attr->description);

                    //otra forma de buscar el attributo con su id
                    $existattr = $product->get_attribute($attr->description);

                    //var_dump($existattr);
                    //si no existe el atributo entonces lo creo
                    //es importante validar si no existe para que no lo sobreescriba en el vincular a producto existente
                    if (empty($existattr)) {
                        $attribute = new WC_Product_Attribute();

                        $attribute->set_id(0);
                        //pa_size slug
                        $attribute->set_name($attr->description);

                        $options = [];

                        if (isset($attr->values)) {
                            foreach ($attr->values as $value) {
                                $options[] = $value['value'];
                            }
                        }

                        //Set terms slugs
                        $attribute->set_options($options);
                        // $attribute->set_position(0);

                        //If enabled
                        $attribute->set_visible(1);

                        //If we are going to use attribute in order to generate variations
                        $attribute->set_variation(1);

                        $attrbiutestoset[] = $attribute;
                    }
                }

                if (sizeof($attrbiutestoset) > 0) {
                    $product->set_attributes($attrbiutestoset);
                }

                if (is_object($product)) {
                    $product->save();
                }
            }
        } catch (Exception $e) {

            echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }
    }
    /**
     * Create a product variation for a defined variable product ID.
     *
     * @since 3.0.0
     * @param int   $product_id | Post ID of the product parent variable product.
     * @param array $variation_data | The data to insert in the product.
     */

    private function create_product_variation($product_id, $variation_data, $dropi_variation, $varianExisttId)
    {
        $create = false;
        $message = '';
        $variation_id = false;
        try {
            // Get the Variable product object (parent)
            $product = wc_get_product($product_id);
            //si ya viene con un variation id quiere quedcir que en el selector selecciono vincular a una variabloe
            //si viene false quiere decir que selecciono crear niueva
            if ($varianExisttId === false) {
                $create = true;
                //lo comento porque en teoria no deberia permitirme modificar una variable que ya tenga ese sku. simplemente deberia botar la alerta
                // pero prmero busco si ya exiuste una variable con ese sku, porque woocomerce no me permite crear variables con sku duplicados
                // $variation_id = $this->get_variant_by_sku($product_id, $variation_data['sku']);
            } else {
                $variation_id = $varianExisttId;
            }

            $default_attributes = [];

            $variation_post = array(
                'post_title'  => $product->get_name(),
                'post_name'   => 'product-' . $product_id . '-variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type'   => 'product_variation',
                'guid'        => $product->get_permalink(),
                //'sku' => $variation_data['sku']
                // 'attributes' =>  $default_attributes

            );

            //$variation_id = $this->get_variant_by_sku($product_id, $variation_data['sku']);

            // si no viene la variable por chosen, y no existe una variable con ese sku, la creo
            if ($variation_id == false) {
                $create = true;
                $variation_id = wp_insert_post($variation_post);
            }

            $variation =  new WC_Product_Variation($variation_id);

            // aqui lo que hago es setar el sku a la variable bien sea nueva o bien sea que exista, pero woocomerce no permite crear variables con el mismo sku asi que explotaria 


            // SKU
            try {
                $existVariantBySku = $this->get_variant_by_sku($product_id, $variation_data['sku']);

                // valido si hay un sku que viene de dropi y si no existe ya una variable con ese sku para entocnes asignarselo
                if (!empty($variation_data['sku']) &&   $existVariantBySku == null)
                    $variation->set_sku($variation_data['sku']);
            } catch (Exception $e) {

                $message = $e->getMessage();
            }

            /**
             * ahora busco los atributos que mande por parametro y se los asigno a la variable
             * pero lo hago solo si es create true, para no sobrescriir los valores de los atributos, y le sirva a cristian trujillo
             */
            foreach ($variation_data['attributes2'] as $attr) {

                $attr = (object)$attr;

                if (!empty($attr->name) && $attr->name != '' && $create === true) {
                    $default_attributes[strtolower($attr->name)] =  strtolower($attr->option);
                    update_post_meta($variation_id, 'attribute_' . strtolower($attr->name), $attr->option);
                }
            }

            //seteo los atributos a la variacion solo si es create
            if ($create === true) {
                $variation->set_default_attributes($default_attributes);
            }


            // aqui hago toda la vuelta de los precios
            if (!empty($variation_data['regular_price'])) {
                if ($create === true) {
                    $variation->set_price($variation_data['regular_price']);
                } else {

                    update_post_meta($variation_id, "_regular_price", $variation_data['regular_price']);
                }
            }
            if (!empty($variation_data['sale_price'])) {
                if ($create === true) {
                    $variation->set_price($variation_data['sale_price']);
                    $variation->set_sale_price($variation_data['sale_price']);
                } else {

                    update_post_meta($variation_id, "_price", $variation_data['sale_price']);
                    update_post_meta($variation_id, "_sale_price", $variation_data['sale_price']);
                }
            }

            $variation->set_regular_price($variation_data['regular_price']);

            // Stock
            if (!empty($variation_data['stock_qty'])) {


                if ($create === true) {
                    $variation->set_manage_stock(true);
                    $variation->set_stock_status('');
                    $variation->set_stock_quantity($variation_data['stock_qty']);
                } else {
                    update_post_meta($variation_id, "_stock", $variation_data['stock_qty']);
                }
            } else {
                $variation->set_manage_stock(false);
            }

            $variation->set_weight(''); // weight (reseting)

            $dropi_variation->warehouse_product_variation = array();

            update_post_meta($variation_id,  '_dropi_variation', serialize($dropi_variation));


            $this->logger->error('productvariation ' . $variation_id, array('source' => 'dropi-products'));
            $this->logger->error(wc_print_r($dropi_variation, true), array('source' => 'dropi-products'));

            $variation->apply_changes(); // Save the data
            $variation->save(); // Save the data
            $variation->save_meta_data(); // Save the data


            $dropi_variation = get_post_meta($variation_id, '_dropi_variation', true);
            //var_dump($dropi_variation);
            // var_dump($variation->get_attributes());
            //var_dump($variation->get_default_attributes());
        } catch (Exception $e) {


            $message = $e->getMessage();
        }


        return  $message;
    }


    /** get variant by sku from woocomerce */
    function  get_variant_by_sku($product_id, $sku)
    {
        $existe = null;
        try {

            $product = new WC_Product_Variable($product_id);



            $current_variations = $product->get_available_variations();

            foreach ($current_variations as $kcurrentvariation) {

                // var_dump($kcurrentvariation);

                if ($kcurrentvariation['sku'] === $sku) {
                    $existe = $kcurrentvariation['variation_id'];
                }
            }
        } catch (Exception $e) {
            //$this->helper->showAdminNotice('Error', 'error');


            $this->logger->error(wc_print_r($e, true), array('source' => 'dropi-products'));
        }


        return $existe;
    }



    private function setImportedOnImportLits($product, $post_id, $id_token)
    {
        try {


            $endpoint = $this->constants->API_URL . "importlist/importstore/1";

            $data = array(
                "products_id" => $product->id,
                "imported_to_store" =>  true,
                "woocomerse_id" =>  $post_id,
                "woocomerse_url" => get_post_field('post_name', $post_id)
            );
            //$id_token = $this->helper->getToken();

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
                    //'Authorization-token' => $id_token,
                ),
                'cookies' => array(),
                'sslverify' => false,
                'method'    => 'PUT'

            );

            $response = wp_remote_request(
                $endpoint,
                $args
            );
            $response_body = (array)json_decode($response['body']);
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();

                $this->logger->error('Error updating import list ', array('source' => 'dropi-products'));
                $this->logger->error(wc_print_r($error_message, true), array('source' => 'dropi-products'));
            } else {
                if (isset($response_body['isSuccess']) && $response_body['isSuccess'] === true) {

                    $this->logger->info('SUCCESS ' . $response_body['message'], array('source' => 'dropi-products'));
                } else {
                    $this->logger->error('ERROR EN PUT IMPORT LIST SHOP' . isset($response_body['message']) ? $response_body['message'] : ' CHECKEAR EN EN EL LOG DEL BACK', array('source' => 'dropi-products'));
                }
            }
        } catch (Exception $e) {
            $this->logger->error('EXCEPTION' . wc_print_r($e, true), array('source' => 'dropi-products'));
        }
    }
    private function setPostImages($post_id, $gallery)
    {
        try {
            if (is_array($gallery) && count($gallery) > 0) {
                // Ensure required media functions are available
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $attachment_ids = array();
                $count = 0;

                foreach ($gallery as $img) {
                    // Determine the correct image URL
                    if (empty($img->urlS3)) {
                        $image_url = $this->constants->IMG_URL . $img->url;
                    } else {
                        $image_url = 'https://d39ru7awumhhs2.cloudfront.net/' . $img->urlS3;
                    }

                    // Download the file to a temporary location
                    $tmp = download_url($image_url, 30);

                    if (is_wp_error($tmp)) {
                        $this->logger->error('download_url error: ' . $tmp->get_error_message(), array('source' => 'dropi-products-images'));
                        continue;
                    }

                    // Determine a sensible filename from the URL
                    $name = basename(parse_url($image_url, PHP_URL_PATH));
                    if (empty($name) || $name === '/') {
                        $name = $img->id . '.jpg';
                    }

                    // Prepare the sideloaded file array
                    $file_array = array(
                        'name'     => sanitize_file_name($name),
                        'tmp_name' => $tmp,
                    );

                    // Sideload the image and get attachment ID
                    $attach_id = media_handle_sideload($file_array, $post_id);

                    // Clean up on failure
                    if (is_wp_error($attach_id)) {
                        @unlink($tmp);
                        $this->logger->error('media_handle_sideload error: ' . $attach_id->get_error_message(), array('source' => 'dropi-products-images'));
                        continue;
                    }

                    // Set featured image as the first attachment
                    if ($count === 0) {
                        set_post_thumbnail($post_id, $attach_id);
                    } else {
                        $attachment_ids[] = $attach_id;
                    }

                    $count++;
                }

                // Assign remaining images to the product gallery
                if (!empty($attachment_ids)) {
                    $existing = get_post_meta($post_id, '_product_image_gallery', true);
                    $existing_ids = !empty($existing) ? explode(',', $existing) : array();
                    $new_ids = array_map('intval', $attachment_ids);
                    $merged = array_filter(array_unique(array_merge($existing_ids, $new_ids)));
                    update_post_meta($post_id, '_product_image_gallery', implode(',', $merged));
                }
            }
        } catch (Exception $e) {

            $this->logger->error('error al crear imagenes', array('source' => 'dropi-products'));
            $this->logger->error(wc_print_r($e, true), array('source' => 'dropi-products'));
        }
    }

    /**
     * Obtener Bodega de un producto
     */

    public function getWarehouse()
    {
        $logger = wc_get_logger();
        $endpoint = $this->constants->API_URL . "warehouses/";

        $warehouse = array();
        //$id_token = $this->helper->getToken();
        $tokens = $this->TokenModel->getTokens();
        $id_token = $tokens[0]->token;

        $args = array(
            //'body' => json_encode($data),
            'timeout' => '100000',
            'redirection' => '5',
            'httpversion' => '1.0',
            'method' => 'GET',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json;charset=UTF-8',
                'dropi-integration-key' =>  $id_token,
            ),
            'cookies' => array(),
            'sslverify' => false,
        );
        $response = wp_remote_get(
            $endpoint,
            $args
        );

        $logger->info('las bodegas ', array('source' => 'dropi-products'));
        $logger->info(wc_print_r($response, true), array('source' => 'dropi-products'));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();

            $this->helper->showAdminNotice($error_message, 'error');
        } else {
            $response_body = (array)json_decode($response['body']);
            if ($response_body['isSuccess'] == false) {

                $this->helper->showAdminNotice($response_body['message'], 'error');
            } else {
                $warehouse  =  $response_body['objects'];
            }
        }

        return $warehouse;
    }

    /**
     * Buscar categorias en Dropi
     */
    public function getCategories()
    {
        $endpoint = $this->constants->API_URL . "categories/";
        $list_categories = array();
        //$id_token = $this->helper->getToken();
        $tokens = $this->TokenModel->getTokens();
        $id_token = $tokens[0]->token;

        $args = array(
            'timeout' => '100000',
            'redirection' => '5',
            'httpversion' => '1.0',
            'method' => 'GET',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json;charset=UTF-8',
                'dropi-integration-key' =>  $id_token,
            ),
            'cookies' => array(),
            'sslverify' => false,
        );

        $response = wp_remote_get(
            $endpoint,
            $args
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();

            $this->helper->showAdminNotice($error_message, 'error');
        } else {
            $response_body = (array)json_decode($response['body']);
            if ($response_body['isSuccess'] == false) {

                $this->helper->showAdminNotice($response_body['message'], 'error');
            } else {
                $list_categories  =  $response_body['objects'];
            }
        }

        return $list_categories;
    }
}
