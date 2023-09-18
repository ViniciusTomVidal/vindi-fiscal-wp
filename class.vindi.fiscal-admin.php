<?php
class VindiFiscalAdmin {

    private $plugin_base;
    public function __construct() {
        add_action( 'admin_menu', [$this, 'custom_vindi_options_page']);

        $this->plugin_base = plugins_url('/', __FILE__);
    }

    public function custom_vindi_options_page() {
        add_menu_page(
            'Vindi Fiscal', // Page title
            'Vindi Fiscal', // Menu title
            'manage_options', // Capability required to access the menu page
            'vindi-fiscal', // Unique slug for the menu page
            [$this,'vindi_options'], // Callback function to render the menu page
            $this->plugin_base.'/assets/img/vindi.svg', // Icon URL or dashicon class
            40 // Position of the menu item in the admin menu
        );


        add_submenu_page(
            'vindi-fiscal', // Parent slug
            'Configurações', // Page title
            'Configurações', // Menu title
            'manage_options', // Capability required to access the submenu page
            'vindi-configuracoes', // Unique slug for the submenu page
            [$this, 'settings_page']
        );
    }

    public function settings_page() {
        ob_start();
        require 'admin/settings.php';
        echo ob_get_clean();
    }

    public function vindi_options() {
        ob_start();
        require 'class.vind.export.php';
        $VindiExport = new VindiExport();
        require 'admin/export.php';
        echo ob_get_clean();
    }

}

$VindiFiscalAdmin = new VindiFiscalAdmin();