<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once('Woocomerce.php');
include_once('Products.php');
include_once('Settings.php');
include_once('Helper.php');
include_once('SyncedInfo.php');
include_once('StatesPlaces.php');
class JPIODFW_Dropi
{

    private $helper;
    private static $instance;
    /*......*/
    // class constructor
    public function __construct()
    {
        $this->helper = JPIODFW_Helper::GetInstance();
        add_action('admin_init', array($this, 'register_dropi_plugin_settings'));
        add_action('admin_enqueue_scripts', array($this, 'am_enqueue_admin_styles'));
        add_action('admin_menu', array($this, 'PluginMenu'));
    }

    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function PluginMenu()
    {

        /**Adding dropi menu to admin */

        add_menu_page(
            'Dropify',
            'Dropify',
            'manage_options',
            'dropi',
            array($this, 'RenderPage'),
            plugins_url('/img/icon.png', __DIR__),
            40
        );
    }

    public function RenderPage()
    {

        $requirements = $this->helper->checkRequirementes();
        ?>

        <div class='wrap'>
            <h2>Dropi - Ajustes generales</h2>

            <form method="post" action="options.php">
                <?php settings_fields('dropi-woocomerce-settings'); ?>
                <?php do_settings_sections('dropi-woocomerce-settings'); ?>
                <table class="form-table" style="width: 100%">


                    <tr valign="top">
                        <th scope="row">

                            Sincronizar ordenes automàticamente

                        </th>
                        <th>


                            <input type="checkbox" value="1" id="dropi-woocomerce-autosync_orders"
                                name="dropi-woocomerce-autosync_orders" <?php if (get_option('dropi-woocomerce-autosync_orders') == 1)
                                    echo 'checked="checked"';
                                ?> />
                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            Crear producto en dropi si no existe (Solo para perfil emprendedor)
                        </th>
                        <th>
                            <input type="checkbox" value="1" id="dropi-woocomerce-create_product_if_no_exist"
                                name="dropi-woocomerce-create_product_if_no_exist" <?php if (get_option('dropi-woocomerce-create_product_if_no_exist') == 1)
                                    echo 'checked="checked"'; ?> />
                        </th>
                    </tr>

                    <!-- hacemos parametrizxable la seleccion de ciudades y departamnetos -->
                    <tr valign="top">
                        <th scope="row">
                            Selector de ciudades y departamentos
                        </th>
                        <th>
                            <select id="dropi-woocomerce-deactive_cities_and_departments"
                                name="dropi-woocomerce-deactive_cities_and_departments">

                                <option value="SI" <?php if (get_option('dropi-woocomerce-deactive_cities_and_departments') != 'NO')
                                    echo 'selected'; ?>>ACTIVAR</option>
                                <option value="NO" <?php if (get_option('dropi-woocomerce-deactive_cities_and_departments') == 'NO')
                                    echo 'selected'; ?>>DESACTIVAR</option>
                            </select>

                        </th>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            Actualizar periodicamente el stock de los productos sincronizados
                        </th>
                        <th> <!-- Checkbox to authorize woocommerce to update periodically the stock of synced products -->
                            <input type="checkbox" value="1" id="dropi-woocomerce-sync_prods_stock"
                                name="dropi-woocomerce-sync_prods_stock" <?php if (get_option('dropi-woocomerce-sync_prods_stock') == 1)
                                    echo 'checked="checked"'; ?> />
                        </th>
                    </tr>

        </div>
        </table>

        <?php submit_button(); ?>

        </form>
        </div>
        <?php

    }



    function am_enqueue_admin_styles()
    {

        wp_enqueue_style('main-styles', plugins_url('../css/styles.css', __FILE__));

        wp_enqueue_style('dashicons');
    }
    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    //settings globales del plugin
    public function register_dropi_plugin_settings()
    {
        //register our settings
        register_setting('dropi-woocomerce-settings', 'dropi-woocomerce-token');
        register_setting('dropi-woocomerce-settings', 'dropi-woocomerce-autosync_orders');
        register_setting('dropi-woocomerce-settings', 'dropi-woocomerce-create_product_if_no_exist');
        register_setting('dropi-woocomerce-settings', 'dropi-woocomerce-deactive_cities_and_departments');
        register_setting('dropi-woocomerce-settings', 'dropi-woocomerce-sync_prods_stock');
    }

