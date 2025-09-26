<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once(dirname(__DIR__) . '/clasess/views/Products.php');
include_once(dirname(__DIR__) . '/clasess/models/ProductsModel.php');
include_once(dirname(__DIR__) . '/clasess/models/TokenModel.php');

class JPIODFW_Products
{
    private $helper;
    private $ProducstInstance;
    private static $instance;
    /*......*/
    // class constructor
    public function __construct()
    {
        $this->helper = JPIODFW_Helper::GetInstance();
        $this->ProducstInstance = JPIODFW_ProductsModel::GetInstance();
        $this->TokenModel = JPIODFW_TokenModel::GetInstance();
    }

    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function register_sub_menues()
    {
        $hook = add_submenu_page(
            'dropi',
            'Productos',
            'Productos',
            'manage_options',
            'dropi-products',
            array(&$this, 'products_callback')
        );


        add_action("load-$hook", [$this, 'products_screen_option']);
    }

    /**Render option page */
    public function products_callback()
    {
        $this->helper->checkRequirementes();
        $ProductsView = JPIODFW_ProductsView::GetInstance();
        $ProductsView->getProducts($this->produtct_list);
    }
    /**
     * Screen options
     */
    public function products_screen_option()
    {

        $option = 'per_page';
        $args = [
            'label' => 'Productos',
            'default' => 5,
            'option' => 'products_per_page'
        ];

        add_screen_option($option, $args);

        $this->produtct_list = new JPIODFW_Product_List();
    }

    function my_load_scripts()
    {

        //  wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array());

        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');

        //Add the Select2 JavaScript file
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0');
        wp_enqueue_script('dropi-sweetalert2', plugin_dir_url(__DIR__) . 'js/sweetalert2@11.js', array('jquery'), date('YmdHis'));

        $current_page = isset($_GET["page"])?$_GET["page"]:'';

        if ($current_page == 'dropi-products') {

            wp_enqueue_script('my_js_products', plugin_dir_url(__DIR__) . 'js/product-events.js', array('jquery'), date('YmdHis'));
        }


        wp_localize_script('my_js_products', 'ajax_var', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my-ajax-nonce'),
            'action' => 'get-woo-products'
        )
        );


        $path = plugins_url('wc-dropi-integration/css/bootstrap.css'); //use your path of course
        $dependencies = array(); //add any depencdencies in array
        $version = false; //or use a version int or string
        wp_enqueue_style('dropi-bootstrap', $path, $dependencies, $version);

