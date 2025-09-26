<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/models/TokenModel.php');
include_once(dirname(__DIR__) . '/Constants.php');

class JPIODFW_Token_SetUp extends WP_List_Table
{
    public $constants;
    private $helper;
    private $TokenModel;
    public $all_tokens;

    public function __construct()
    {
        $this->constants = JPIODFW_Constants::GetInstance();
        $this->helper = JPIODFW_Helper::GetInstance();
        parent::__construct([
            'singular' => __('Token', 'wc-dropi-integration'),
            'plural' => __('Tokens', 'wc-dropi-integration'),
            'ajax' => false

        ]);
        $this->TokenModel = JPIODFW_TokenModel::GetInstance();
        $this->all_tokens = $this->TokenModel->getTokens();
    }

    public function prepare_items()
    {
        $this->process_bulk_action();

        $token = $this->TokenModel->getTokens();
        if (!empty($token)) { ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php _e('Listo! tienes tokens configurados', 'wc-dropi-integration'); ?>
                </p>
            </div>
        <?php } else {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <?php _e('Necesitas configurar al menos un token', 'wc-dropi-integration'); ?>
                </p>
            </div>
        <?php
        }

        $last_token = isset($_GET['token']) ? $_GET['token'] : null;
        $last_token_store = isset($_GET['token-name']) ? $_GET['token-name'] : null;
        $sync_dropi = get_option('dropi-woocomerce-autosync_orders');
        $create_prod = isset($_GET['dropi-woocomerce-create_product_if_no_exist']) ? $_GET['dropi-woocomerce-create_product_if_no_exist'] : null;

        if (count($this->TokenModel->does_token_exist($last_token)) < 1 && $last_token != null && $last_token_store != null) {
            if ($sync_dropi == 1) {
                $this->TokenModel->setNewToken($last_token, $last_token_store, $this->constants->SINC_AUTOM, $create_prod);
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php _e('Listo! se ha configurado tu nuevo token', 'wc-dropi-integration'); ?>
                    </p>
                </div>
            <?php
            } else {
                $this->TokenModel->setNewToken($last_token, $last_token_store, $this->constants->SINC_MANUAL, $create_prod);
            }
            wp_safe_redirect('?page=dropi-settings&msg=Token agregado exitosamente');
            exit;
        }
        /*else{
        ?>
        <div class="notice notice-error is-dismissible">
        <p>
        <?php _e('No se pudo guardar tu token. Verifica que la información ingresada sea la correcta.', 'wc-dropi-integration'); ?>
        </p>
        </div>
        <?php
        }*/

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = $this->all_tokens;
    }

    public function get_columns()
    {

        $columns = [
            //'cb'      => '<input type="checkbox" />',
            'store' => __('Tienda', 'wc-dropi-integration'),
            'token' => __('Token', 'wc-dropi-integration'),
          //  'sync' => __('Sincroniza', 'wc-dropi-integration'),
            'icons' => __('Acciones', 'wc-dropi-integration'),
        ];

        return $columns;
    }

    /*function column_cb($item)
    {
    return sprintf(
    '<input type="checkbox" name="bulk-import[]" value="%s" />',
    $item->id
    );
    }*/

    public function column_name()
    {

    }

    public function get_sortable_columns()
    {

    }

    public function get_bulk_actions()
    {
        $actions = [
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }

    public function process_bulk_action()
    {
        if ('delete' === $this->current_action()) {

            // In our file that handles the request, verify the nonce.
            $nonce = sanitize_text_field(($_REQUEST['_wpnonce']));
            $error = false;
            ;
            if (!wp_verify_nonce($nonce, 'sp_delete_token')) {
                die('There is no token to delete');
            } else {
                $result = $this->TokenModel->deleteToken(absint(sanitize_text_field($_GET['token'])));
                //$result = true;

                if ($result === true) {
                    $message = __('Token deleted successfully', 'wc-dropi-integration');
                } else {
                    $error = true;
                    $message = $result;
                }

                wp_safe_redirect('?page=dropi-settings&msg=' . $message . '&error=' . $error);
                exit;
            }
        }

        if (
            (isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
            //|| (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
        ) {
            error_log(print_r('i recognize action', true));
        }
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
        $delete_nonce = wp_create_nonce('sp_delete_token');
        $url = "<a class='btn-dropi-token-delete'  data-item='" . json_encode($item) . "' data-id='" . $item->id . "' title ='Borrar' href='?page=" . sanitize_text_field($_REQUEST['page']) . "&action=delete&token=" . absint($item->id) . "&_wpnonce=" . $delete_nonce . "'><span class='dashicons dashicons-trash'></span></a>";

        return $url;
    }

    public function stores_list()
    {
        $list_stores = $this->TokenModel->getStores();
    }

    public function token_list($item)
    {
        $list_stores = $this->TokenModel->getStores();
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