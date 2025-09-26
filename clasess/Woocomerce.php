<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/clasess/models/OrdersModel.php');
include_once('Helper.php');
class JPIODFW_Woocomerce
{

    private static $instance;
    /*......*/
    // class constructor
    public function __construct()
    {
    }

    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Muestro el id del producto en dropi
     */
    public function showProductDropiId($html, $product)
    {
        $dropi_product = get_post_meta($product->get_ID(), '_dropi_product', true);

        $unserialized = unserialize($dropi_product);
        if (empty($unserialized) || $unserialized == false) {
            $unserialized = json_decode($dropi_product);
        }
        $html .= '<p class="my-restock-notice">' . var_dump($unserialized) . '</p>';

        return $html;
    }

    // define the woocommerce_order_status_completed callback 
    /**
     * Cuando la orden pasa a status completado paso a crear la orden den dorpisa
     */
    function woocommerce_order_status_changed($order_id)
    {
        $order = new WC_Order($order_id);

        $logger = wc_get_logger();
        $logger->info('status', array('source' => 'dropi-products'));
        $logger->info($order->get_status(), array('source' => 'dropi-products'));
        //global $items;


        if ($order->get_status() == 'processing') {

            /**verifico si tiene sincronizado el autosyncs */
            $autozync = sanitize_text_field(get_option('dropi-woocomerce-autosync_orders'));

            if ($autozync == 1) {
                // call dropi orders creator
                $OrdersModel = JPIODFW_OrdersModel::GetInstance();
                $OrdersModel->save($order);
            }
        }
        if ($order->get_status() == 'canceled ') {

            // call dropi orders creator
            /*$OrdersModel = JPIODFW_OrdersModel::GetInstance();
            $OrdersModel->save($order);*/
        }
    }

    /**funcion que muestra la columna extra en ordenes para sasber si la orden incluye productos dropi */
    function show_custom_order_column_values($column, $post_id)
    {
        if ('is_dropi_order' == $column) {

            $_is_dropi_order = get_post_meta($post_id, '_is_dropi_order', true);
            if ($_is_dropi_order === 'Yes')
                echo "<span class='dashicons dashicons-yes' style='color:#2bee2b'></span>" . __('Orden sicronizada con Dropi', 'wc-dropi-integration');
            elseif (!empty($_is_dropi_order)) {

                echo $_is_dropi_order;
            } else
                echo '<small><em>' . __("NO SYNC", 'wc-dropi-integration') . '</em></small>';
        }
        if ('dropi_order_id' == $column) {

            $_is_dropi_order = get_post_meta($post_id, '_dropi_order_id', true);
            echo $_is_dropi_order;
        }
        if ('shipping_guide' == $column) {
            $_shipping_guide = get_post_meta($post_id, 'shipping_guide', true);
            echo $_shipping_guide;
        }
    }
    /**FUNCION QUE AGREGA EL NUEVO BOTON EN LA COLUMNA ACTIONS */
    function add_custom_order_status_actions_button($actions, $order)
    {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $_is_dropi_order = get_post_meta($order_id, '_is_dropi_order', true);


        if ($_is_dropi_order != 'Yes') {

            // The key slug defined for your action button
            $action_slug = 'sync-to-dropi';


            // Set the action button
            $actions[$action_slug] = array(
                'url' => wp_nonce_url(admin_url('admin-ajax.php?action=send_order_to_dropi&order_id=' . $order_id), 'send-order-to-dropi'),
                'name' => __('Send to dropi', 'wc-dropi-integration'),
                'action' => $action_slug,
            );
        }
        return $actions;
    }



    function send_order_to_dropi()
    {

        // Check for nonce security
        $success = true;
        $message = '';
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);

        if (!wp_verify_nonce($nonce, 'send-order-to-dropi')) {
            $success = false;
            $message = 'No nonce';
        }

        if (empty($_REQUEST['order_id'])) {
            $success = false;
            $message = 'No order id';
        }

