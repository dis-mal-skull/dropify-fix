<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/models/TokenModel.php');
include_once(dirname(__DIR__) . '/tables/TokenTable.php');

class JPIODFW_TokensView
{
    private static $instance;
    private $helper;

    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    public function __construct()
    {
        $this->helper = JPIODFW_Helper::GetInstance();
    }
    public function getTokenView($tokens_list)
    {
        $requirements = $this->helper->checkRequirementes();
        ?>
        <h1 class="wp-heading-inline">
            Configuración de Tokens</h1>
        <hr class="wp-header-end">

        <div class='wrap'>


            <form method="post" action="options.php">
                <?php settings_fields('dropi-woocomerce-settings'); ?>
                <?php do_settings_sections('dropi-woocomerce-settings'); ?>
                <table class="form-table" style="width: 100%">


                    <tr valign="top">
                        <th scope="row">
                            <?php echo __('Token de autenticación:', 'wc-dropi-integration') ?>
                        </th>
                        <td>
                            <input size="100" type="text" name="dropi-woocomerce-token" id="token"
                                placeholder="Escribe el token generado en Dropi" />
                            <!-- value="<?php echo esc_attr(get_option('dropi-woocomerce-token')); ?>"-->
                        </td>


                    </tr>
                    <tr>
                        <th scope="row">
                            <?php echo __('Nombre de la tienda:', 'wc-dropi-integration') ?>
                        </th>
                        <td>
                            <input size="100" type="text" name="dropi-woocomerce-token" id="token-name"
                                placeholder="Escribe el nombre de la tienda" />
                        </td>
                    </tr>


                </table>

                <a href="javascript:void(0)" class="button"
                    onclick="window.location.href='?page=dropi-settings&token='+jQuery('#token').val()+'&token-name='+jQuery('#token-name').val()">
                    Guardar Token </a>

            </form>


        </div>

        <?php $tokens_list->views(); ?>

        <div id="post-body" style="margin-right: 0;" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <?php

                        $tokens_list->prepare_items();
                        $tokens_list->display(); ?>
                    </form>
                </div>
            </div>
        </div>

        <?php

    }


    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null)
    {
        ?>
        <div class="metabox-holder  columns-2">
            <p>Submitted</p>
        </div>
        <?php
        echo get_submit_button($text, $type, $name, $wrap, $other_attributes);
    }

    function get_submit_button($text, $type, $name, $wrap, $other_attributes)
    {
        ?>
        <div class="metabox-holder  columns-2">
            <h2>Submitted</h2>
        </div>
        <?php
    }

}