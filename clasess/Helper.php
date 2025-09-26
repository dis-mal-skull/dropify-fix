<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/db_tables/token_db_table.php');
include_once(dirname(__DIR__) . '/clasess/models/TokenModel.php');
include_once(dirname(__DIR__) . '/clasess/Constants.php');
// Ensure plugin API functions like is_plugin_active are available
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}


class JPIODFW_Helper
{

    private static $instance;
    public $db_tokens;
    public $tokenModel;
    public $constants;
    /*......*/
    // class constructor
    public function __construct()
    {
        $this->tokenModel = JPIODFW_TokenModel::GetInstance();
        $this->constants = JPIODFW_Constants::GetInstance();
    }

    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }



    public function checkRequirementes()
    {

        /**
         * Check if WooCommerce is active
         **/
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // Put your plugin code here
?>
            <div class="notice notice-warning is-dismissible">
                <p>ATENCIÓN: Woocomerce no esta instalado. Este plugin necesita woocomerce para funcionar.</p>
            </div>
<?php      
        }
        
        //CHECK IF DROPI TOKENS TABLE EXIST ON WORDPRESS. IF NOT, THEN THE TABLE IS CREATED AND INSERT THE ACTUAL TOKEN TO THIS TABLE.
        if (is_plugin_active('wc-dropi-integration/wc-dropi-integration.php')){
            global $wpdb;
            $table_name = $wpdb->base_prefix.'dropi_tokens';
            $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

            if (! $wpdb->get_var($query) == $table_name ) {
                JPIODFW_DropiTokensTable::create_dropi_tokens_table();
                $token = sanitize_text_field(get_option('dropi-woocomerce-token'));
                $sync = get_option('dropi-woocomerce-autosync_orders');
                $create_prod = get_option('dropi-woocomerce-create_product_if_no_exist');
                $sync_token = '';

                if ($sync == 1){
                    $sync_token = $this->constants->SINC_AUTOM;
                }
                else{
                    $sync_token = $this->constants->SINC_MANUAL;
                }
                $this->tokenModel->setNewToken($token, 'Tienda 1', $sync_token, $create_prod);
                $imported_products = $this->tokenModel->getOldImportedProducts();
                foreach ($imported_products as $product){
                    update_post_meta($product->post_id,'_dropi_token_store', 'Tienda 1');
                    update_post_meta($product->post_id,'_dropi_token', $token);
                }
            }
        }
    }

    public function getToken()
    {
        $token = sanitize_text_field(get_option('dropi-woocomerce-token'));
        return $token;
    }

    public function  showAdminNotice($message, $type)
    {
        $adminnotice = new WC_Admin_Notices();


        $adminnotice->add_custom_notice($type, '<p>Dropi: ' . $message . '</p>');

        $adminnotice->output_custom_notices();
    }

    public function init()
    {
    }

   
}
