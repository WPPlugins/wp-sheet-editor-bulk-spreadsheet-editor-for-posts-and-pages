<?php

/*
 Plugin Name: WP Sheet Editor by VegaCorp
 Description: Bulk edit posts and pages easily using a beautiful spreadsheet inside WordPress.
 Version: 1.4.7
 Author: VegaCorp
 Author URI: http://vegacorp.me
 Plugin URI: http://wpsheeteditor.com  
 @fs_premium_only /modules/acf/, /modules/autofill-cells/, /modules/columns-renaming/, /modules/columns-visibility/, /modules/custom-columns/, /modules/custom-post-types/, /modules/filters/, /modules/formulas/, /modules/woocommerce/, /modules/yoast-seo/, /whats-new/
*/
if ( !defined( 'VGSE_MAIN_FILE' ) ) {
    define( 'VGSE_MAIN_FILE', __FILE__ );
}
if ( !defined( 'VGSE_DIST_DIR' ) ) {
    define( 'VGSE_DIST_DIR', __DIR__ );
}
require 'inc/freemius-init.php';
if ( !class_exists( 'WP_Sheet_Editor_Dist' ) ) {
    class WP_Sheet_Editor_Dist
    {
        private static  $instance = false ;
        public  $addon_helper = null ;
        private function __construct()
        {
        }
        
        /**
         * Creates or returns an instance of this class.
         */
        public static function get_instance()
        {
            
            if ( null == WP_Sheet_Editor_Dist::$instance ) {
                WP_Sheet_Editor_Dist::$instance = new WP_Sheet_Editor_Dist();
                WP_Sheet_Editor_Dist::$instance->init();
            }
            
            return WP_Sheet_Editor_Dist::$instance;
        }
        
        public function init()
        {
            $modules = $this->get_modules_list();
            if ( empty($modules) ) {
                return;
            }
            // Load all modules
            foreach ( $modules as $module ) {
                $path = __DIR__ . "/modules/{$module}/{$module}.php";
                if ( file_exists( $path ) ) {
                    require $path;
                }
            }
        }
        
        /**
         * Get all modules in the folder
         * @return array
         */
        public function get_modules_list()
        {
            $directories = glob( __DIR__ . '/modules/*', GLOB_ONLYDIR );
            if ( !empty($directories) ) {
                $directories = array_map( 'basename', $directories );
            }
            return $directories;
        }
        
        public function __set( $name, $value )
        {
            $this->{$name} = $value;
        }
        
        public function __get( $name )
        {
            return $this->{$name};
        }
    
    }
}
WP_Sheet_Editor_Dist::get_instance();