        $path = plugins_url('wc-dropi-integration/js/bootstrap.min.js'); //use your path of course
        wp_enqueue_script('dropi-bootstrap', $path, $dependencies, $version);
    }


    function my_import_product_event()
    {
        try {
            // Check for nonce security
            $success = true;
            $message = '';
            $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
            $product_name = isset($_REQUEST['product_name']) ? sanitize_text_field($_REQUEST['product_name']) : '';
            $product_description = isset($_REQUEST['product_description']) ? sanitize_textarea_field($_REQUEST['product_description']) : '';
            $product_price = isset($_REQUEST['product_price']) ? sanitize_text_field($_REQUEST['product_price']) : '';
            $sob_descripcion = isset($_REQUEST['sob_descripcion']) ? sanitize_text_field($_REQUEST['sob_descripcion']) : null;
            $sob_nombre = isset($_REQUEST['sob_nombre']) ? sanitize_text_field($_REQUEST['sob_nombre']) : null;
            $sob_precio = isset($_REQUEST['sob_precio']) ? sanitize_text_field($_REQUEST['sob_precio']) : null;
            $sob_images = isset($_REQUEST['sob_images']) ? sanitize_text_field($_REQUEST['sob_images']) : null;
            $sob_stock = isset($_REQUEST['sob_stock']) ? sanitize_text_field($_REQUEST['sob_stock']) : null;
            $variationstoimport = isset($_REQUEST['variationstoimport']) ? (array) $_REQUEST['variationstoimport'] : [];
            $variations = isset($_REQUEST['variations']) ? (array) $_REQUEST['variations'] : [];
            $chose_variations = isset($_REQUEST['chose_variations']) ? (array) $_REQUEST['chose_variations'] : [];
            $attributes = isset($_REQUEST['attributes']) ? (array) $_REQUEST['attributes'] : [];
            $productaction = isset($_REQUEST['productaction']) ? sanitize_text_field($_REQUEST['productaction']) : '';
            $productselect = isset($_REQUEST['productselect']) ? sanitize_text_field($_REQUEST['productselect']) : '';
            $id_store = isset($_REQUEST['store']) ? sanitize_text_field($_REQUEST['store']) : '';

            $store = $this->TokenModel->getTokenById(intval($id_store));

            if (!wp_verify_nonce($nonce, 'sp_import_product')) {
                $success = false;
                $message = 'No nonce';
            }

            if (empty($_REQUEST['product'])) {
                $success = false;
                $message = 'No product';
            }
            $product = isset($_REQUEST['product']) ? absint(sanitize_text_field($_REQUEST['product'])) : 0;

            $result = $this->ProducstInstance->import_product(
                $product,
                $product_name,
                $product_description,
                $product_price,
                $sob_descripcion,
                $sob_nombre,
                $sob_precio,
                $sob_images,
                $variationstoimport,
                $productaction,
                $productselect,
                $variations,
                $chose_variations,
                $attributes,
                $sob_stock,
                $store
            );



            //var_dump( $result);
            $message = $result['message'];
            echo json_encode([
                'success' => $result['success'],
                'message' => $message,
            ]);
            wp_die('', '', [
                'response' => 200
            ]);
        } catch (Exception $e) {
            $logger = wc_get_logger();
            $logger->error('error', array('source' => 'dropi-products'));
            $logger->error(wc_print_r($e, true), array('source' => 'dropi-products'));
        }
    }

    function my_get_woo_products_cb2()
    {
        $productos = [];
        // Check for nonce security
        $nonce = sanitize_text_field($_GET['nonce']);

        if (!wp_verify_nonce($nonce, 'my-ajax-nonce')) {
            die('Busted!');
        }

        $args = array(
            'status' => 'publish',
            'limit' => -1
        );
        $products = wc_get_products($args);

        $productos = array();

        foreach ($products as $product) {

            $newprod = $product->get_data();

            if ($product->get_type() == 'variable') {

                $variations = $product->get_available_variations();


                $newprod['variations'] = $variations;
            }

            $productos[] = $newprod;
        }
        
        echo json_encode($productos);

        wp_die('', '', [
            'response' => 200,

        ]);
    }

    /**funcion que va amostrar en la lista de productos si ya etsa sincronizado con dropi */
    function show_custom_product_column_values($column, $post_id)
    {
        if ('is_dropi_product' == $column) {

            $dropi_product = get_post_meta($post_id, '_dropi_product', true);

            $unserialized = unserialize($dropi_product);
            if (empty($unserialized) || $unserialized == false) {
                $unserialized = json_decode($dropi_product);
            }

            if (!empty($dropi_product)) {

                echo "<span class='dashicons dashicons-yes' style='color:#2bee2b'></span>" . __('Sincronizado', 'wc-dropi-integration');
            } else
                echo '<small><em>' . __("NO SYNC", 'wc-dropi-integration') . '</em></small>';
        }
        if ('dropi_product_id' == $column) {

            $dropi_product = get_post_meta($post_id, '_dropi_product_id', true);

            echo $dropi_product;
        }
        if ('dropi_token_store' == $column){
            $dropi_store = get_post_meta($post_id, '_dropi_token_store', true);
            echo $dropi_store;
        }
    }

    /**funcion que agrega la columna extra a la lista de productos */
    function custom_shop_product_column($columns)
    {

        $reordered_columns = array();

        // Inserting columns to a specific location
        foreach ($columns as $key => $column) {
            $reordered_columns[$key] = $column;
            if ($key == 'featured') {
                // Inserting after "Status" column
                $reordered_columns['is_dropi_product'] = __('Dropi Product', 'wc-dropi-integration');
                $reordered_columns['dropi_product_id'] = __('Dropi ID', 'wc-dropi-integration');
                $reordered_columns['dropi_token_store'] = __('Tienda', 'wc-dropi-integration');
            }
        }
        return $reordered_columns;
    }






    public function init()
    {


        //EL MENU SUPERIOR PARA SELECCIONAR LA SCOLUMNAS
        //add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        //EL MENU LATERAL
        add_action('admin_menu', array(&$this, 'register_sub_menues'));
        //LOS SCRIPTS
        add_action('admin_enqueue_scripts', array(&$this, 'my_load_scripts'));

        add_action('wp_ajax_nopriv_get-woo-products', array(&$this, 'my_get_woo_products_cb2'));
        add_action('wp_ajax_get-woo-products', array(&$this, 'my_get_woo_products_cb2'));


        //PARA EL EVENTO AJAX DE IMPORTAR
        add_action('wp_ajax_nopriv_import', array(&$this, 'my_import_product_event'));
        add_action('wp_ajax_import', array(&$this, 'my_import_product_event'));

        // AGREGA NUEVA COLUMNA A LA LISTA DE ORDENES
        add_filter('manage_edit-product_columns', array($this, 'custom_shop_product_column'), 20);

        //MOSTRAR NUEVA COLUMNA EN LISTA DE PRODUCTS
        add_action('manage_product_posts_custom_column', array($this, 'show_custom_product_column_values'), 20, 2);
    }
}