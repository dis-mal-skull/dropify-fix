<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once(dirname(__DIR__) . '/clasess/views/settings.php');
include_once(dirname(__DIR__) . '/clasess/models/TokenModel.php');

class  JPIODFW_Settings{
    private static $instance;
    private $SettingsInstance;
    private $helper;

    public function __construct()
    {
        $this->helper = JPIODFW_Helper::GetInstance();   
        $this->SettingsInstance = JPIODFW_TokenModel::GetInstance();
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
            'Settings',
            'Tokens',
            'manage_options',
            'dropi-settings',
            array(&$this, 'token_view_callback')
        );


        add_action("load-$hook", [$this, 'settings_screen_option']);
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }
    public function token_view_callback(){
        $this->helper->checkRequirementes();
        $tokensView = JPIODFW_TokensView::GetInstance();
        $tokensView->getTokenView($this->tokens_list);
    }

    public function settings_screen_option(){
        $option = 'per_page';
        $args = [
            'label' => 'Tokens',
            'default' => 5,
            'option' => 'settings_per_page'
        ];

        add_screen_option($option, $args);

        $this->tokens_list = new JPIODFW_Token_SetUp();
    }

    function my_load_scripts()
    {

      //  wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array());
   
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');

        //Add the Select2 JavaScript file
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0');

     
        wp_enqueue_script('dropi-sweetalert2', plugin_dir_url(__DIR__) . 'js/sweetalert2@11.js', array('jquery'), date('YmdHis'));

        $path = plugins_url('wc-dropi-integration/css/bootstrap.css'); //use your path of course
        $dependencies = array(); //add any depencdencies in array
        $version = false; //or use a version int or string
        wp_enqueue_style('dropi-bootstrap', $path, $dependencies, $version);

        $path = plugins_url('wc-dropi-integration/js/bootstrap.min.js'); //use your path of course
        $dependencies = array(); //add any depencdencies in array
        $version = false; //or use a version int or string
        wp_enqueue_script('dropi-bootstrap', $path, $dependencies, $version);
    }

    public function init(){
        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        //EL MENU LATERAL
        add_action('admin_menu', array(&$this, 'register_sub_menues'));
        //LOS SCRIPTS
        //add_action('admin_enqueue_scripts', array(&$this, 'my_load_scripts'));

    }

}