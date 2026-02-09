<?php 

/**
 * Plugin Name:       Photography Client Management
 * Plugin URI:        https://github.com/aniab46/photography-client-management
 * Description:       Handle all Photography Client Management operations.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Muhammad Aniab
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * Text Domain:       photography-client-management
 * Domain Path:       /languages
 */

class PCM_photography_Client_Management {

    public function __construct(){
        add_action('init',[$this,'init']);
        $this->defined_function();
        register_activation_hook(__FILE__,[$this,'register_database']);

    }

    public function init(){
        // Custom post type for client
        $this->register_custom_post_type();
        
        // Meta box for client
        include_once plugin_dir_path(__FILE__) .'include/admin/meta_box_manage.php';
        new photographyclientmanagement_metabox\Meta_box_manage();

        // Single page for client - load unconditionally, check happens in class on wp hook
        include_once plugin_dir_path(__FILE__) .'include/client/client_single_page.php';  
        new photographyclientmanagement\Client_single_page();

        // Ajax handler include and initialization
        include_once plugin_dir_path(__FILE__) .'include/client/ajax_handler.php';  
        new ajaxhandler\ajax_handler();

        
    }

    public function register_custom_post_type(){
        $labels=[
            'name'=>'Clients',
            'singular_name'=>'Client',
            'menu_name'=>'Client',
            'name_admin_bar'=>'Client',
            'add_new'=>'Add New',
            'add_new_item'=>'Add New Client',
            'view_item'=>'View Clients',
            'all_items'=>'All Clients',
            'add_new'=>'Add New Client'

        ];
        $arg=[
            'labels'=>$labels,
            'public'=> true,
            'has_archive'=>true,
            'menu_icon'=>'dashicons-businessman',
            'supports'=>['title'],
        ];
        register_post_type('client',$arg);
        flush_rewrite_rules();
    }

    public function register_database(){
        // Include database registration file
        include_once plugin_dir_path(__FILE__) .'include/admin/database.php';
        pcm_create_activity_table();

    }
    public function defined_function(){
        define('PCM_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('PCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('PCM_PLUGIN_VERSION', '1.0');
    }

}

new PCM_photography_Client_Management();