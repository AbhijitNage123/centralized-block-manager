<?php
/**
 * Plugin Name: Centralized Block Manager
 * Plugin URI: https://wordpress.org/plugins/centralized-block-manager/
 * Description: Manage and control which blocks are available in the WordPress editor with granular control by post type.
 * Version: 1.0.0
 * Author: Abhijit Nage
 * Author URI: https://github.com/AbhijitNage123/centralized-block-manager
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: centralized-block-manager
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BLOCK_MANAGER_VERSION', '1.0.0' );
define( 'BLOCK_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOCK_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class Block_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'admin_init', array( $this, 'activation_redirect' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    
    public function init() {
        
        $this->includes();
        $this->init_hooks();
    }
    
    
    private function includes() {
        $filter_file = BLOCK_MANAGER_PLUGIN_DIR . 'includes/class-block-filter.php';
        if ( file_exists( $filter_file ) ) {
            require_once $filter_file;
        }
        
        if ( is_admin() ) {
            $settings_file = BLOCK_MANAGER_PLUGIN_DIR . 'admin/class-block-manager-settings.php';
            if ( file_exists( $settings_file ) ) {
                require_once $settings_file;
            }
        }
    }
    
    private function init_hooks() {
        if ( class_exists( 'Block_Filter' ) ) {
            new Block_Filter();
        }
        
        if ( is_admin() && class_exists( 'Block_Manager_Settings' ) ) {
            new Block_Manager_Settings();
        }
    }
    
    public function activate() {
        if ( ! get_option( 'bm_disabled_blocks' ) ) {
            add_option( 'bm_disabled_blocks', array() );
        }
        
        if ( ! get_option( 'bm_disabled_blocks_by_post_type' ) ) {
            add_option( 'bm_disabled_blocks_by_post_type', array() );
        }
        
        // Set redirect flag for activation
        set_transient( 'bm_activation_redirect', true, 30 );
        
        // Add activation flag for debugging
        update_option( 'bm_plugin_activated', current_time( 'mysql' ) );
        
    }
    
    public function deactivate() {
        // Clean up database options when plugin is deactivated
        delete_option( 'bm_disabled_blocks' );
        delete_option( 'bm_disabled_blocks_by_post_type' );
        delete_option( 'bm_plugin_activated' );
        
        // Clean up any transients
        delete_transient( 'bm_activation_redirect' );
        
    }
    
    public function activation_redirect() {
        // Check if we should redirect after activation
        if ( get_transient( 'bm_activation_redirect' ) ) {
            // Delete the transient so we don't redirect again
            delete_transient( 'bm_activation_redirect' );
            
            // Don't redirect if we're already on the page or doing AJAX  
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe for redirect logic
            if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'centralized-block-manager' ) {
                return;
            }
            
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                return;
            }
            
            if ( headers_sent() ) {
                return;
            }
            
            // Make sure user has permission to access the page
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            
            // Redirect to the block manager page
            wp_safe_redirect( admin_url( 'admin.php?page=centralized-block-manager' ) );
            exit;
        }
    }
}

Block_Manager::get_instance();

