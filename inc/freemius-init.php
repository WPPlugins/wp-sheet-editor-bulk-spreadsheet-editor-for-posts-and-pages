<?php

// Create a helper function for easy SDK access.
if ( !function_exists( 'vgse_freemius' ) ) {
    function vgse_freemius()
    {
        global  $vgse_freemius ;
        
        if ( !isset( $vgse_freemius ) ) {
            // Include Freemius SDK.
            require_once VGSE_DIST_DIR . '/vendor/freemius/start.php';
            $vgse_freemius = fs_dynamic_init( array(
                'id'             => '1010',
                'slug'           => 'wp-sheet-editor-bulk-spreadsheet-editor-for-posts-and-pages',
                'type'           => 'plugin',
                'public_key'     => 'pk_ec1c7da603c0772f1bfe276efb715',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'       => 'vg_sheet_editor_setup',
                'first-path' => 'admin.php?page=vg_sheet_editor_setup',
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $vgse_freemius;
    }

}
// Init Freemius.
vgse_freemius();
// Signal that SDK was initiated.
do_action( 'vgse_freemius_loaded' );