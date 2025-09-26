<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class JPIODFW_Constants
{

    public $API_URL = '';
    public $IMG_URL = '';
    public $STATUS_BORRADOR = 'PENDIENTE CONFIRMACION';
    public $CON_RECAUDO = 'CON RECAUDO';
    public $SIN_RECAUDO = 'SIN RECAUDO';
    private static $instance;
    public $SINC_AUTOM = 'AUTOMÁTICAMENTE';
    public $SINC_MANUAL = 'MANUALMENTE';
    /*......*/

    static function GetInstance()
    {

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct()
    {
        $this->getApiUrl();
    }

    function getApiUrl()
    {
        if (class_exists('WooCommerce')) {
            try {
                $shop_country = wc_get_base_location()['country'];


                switch ($shop_country) {
                    case 'CO':
                        $this->API_URL = 'https://api.dropi.co/integrations/';
                        $this->IMG_URL = 'https://api.dropi.co/';
                        break;
                    case 'PA':
                        $this->API_URL = 'https://api.dropi.pa/integrations/';
                        $this->IMG_URL = 'https://api.dropi.pa/';
                        break;
                    case 'MX':
                        $this->API_URL = 'https://api.dropi.mx/integrations/';
                        $this->IMG_URL = 'https://api.dropi.mx/';
                        break;
                    case 'EC':
                        $this->API_URL = 'https://api.dropi.ec/integrations/';
                        $this->IMG_URL = 'https://api.dropi.ec/';
                        break;
                    case 'CL':
                        $this->API_URL = 'https://api.dropi.cl/integrations/';
                        $this->IMG_URL = 'https://api.dropi.cl/';
                        break;
                    case 'PE':
                        $this->API_URL = 'https://api.dropi.pe/integrations/';
                        $this->IMG_URL = 'https://api.dropi.pe/';
                        break;
                    case 'ES':
                        $this->API_URL = 'https://api.dropi.com.es/integrations/';
                        $this->IMG_URL = 'https://api.dropi.com.es/';
                        break;
                    case 'PY':
                        $this->API_URL = 'https://api.dropi.com.py/integrations/';
                        $this->IMG_URL = 'https://api.dropi.com.py/';
                        break;
                    default:
                        echo '<div class="notice notice-error is-dismissible">
                        <p>Dropi Error, por favor configura un pais permitido por dropi</p>
                    </div>';
                }
            } catch (Exception $e) {
                echo '<div class="notice notice-error is-dismissible">
                <p>Dropi Error, por favor configura un pais permitido por dropi</p>
            </div>';
            }
            //$this->API_URL = 'http://127.0.0.1:8000/integrations/';
        } else {
            echo '<div class="notice notice-error is-dismissible">
                <p>Error Dropify: Para usar Dropify debes tener instalado el plugin WooCommerce</p>
            </div>';
        }
    }
}
