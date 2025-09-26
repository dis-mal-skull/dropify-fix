<?php
class JPIODFW_DropiTokensTable
{
    static function create_dropi_tokens_table()
    {
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
    }
//register_activation_hook(__FILE__, 'create_dropi_tokens_table');
}