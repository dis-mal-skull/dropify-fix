<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class JPIODFW_TokenModel{

    private static $instance;
    public $table_name; 
    public $TOKENS;

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

    public function num_total_stores(){
        global $wpdb;
        $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM " . $this->table_name);
        return $rowcount;
    }

    public function does_token_exist($token){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dropi_tokens';
        return $wpdb->get_results("SELECT * FROM " . $this->table_name . " WHERE token = '" . $token . "'", OBJECT);
    }

    public function getTokens(){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dropi_tokens';
        return $wpdb->get_results( "SELECT * FROM " . $this->table_name, OBJECT );
    }

    public function assignStoreName($listProducts, $store){
        for ($i = 0; $i < count($listProducts); $i++){
            $listProducts[$i]->store = $store;
        }
        return $listProducts;
    }

    public function getTokenByStore($store){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dropi_tokens';
        return $wpdb->get_results( "SELECT * FROM " . $this->table_name . " WHERE store = '" . $store . "'", OBJECT );
    }

    public function getTokenById($id){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dropi_tokens';
        return $wpdb->get_results( "SELECT * FROM " . $this->table_name . " WHERE id = '" . $id . "'", OBJECT );
    }

    public function getOldImportedProducts(){
        global $wpdb;
        return $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_dropi_product' ", OBJECT);
    }

    public function deleteToken($id){
        global $wpdb;
        $token = $this->getTokenById($id)[0]->token;
        $value = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = '" . $token . "'", OBJECT);
        foreach($value as $postinfo){
            delete_post_meta($postinfo->post_id,'_dropi_product');
            delete_post_meta($postinfo->post_id,'_dropi_product_id');
            delete_post_meta($postinfo->post_id,'_dropi_token_store');
            delete_post_meta($postinfo->post_id,'_dropi_token');
        }
        $this->table_name = $wpdb->prefix . 'dropi_tokens';
        $wpdb->delete( $this->table_name, array('token' => $token), $where_format = null );
    }

    public function setNewToken($token, $store_name, $sync_dropi, $create_prod){
        //$this->LIST_OF_TOKENS = $this->LIST_OF_TOKENS + $token; 
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dropi_tokens';
        $wpdb->insert($this->table_name, array('store' => $store_name, 'token' => $token,  'sync' => $sync_dropi, 'create_prod_empr' => $create_prod));
    }

}