    function update_stock_job() {
        
        $all_prods = wc_get_products(array( 
            'limit' => -1,
            'status' => 'publish',
            'meta_key' => '_dropi_product',
        ));

        foreach($all_prods as $product){
            $dropi_data = unserialize(get_post_meta($product->get_ID(),'_dropi_product',true));
            $dropi_token = get_post_meta($product->get_ID(),'_dropi_token',true);
            $new_stock_prod = $this->dropiProdUpdated($dropi_data->id, $dropi_token);

            if ($dropi_data->type == 'SIMPLE' && $product->is_type('simple')){
                $product->set_stock($new_stock_prod->stock);
            }
            else if ($dropi_data->type == 'VARIABLE' && $product->is_type('variable')){
                $dropi_token = get_post_meta($product->get_ID(),'_dropi_token',true);
                $all_variations = $product->get_available_variations();

                foreach($all_variations as $prod_var){
                    $dropi_var = unserialize(get_post_meta($prod_var['variation_id'],'_dropi_variation',true));
                    $variation_to_edit = wc_get_product($prod_var['variation_id']);
                    
                    // $key_var = array_search($dropi_var->id, array_column($new_stock_prod->variations,'key'));
                    foreach ( $new_stock_prod->variations as $new_stock_var){
                        if ($new_stock_var->id == $dropi_var->id){
                            $variation_to_edit->set_stock_quantity(floatval($new_stock_var->stock));
                            $variation_to_edit->save();
                        }
                    }

                }
            }
            
        }
    }

    function dropiProdUpdated($dropi_id, $dropi_token){
        $endpoint = $this->helper->constants->API_URL . "products/" . $dropi_id;

        $args = array(
            'timeout' => '100000',
            'redirection' => '5',
            'httpversion' => '1.0',
            'method' => 'GET',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json;charset=UTF-8',
                'dropi-integration-key' =>  $dropi_token,
            ),
            'cookies' => array(),
            'sslverify' => false,
        );

        $response = wp_remote_get(
            $endpoint,
            $args
        );
        if (is_wp_error($response)) {
            error_log('Error getting dropi product');
            $error_message = $response->get_error_message();
            return $error_message;
            $this->helper->showAdminNotice($error_message, 'error');
        }
        $temp_response = (array)json_decode($response['body']);

        if ($temp_response['isSuccess'] == true) {
            $prod_updated = $temp_response;
            return $prod_updated['objects'];
        }
        else{
            return [];
        }
    }

    function create_cron_jobs($schedules)
    {
        // wp_clear_scheduled_hook('update_stock_event');
        $schedules['every_four_hours'] = array(
            'interval' => 14400, // X min * 60 seg
            'display' => 'Every Four Hours',
        );
        return $schedules;
    }

    public function InitPlugin()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {


            $WoocomerceInstante = JPIODFW_Woocomerce::GetInstance();
            $WoocomerceInstante->init();

            $TokensInstance = JPIODFW_Settings::GetInstance();
            $TokensInstance->init();

            $ProductsInstante = JPIODFW_Products::GetInstance();
            $ProductsInstante->init();

            $SyncedInfo = JPIODFW_SyncedInfo::GetInstance();
            $SyncedInfo->init();

            $deactive_cities_and_departments = sanitize_text_field(get_option('dropi-woocomerce-deactive_cities_and_departments'));

            $update_prods_synced = sanitize_text_field(get_option('dropi-woocomerce-sync_prods_stock'));

            if ($update_prods_synced == 1) {
                // Function to update synced products periodically. With this I could create a cron and running as schedule.
                add_filter('cron_schedules', array($this,'create_cron_jobs'));

                if (!wp_next_scheduled('update_stock_event')){
                    wp_schedule_event(time(), 'every_four_hours', 'update_stock_event');
                }
                add_action('update_stock_event', array($this,'update_stock_job'));
            }


            /**chequeo si ya existe el plugin de departamentos y ciudades de saul morales y si no, instancio el nuestro , o si esta activo el mostrar ciudades y departamentos*/
            if (
                !in_array('departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
                && $deactive_cities_and_departments != 'NO'
            ) {
                /**
                 * Departamentos y ciudades inspirados en saul morales
                 */
                $StatsPlacesInstace = new JPIODFW_WC_States_Places_Colombia_Dropi(__FILE__);
                $StatsPlacesInstace->init();
            }
        }
    }
}