        $order_id = absint(wp_unslash(sanitize_text_field($_GET['order_id'])));
        if (!is_numeric($order_id)) {
            wp_safe_redirect((wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=shop_order')));
            exit;
        }

        $get_post_type =  get_post_type(absint(wp_unslash($_GET['order_id'])));
        if (
            //current_user_can('send_order_to_dropi') &&
            check_admin_referer('send-order-to-dropi') &&
            isset($_GET['order_id'])
            && ($get_post_type  === 'shop_order' || $get_post_type  === 'shop_order_placehold')
        ) {
            $order = wc_get_order($order_id);
            //TODO: PRUEBA CAMBIANDO EL STATUS A COMPLETADO
            if (is_a($order, 'WC_Order')) {
                // $status = "completed";
                //$order->update_status($status, 'Payment gateway check', true);
                $OrdersModel = JPIODFW_OrdersModel::GetInstance();
                $result = $OrdersModel->save($order);
            }
        }
        // wp_safe_redirect((wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=shop_order')));
        //exit;
        $success = $result === true ? true : false;
        echo json_encode([
            'success' => $success,
            'message' => $success === true ? $message : $result,
        ]);
        wp_die($message, '', [
            'response' => 200
        ]);
    }

    /**funcion que agrega la columna extra a la lista de ordenes */
    function custom_shop_order_column($columns)
    {
        $reordered_columns = array();

        // Inserting columns to a specific location
        foreach ($columns as $key => $column) {
            $reordered_columns[$key] = $column;
            if ($key == 'order_status') {
                // Inserting after "Status" column
                $reordered_columns['is_dropi_order'] = __('Dropi', 'wc-dropi-integration');
                $reordered_columns['dropi_order_id'] = __('ID Dropi', 'wc-dropi-integration');
                $reordered_columns['shipping_guide'] = __('# Guia', 'wc-dropi-integration');
            }
        }
        return $reordered_columns;
    }

    function add_custom_order_status_actions_button_css()
    {
        $action_slug = "sync-to-dropi"; // The key slug defined for your action button

        echo '<style>.wc-action-button-' . $action_slug . '::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
    }

    function my_load_scripts()
    {
        wp_enqueue_script('my_js_orders', plugin_dir_url(__DIR__) . 'js/order-events.js', array('jquery'), date('Ymdhis'));

        wp_localize_script(
            'my_js_orders',
            'ajax_var',
            array(
                'url' => admin_url('admin-ajax.php'),

            )
        );

        wp_register_script('sweetalert2', '//cdn.jsdelivr.net/npm/sweetalert2@11', null, null, true);
        wp_enqueue_script('sweetalert2');
    }


    // Adding to admin order list bulk dropdown a custom action 'custom_downloads'

    function downloads_bulk_actions_cyns_orders($actions)
    {
        $actions['sync_dropi'] = __('Enviar a dropi', 'woocommerce');
        return $actions;
    }

    function downloads_handle_bulk_action_sync_shop_order($redirect_to, $action, $post_ids)
    {
        if ($action !== 'sync_dropi')
            return $redirect_to; // Exit

        $processed_ids = array();

        foreach ($post_ids as $post_id) {
            $order = wc_get_order($post_id);
            //$order_data = $order->get_data();


            //TODO: PRUEBA CAMBIANDO EL STATUS A COMPLETADO
            if (is_a($order, 'WC_Order')) {
                // $status = "completed";
                //$order->update_status($status, 'Payment gateway check', true);
                $OrdersModel = JPIODFW_OrdersModel::GetInstance();
                $result = $OrdersModel->save($order);
            }

            $processed_ids[] = $post_id;
        }

        //   wp_safe_redirect('?post_type=shop_orders&processed_count=' . count($processed_ids)."&processed_ids=".implode(',', $processed_ids)."&write_downloads=1");


        return $redirect_to = add_query_arg(
            array(
                'write_downloads' => '1',
                'processed_count' => count($processed_ids),
                'processed_ids' => $processed_ids,
            ),
            $redirect_to
        );
    }


    function downloads_bulk_action_admin_notice()
    {
        if (empty($_REQUEST['write_downloads']))
            return; // Exit

        $count = intval($_REQUEST['processed_count']);

        printf('<div id="message" class="updated fade"><p>' .
            _n(
                'Processed %s Order for sync.',
                'Processed %s Orders for sync.',
                $count,
                'write_downloads'
            ) . '</p></div>', $count);
    }


    public function init()
    {
        //AÑADO ESTILOS
        add_action('admin_head', array($this, 'add_custom_order_status_actions_button_css'));
        //MODIFICO LA COLUMNA ACTIONS DE WOOCOMERCE PARA AÑADIR UN NUEVO BOTON
        add_filter('woocommerce_admin_order_actions', array($this, 'add_custom_order_status_actions_button'), 100, 2);

        //la accion de enviar a dorpi con ajax
        add_action('wp_ajax_send_order_to_dropi', array($this, 'send_order_to_dropi'));
        add_action('wp_ajax_nopriv_import', array(&$this, 'send_order_to_dropi'));
        //EJECUTA UNA FUNCION CUANDO LA ORDEN SE PASAS A COMPLETADA
        //TODO, SERIA BACANO QUE EL USUARIO SELECCIONARA EN QUE STATUS QUIERE QUE S EHAGA ESTO
        add_action('woocommerce_order_status_changed', array($this, 'woocommerce_order_status_changed'), 1);
        // add_action('woocommerce_order_status_completed', array($this, 'action_woocommerce_order_status_completed'), 1);
        // add_filter('woocommerce_get_stock_html', array($this, 'showProductDropiId'), 10, 2);

        //MOSTRAR NUEVA COLUMNA EN LISTA DE ORDENES
        add_action('manage_shop_order_posts_custom_column', array($this, 'show_custom_order_column_values'), 20, 2);

        // AGREGA NUEVA COLUMNA A LA LISTA DE ORDENES
        add_filter('manage_edit-shop_order_columns', array($this, 'custom_shop_order_column'), 20);
        //LOS SCRIPTS
        add_action('admin_enqueue_scripts', array(&$this, 'my_load_scripts'));

        //accion masvia enviar adropi
        add_filter('bulk_actions-edit-shop_order', array(&$this, 'downloads_bulk_actions_cyns_orders'), 20, 1);

        // Make the action from selected orders
        add_filter('handle_bulk_actions-edit-shop_order', array($this, 'downloads_handle_bulk_action_sync_shop_order'), 10, 3);

        // The results notice from bulk action on orders
        add_action('admin_notices', array($this, 'downloads_bulk_action_admin_notice'));
    }
}
