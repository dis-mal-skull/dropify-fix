<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/Constants.php');

class JPIODFW_SyncedProds extends WP_List_Table
{
    public $constants;
    public $token_data;

    public function __construct()
    {
        $this->constants = JPIODFW_Constants::GetInstance();
        $this->token_data = JPIODFW_TokenModel::GetInstance();

        parent::__construct([
            'singular' => __('Producto sincronizado con Dropi', 'wc-dropi-integration'),
            'plural' => __('Productos sincronizados con Dropi', 'wc-dropi-integration'),
            'ajax' => false

        ]);
    }

    public function prepare_items()
    {
        $this->process_bulk_action();

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = $this->get_synced_data();
    }

    private function get_synced_data(){
        $synced_prods_list = array();

        $all_prods = wc_get_products(array( 
            'limit' => -1,
            'status' => 'publish',
            'meta_key' => '_dropi_product',
        ));

        foreach($all_prods as $product){
            $dropi_data = unserialize(get_post_meta($product->get_ID(),'_dropi_product',true));
            $token_name = $this->token_data->does_token_exist(get_post_meta($product->get_ID(),'_dropi_token',true))[0];
            
            $temp_data = array(
                'woo_id' => $product->get_ID(),
                'woo_prod' => $product->get_name(),
                'dropi_prod' => $dropi_data->name,
                'dropi_prod_id' => $dropi_data->id,
                'token_name' => $token_name->store
            );
            $synced_prods_list = array_merge($synced_prods_list, array($temp_data));
            
        }
        return $synced_prods_list;
    }

    public function get_columns()
    {

        $columns = [
            'woo_id' => __('ID WooCommerce', 'wc-dropi-integration'),
            'woo_prod' => __('Nombre del producto', 'wc-dropi-integration'),
            'dropi_prod' => __('Producto Dropi', 'wc-dropi-integration'),
            'dropi_prod_id' => __('ID Dropi', 'wc-dropi-integration'),
            'token_name' => __('Nombre de la tienda', 'wc-dropi-integration'),
            'icons' => __('Acciones', 'wc-dropi-integration'),
        ];

        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(

            'id' => array('id', true),
            'name' => array('name', true),

        );

        return $sortable_columns;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'icons':
                return $this->column_icons($item);
            default:
                $item = (array) $item;
                return $item[$column_name];
        }
    }

    public function column_icons($item)
    {
        $delete_nonce = wp_create_nonce('sp_delete_dropi_sync');
        $url = "<a class='btn-synced-prod-dropi-delete'  data-item='" . json_encode($item) . "' data-id='" . $item['woo_id'] . "' title ='Borrar' href='?page=" . sanitize_text_field($_REQUEST['page']) . "&action=delete&woo_id=" . $item['woo_id'] . "&_wpnonce=" . $delete_nonce . "'><span class='dashicons dashicons-trash'></span></a>";
        // $url = "<a class='btn-synced-prod-dropi-delete'><span class='dashicons dashicons-trash'></span></a>";

        return $url;
    }

    public function process_bulk_action()
    {
        if ('delete' === $this->current_action()) {

            $nonce = sanitize_text_field(($_REQUEST['_wpnonce']));
            $error = false;
            // ;
            if (!wp_verify_nonce($nonce, 'sp_delete_dropi_sync')) {
                die('No hay productos sincronizados para eliminar');
            } 
            else {
                $prod_id = sanitize_text_field($_GET['woo_id']);
                delete_post_meta($prod_id,'_dropi_product');

                wp_safe_redirect('?page=synced-prods-dropi');
                exit;
            }
        }
    }

    protected function display_tablenav($which)
    {
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">
            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions($which); ?>
            </div>
            <?php
            //$this->extra_tablenav($which);
            $this->pagination($which);
            ?>
            <br class="clear" />
        </div>
    <?php
    }


}