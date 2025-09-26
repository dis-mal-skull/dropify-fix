<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once(dirname(__DIR__) . '/models/ProductsModel.php');
include_once(dirname(__DIR__) . '/Constants.php');
include_once(dirname(__DIR__) . '/models/TokenModel.php');

class JPIODFW_Product_List extends WP_List_Table
{

    public $logger;
    public $ProducstInstance;
    public $constants;
    public $TokenModel;
    public $tokens;
    public $stores_per_product;

    /** Class constructor */
    public function __construct()
    {
        $this->logger = wc_get_logger();
        $this->constants = JPIODFW_Constants::GetInstance();
        $this->TokenModel = JPIODFW_TokenModel::GetInstance();
        $this->tokens = $this->TokenModel->getTokens();

        parent::__construct([
            'singular' => __('Producto', 'wc-dropi-integration'), //singular name of the listed records
            'plural' => __('Productos', 'wc-dropi-integration'), //plural name of the listed records
            'ajax' => false //should this table support ajax?

        ]);
        $this->ProducstInstance = JPIODFW_ProductsModel::GetInstance();
    }
    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $logger = wc_get_logger();
        try {
            if ((isset($_GET['orderby']) && !is_string($_GET['orderby']))
                || isset($_GET['order']) && !is_string($_GET['order'])
            ) {
                exit;
            }


            $onlyVerifiedUsers = isset($_GET['userVerified']) ? $_GET['userVerified'] : null;
            $filter_have_stock = isset($_GET['filter_have_stock']) &&  $_GET['filter_have_stock'] === 'ONLY_HAVE_STOCK' ? $_GET['filter_have_stock'] : null;
            $filter_have_description = isset($_GET['filter_have_description']) &&  $_GET['filter_have_description'] === 'ONLY_HAVE_DESCRIPTION' ? $_GET['filter_have_description'] : null;
            $category_filter = isset($_GET['category_filter']) ? $_GET['category_filter'] : null;
            $warehouses_filter = isset($_GET['warehouses_filter']) ? $_GET['warehouses_filter'] : null;
            $store_filter = isset($_GET['store_filter']) ? $_GET['store_filter'] : null;

            $this->_column_headers = $this->get_column_info();

            /** Process bulk action */
            $this->process_bulk_action();

            $per_page     = $this->get_items_per_page('products_per_page', 10);
            $current_page = $this->get_pagenum();

            $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
            // Si no se especifica columna, por defecto el título
            $orderby = (isset($_GET['orderby']) && !empty($_GET['orderby'])) ? sanitize_text_field($_GET['orderby']) : 'id';
            // Si no hay orden, por defecto asendente
            $order = (isset($_GET['order']) && !empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'asc';

            $logger->info(wc_print_r('$per_page ' . $per_page, true), array('source' => 'dropi-products'));
            $logger->info(wc_print_r('$current_page ' . $current_page, true), array('source' => 'dropi-products'));

            $startData = 0;
            if ($current_page > 1) {
                $startData = ($current_page * $per_page);
            }
            $products = $this->ProducstInstance->getProducts(
                $per_page,
                $startData,
                $search,
                $orderby,
                $order,
                $onlyVerifiedUsers,
                $filter_have_stock,
                $filter_have_description,
                $category_filter,
                $warehouses_filter,
                $store_filter,
                $this->tokens
            );


            //var_dump($this->ProducstInstance->getShopInfo());

            $logger->info(wc_print_r($products, true), array('source' => 'dropi-products'));

            if (is_array($products) && sizeof($products) > 0) {
                $total_items  = $products['count'];
                $this->set_pagination_args([
                    'total_items' => 9999, //WE have to calculate the total number of items
                    'per_page'    => $per_page //WE have to determine how many items to show on a page
                ]);
                //var_dump($products);
                $this->items = $products['objects'];
            }
        } catch (Exception $e) {
            $logger->info(wc_print_r('Exception: ' . $e, true), array('source' => 'dropi-products'));
        }
    }
    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'id'    => __('ID', 'wc-dropi-integration'),
            'sku'    => __('SKU', 'wc-dropi-integration'),
            'img'    => __('Imagen', 'wc-dropi-integration'),
            'name'    => __('Nombre', 'wc-dropi-integration'),
            'sale_price'    => __('Precio', 'wc-dropi-integration'),
            'suggested_price'    => __('Precio sugerido', 'wc-dropi-integration'),
            'stock'    => __('Stock', 'wc-dropi-integration'),
            'category'    => __('Categoria', 'wc-dropi-integration'),
            // 'imported'    => __('Importado', 'wc-dropi-integration'),
            'warehouse'    => __('Bodega', 'wc-dropi-integration'),
            'store'    => __('Tienda', 'wc-dropi-integration'),
            'icons'    => __('Actions', 'wc-dropi-integration'),


        ];

        return $columns;
    }
    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_name($item)
    {

        $actions = [];
        $import_nonce = wp_create_nonce('sp_import_product');

        $title = '<strong>' . $item->name . '</strong>';
        $clear = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($item->description))))));
        $item->description = $clear;

        try {
            $actions = [
                'import' => "<a class='btn-dropi-import' data-item='" . json_encode($item) . "' data-description='" . $clear . "' data-price='" . $item->suggested_price . "' data-id='" . $item->id . "' data-store='" . $item->store->id . "' data-name='" . $item->name .  "'  href='?page=" . sanitize_text_field($_REQUEST['page']) . "&action=import&product=" . absint($item->id) . "&store=" . $item->store->id . "&_wpnonce=" . $import_nonce . "'>Importar</a>"
            ];
        } catch (Exception $e) {

            var_dump($e);
        }


        return $title . $this->row_actions($actions);
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(

            'id' => array('id', true),
            'name' => array('name', true),
            'sku' => array('sku', true),
            'sale_price' => array('sale_price', true),
            'suggested_price' => array('suggested_price', true),
            'stock' => array('stock', true),
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'bulk-import' => 'Importar'
        ];

        return $actions;
    }
    public function process_bulk_action()
    {

        //Detect when a bulk action is being triggered...
        if ('import' === $this->current_action()) {

            // In our file that handles the request, verify the nonce.
            $nonce = sanitize_text_field(($_REQUEST['_wpnonce']));
            $error = false;;
            if (!wp_verify_nonce($nonce, 'sp_import_product')) {
                die('Go get a life script kiddies');
            } else {
                $result =    $this->ProducstInstance->import_product(absint(sanitize_text_field($_GET['product'])));


                if ($result === true) {
                    $message = __('Successfully imported product', 'wc-dropi-integration');
                } else {
                    $error = true;
                    $message = $result;
                }

                wp_safe_redirect('?page=dropi-products&msg=' . $message . '&error=' . $error);
                exit;
            }
        }

        // If the import bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-import')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-import')
        ) {

            $import_ids = isset($_POST['bulk-import']) ? (array) $_POST['bulk-import'] : array();

            // loop over the array of record IDs and import them
            foreach ($import_ids as $id) {
                $this->ProducstInstance->import_product($id);
            }

            $message = __('Successfully imported products', 'wc-dropi-integration');
            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_safe_redirect('?page=dropi-products&msg=' . $message);

            exit;
        }
    }

    /** Text displayed when no customer data is available */
    public function no_items()
    {
        _e('No hay productos disponibles.', 'sp');
    }


    public function colum_img($item)
    {

        $img =   isset($item->gallery[0]) ? $item->gallery[0] : [];
        if (isset($img->urlS3) && !empty($img->urlS3)) {
            $url =  'https://d39ru7awumhhs2.cloudfront.net/' . $img->urlS3;

            $img = '<img style="cursor:pointer" class="img-dropi-import" width="50px" src="' . $url . '" data-src="' . $url . '"/>';
        } else if (isset($img->url)) {
            $url =  $this->constants->IMG_URL . $img->url;

            $img = '<img style="cursor:pointer" class="img-dropi-import" width="50px" src="' . $url . '" data-src="' . $url . '"/>';
        } else {
            $url = plugin_dir_url(dirname(__DIR__)) . 'img/avatar.png';
            $img = $img = '<img style="cursor:pointer" class="img-dropi-import" width="50px" src="' . $url . '" data-src="' . $url . '"/>';
        }
        return $img;
    }

    public function list_column_categories($item)
    {
        $cat = '';
        $list_cat = isset($item->categories) ? $item->categories : [];
        if (count($list_cat) > 1) {
            for ($i = 0; $i < count($list_cat); $i++) {
                if ($i == count($list_cat) - 1)
                    $cat = $cat . $list_cat[$i]->name;
                else
                    $cat = $cat . $list_cat[$i]->name . ", ";
            }
        } else {
            $cat =  isset($item->categories[0]->name) ? $item->categories[0]->name : '';
        }


        return $cat;
    }

    public function column_warehouse($item)
    {
        $warehouse = $item->user->name;
        return $warehouse;
    }

    public function colum_category($item)
    {


        $cat =   isset($item->categories[0]) ? $item->categories[0] : [];


        return isset($cat->name) ? $cat->name : '';
    }
    public function colum_icons($item)
    {

        $import_nonce = wp_create_nonce('sp_import_product');
        $clear = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($item->description))))));
        $item->description = $clear;
        $url = "<a class='btn-dropi-import'  data-item='" . json_encode($item) . "' data-description='" . $clear . "' data-price='" . $item->suggested_price . "' data-id='" . $item->id . "' data-name='" . $item->name . "' data-store='" . $item->store->id . "' title ='Importar' href='?page=" . sanitize_text_field($_REQUEST['page']) . "&action=import&product=" . absint($item->id) . "&_wpnonce=" . $import_nonce . "'><span class='dashicons dashicons-database-import'></span></a>";


        return $url;
    }
    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        // aqui se estructura la data que será renderizada en los td de la tabla productos
        switch ($column_name) {

            case 'img':
                return $this->colum_img($item);

            case 'category':
                return $this->list_column_categories($item);
                //return $this->colum_category($item);
            case 'sale_price':

                return $this->price_colum($item);
            case 'suggested_price':
                $item = (array)$item;
                $price = floatval($item[$column_name]);
                return number_format($price, 0, ',', '.');
            case 'stock':
                $item = (array)$item;

                if ($item['type'] == 'SIMPLE') {

                    if (isset($item[$column_name])) {
                        $finalstock = intval($item[$column_name]);
                    }

                    if (isset($item['warehouse_product'])) {
                        $counter = 0;
                        foreach ($item['warehouse_product'] as $warehouse) {
                            $counter = $counter + intval($warehouse->stock);
                        }

                        $finalstock = $counter;
                        $item['stock']  = $finalstock;
                    }
                } else {
                    $finalstock = 0;

                    if (sizeof($item['variations'])) {

                        $stock = 0;
                        $CountStockWareh = 0;
                        foreach ($item['variations'] as $variation) {
                            $stockByMultiWare = 0;

                            if ($variation->stock) {
                                $stock = $stock + $variation->stock;
                            } else {
                                foreach ($variation->warehouse_product_variation as $key) {
                                    if ($key->stock) {
                                        $stockByMultiWare =  $stockByMultiWare + $key->stock;
                                    }
                                }
                                $variation->stock = $stockByMultiWare;
                                $CountStockWareh = $CountStockWareh + $stockByMultiWare;
                            }
                        }

                        if ($stock == 0 && $stock == $CountStockWareh) {
                            $finalstock = 0;
                        } else if ($stock > $CountStockWareh) {
                            $finalstock  = $stock;
                        } else if ($stock < $CountStockWareh) {
                            $finalstock  = $CountStockWareh;
                        }
                    }
                }

                return number_format($finalstock, 0, ',', '.');
            case 'warehouse':
                return $this->column_warehouse($item);
            case 'icons':
                return $this->colum_icons($item);
            case 'imported':
                return $this->colum_imported($item);
            case 'store':
                return $item->store->store;

            default:
                $item = (array)$item;
                return $item[$column_name];
                //return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    private function colum_imported($item)
    {
        $this->logger->info(wc_print_r($item->shops, true), array('source' => 'dropi-products'));
        if (
            sizeof($item->shops)  > 0
            && $item->shops[0]->pivot->imported_to_store == 1
        ) {

            return "<span class='dashicons dashicons-yes' style='color:#2bee2b'></span>";
        } else {
            return "";
        }
    }

    private function price_colum($item)
    {
        $price = floatval($item->sale_price);
        $comision = $item->user->plan->dropi_percent_product_supplier;
        $dropi_product_supplier_increment_fixed = $item->user->plan->dropi_product_supplier_increment_fixed;
        if ($dropi_product_supplier_increment_fixed === true) {
            if (floatval($comision) > 0) {
                $aditional = $comision;
                $price = $price + $aditional;
            }
        } else {
            if (floatval($comision) > 0) {
                $aditional = ($price * $comision) / 100;
                $price = $price + $aditional;
            }
        }

        return number_format($price, 0, ',', '.');
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        //var_dump($item);
        return sprintf(
            '<input type="checkbox" name="bulk-import[]" value="%s" />',
            $item->id
        );
    }

    protected function get_views()
    {
        $status_links = array(
            "all"       => __("<a href='?page=dropi-products'>Todos</a>", 'my-plugin-slug'),
            "verified_providers" => __("<a href='?page=dropi-products&userVerified=true'>Proveedores verificados</a>", 'my-plugin-slug'),

        );
        return $status_links;
    }

    function extra_tablenav($which)
    {
        switch ($which) {
            case 'top':
                // Your html code to output
                global $wpdb;

                ////filtro por scotk
                $filter_have_stock = isset($_GET['filter_have_stock']) ? $_GET['filter_have_stock'] : 'ALL';
                $wp_query = add_query_arg();
                $wp_query = remove_query_arg('filter_have_stock');
                $link = esc_url_raw($wp_query);


?>
                <div class="alignleft actions">
                    <select name="filter_have_stock" id="filter-have-stock">
                        <option<?php selected($filter_have_stock, 'ALL'); ?> value="ALL" data-rc="<?php _e($link); ?>"><?php _e('Stock: Todos'); ?></option>
                            <?php
                            // $wp_query = add_query_arg('filter_have_stock', 'ONLY_HAVE_STOCK');
                            // $link = esc_url_raw($wp_query);

                            printf(
                                "<option %s value='%s' data-rc='%s'>%s</option>\n",
                                selected($filter_have_stock, 'ONLY_HAVE_STOCK', false),
                                esc_attr('ONLY_HAVE_STOCK'),
                                esc_attr($link),
                                'Productos con stock'
                            );

                            ?>
                    </select>

                </div>



                <?php

                //filtro por descripción
                $filter_have_description = isset($_GET['filter_have_description']) ?  $_GET['filter_have_description'] : 'ALL';
                // $wp_query = add_query_arg();
                $wp_query = remove_query_arg('filter_have_description');
                $link = esc_url_raw($wp_query);


                ?>
                <div class="alignleft actions">
                    <select name="filter_have_description" id="filter-have-description">
                        <option<?php selected($filter_have_stock, 'ALL'); ?> value="ALL" data-rc="<?php _e($link); ?>"><?php _e('Descripcion: Todos'); ?></option>
                            <?php
                            // $wp_query = add_query_arg('filter_have_description', 'ONLY_HAVE_DESCRIPTION');
                            // $link = esc_url_raw($wp_query);
                            printf(
                                "<option %s value='%s' data-rc='%s'>%s</option>\n",
                                selected($filter_have_description, 'ONLY_HAVE_DESCRIPTION', false),
                                esc_attr('ONLY_HAVE_DESCRIPTION'),
                                esc_attr($link),
                                'Productos con descripción'
                            );
                            // $link.='&filter_have_description=YES';
                            ?>
                    </select>

                </div>

                <?php

                //filtro por Categoría
                $category_filter = isset($_GET['category_filters']) ?  $_GET['category_filters'] : '';
                $wp_query = remove_query_arg('category_filter');
                $link = esc_url_raw($wp_query);

                ?>

                <div class="alignleft actions">
                    <select name="category_filter" id="category-filter">
                        <option <?php selected($category_filter, 'ALL'); ?> value="" data-rc="<?php _e($link); ?>"><?php _e('Categorías: Todas'); ?></option>
                        <?php
                        $all_cats = $this->ProducstInstance->getCategories();
                        foreach ($all_cats as $key => $value) :
                            printf(
                                "<option value='%s' data-rc='%s'>%s</option>\n",
                                esc_attr($value->id),
                                esc_attr($link),
                                $value->name
                            );
                        endforeach;
                        ?>

                    </select>
                </div>

                <?php

                //filtro por Bodegas
                $warehouses_filter = isset($_GET['warehouses_filter']) ?  $_GET['warehouses_filter'] : '';
                //$wp_query = remove_query_arg('warehouses_filter');
                $link = esc_url_raw($wp_query);
                //$this->logger = wc_get_logger();
                ?>

                <!-- comento temporalmente porque etsa colgandio demasiado la consulta, ver como ahcemos el selctor lazy, SE ME OCURRE llamar poe ajax las bodegas en el document ready--->
                <!--<div class="alignleft actions">
                <select name="warehouses_filter" id="warehouses-filter">
                    <option <?php selected($warehouses_filter, 'ALL'); ?> value="" data-rc="<?php _e($link); ?>"><?php _e('Bodegas: Todas'); ?></option>
                    <?php

                    /*$all_whouses = $this->ProducstInstance->getWarehouse();
                    foreach ($all_whouses as $key => $value) {
                        $tmp = str_replace("\n", "", $value->store_name);
                        if (isset($value->name) && $tmp != "null") {
                            printf(
                                "<option value='%s' data-rc='%s'>%s</option>\n",
                                esc_attr($value->id),
                                esc_attr($link),
                                $value->name
                                //$value->id . ": " . $value->name . " / " . $value->store_name
                            );
                        }
                    }*/
                    ?>
                  
                  
              </select>

                </div>-->

                <?php

                //filtro por Tienda
                $store_filter = isset($_GET['store_filter']) ?  $_GET['store_filter'] : '';
                //$wp_query = remove_query_arg('store_filter');
                $link = esc_url_raw($wp_query);
                //$this->logger = wc_get_logger();
                ?>

                <div class="alignleft actions">
                    <select name="store_filter" id="store-filter">
                        <!--<option <?php selected($store_filter, 'ALL'); ?> value="" data-rc="<?php _e($link); ?>"><?php _e('Tiendas: Todas'); ?></option>-->
                        <?php

                        $all_stores = $this->TokenModel->getTokens();
                        foreach ($all_stores as $key => $value) {
                            $tmp = str_replace("\n", "", $value->store);
                            if (isset($value->store) && $tmp != "null") {
                                printf(
                                    "<option value='%s' data-rc='%s'>%s</option>\n",
                                    esc_attr($value->id),
                                    esc_attr($link),
                                    $value->store
                                    //$value->id . ": " . $value->name . " / " . $value->store_name
                                );
                            }
                        }
                        ?>


                    </select>

                    <a href="javascript:void(0)" class="button" onclick="window.location.href='<?php echo esc_attr($link) ?>&filter_have_description='+jQuery('#filter-have-description').val()+'&filter_have_stock='+jQuery('#filter-have-stock').val()+'&category_filter='+jQuery('#category-filter').val()+'&warehouses_filter='+jQuery('#warehouses-filter').val()+'&store_filter='+jQuery('#store-filter').val()">Filtrar</a>
                </div>

        <?php
                break;
                break;

            case 'bottom':
                // Your html code to output
                break;
        }
    }

    /**
     * Generates custom table navigation to prevent conflicting nonces.
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     */
    protected function display_tablenav($which)
    {
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">
            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions($which); ?>
            </div>
            <?php
            $this->extra_tablenav($which);
            $this->pagination($which);
            ?>
            <br class="clear" />
        </div>
<?php
    }
}
