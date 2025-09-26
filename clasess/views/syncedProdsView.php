<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class JPIODFW_SyncedProdsView
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
    public function getSyncedProdsView($synced_prods_list)
    {
        $requirements = $this->helper->checkRequirementes();
        ?>
        <br>
        <h1 class="wp-heading-inline">
            Productos WooCommerce sincronizados con Dropi </h1>
        <hr class="wp-header-end">

         <?php $synced_prods_list->views(); ?>

        <div id="post-body" style="margin-right: 0;" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <?php

                        $synced_prods_list->prepare_items();
                        $synced_prods_list->display(); 
                        ?>
                    </form>
                </div>
            </div>
        </div>


        <?php
    }

}