<?php
/**
 * Uninstall script for Centralized Block Manager
 * 
 * This file is called when the plugin is deleted from WordPress.
 * It removes all plugin-related data from the database.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Security check - make sure we're in the right context
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Remove all plugin options from the database
 */
function centralized_block_manager_uninstall_cleanup() {
    // Remove main plugin options
    delete_option( 'bm_disabled_blocks' );
    delete_option( 'bm_disabled_blocks_by_post_type' );
    delete_option( 'bm_plugin_activated' );
    
    // Remove any transients
    delete_transient( 'bm_activation_redirect' );
    
    // Remove any site-wide options (for multisite)
    delete_site_option( 'bm_disabled_blocks' );
    delete_site_option( 'bm_disabled_blocks_by_post_type' );
    delete_site_option( 'bm_plugin_activated' );
    
    // Clear any cached data related to our plugin
    wp_cache_flush();
    
    // Log cleanup if debug mode is enabled
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Centralized Block Manager: Uninstall cleanup completed - all database options removed' );
    }
}

// Run the cleanup
centralized_block_manager_uninstall_cleanup();

// For multisite installations, clean up each site
if ( is_multisite() ) {
    global $wpdb;
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    
    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        centralized_block_manager_uninstall_cleanup();
        restore_current_blog();
    }
}