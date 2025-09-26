<?php

/**
 * Plugin Name: Dropify
 * Description: This plugin allow users to show and import products from dropi on woocomerce
 * Version: 4.6.9
 * Author: Jhainey Perez
 * Text Domain: wc-dropi-integration
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

function create_dropi_tokens_table()
{
    try {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'dropi_tokens';

        $sql = "CREATE TABLE " . $table_name . " (
        id int(11) NOT NULL AUTO_INCREMENT,
        store tinytext NOT NULL,
        token VARCHAR(1000) NOT NULL,
        create_prod_empr BOOLEAN,
        sync VARCHAR(50),
        PRIMARY KEY  (id),
        KEY token (token)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        if (get_option('dropi-woocomerce-token') != null) {
            error_log('entree');
            $token = sanitize_text_field(get_option('dropi-woocomerce-token'));
            $sync = get_option('dropi-woocomerce-autosync_orders');
            $create_prod = get_option('dropi-woocomerce-create_product_if_no_exist');
            $sync_token = '';


            if ($sync == 1) {
                $sync_token = 'AUTOMÁTICAMENTE';
            } else {
                $sync_token = "MANUALMENTE";
            }

            $data_tokens = $wpdb->get_results("SELECT * FROM " . $table_name, OBJECT);

            if ($data_tokens == []) {
                error_log(print_r("data tokens vacio", true));
                $wpdb->insert($table_name, array('store' => 'Tienda 1', 'token' => $token, 'sync' => $sync_token, 'create_prod_empr' => $create_prod));
                $imported_products = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_dropi_product' ", OBJECT);

                foreach ($imported_products as $product) {
                    update_post_meta($product->post_id, '_dropi_token_store', 'Tienda 1');
                    update_post_meta($product->post_id, '_dropi_token', $token);
                }
            }
        }
    } catch (Exception $e) {


        echo $e->getMessage();
        echo $e->getLine();
        echo $e->getFile();
    }
}

register_activation_hook(__FILE__, 'create_dropi_tokens_table');

include_once('clasess/Dropi.php');
add_action('plugins_loaded', function () {
    $Dropi = JPIODFW_Dropi::GetInstance();
    $Dropi->InitPlugin();
});
