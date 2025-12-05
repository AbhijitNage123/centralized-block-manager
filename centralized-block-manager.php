<?php
/**
 * Plugin Name: Centralized Block Manager
 * Plugin URI: https://wordpress.org/plugins/centralized-block-manager/
 * Description: Manage and control which blocks are available in the WordPress editor with granular control by post type.
 * Version: 1.0.0
 * Author: Abhijit Nage
 * Author URI: https://github.com/centralized-block-manager
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: centralized-block-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
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
        // Add debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Block Manager: Initializing plugin' );
        }
        
        $this->load_textdomain();
        $this->includes();
        $this->init_hooks();
    }
    
    private function load_textdomain() {
        load_plugin_textdomain( 'centralized-block-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
    
    private function includes() {
        $filter_file = BLOCK_MANAGER_PLUGIN_DIR . 'includes/class-block-filter.php';
        if ( file_exists( $filter_file ) ) {
            require_once $filter_file;
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Block Manager: Filter file not found: ' . $filter_file );
            }
        }
        
        if ( is_admin() ) {
            $settings_file = BLOCK_MANAGER_PLUGIN_DIR . 'admin/class-block-manager-settings.php';
            if ( file_exists( $settings_file ) ) {
                require_once $settings_file;
            } else {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Block Manager: Settings file not found: ' . $settings_file );
                }
            }
        }
    }
    
    private function init_hooks() {
        if ( class_exists( 'Block_Filter' ) ) {
            new Block_Filter();
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Block Manager: Block_Filter class not found' );
            }
        }
        
        if ( is_admin() && class_exists( 'Block_Manager_Settings' ) ) {
            new Block_Manager_Settings();
        } else if ( is_admin() ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Block Manager: Block_Manager_Settings class not found' );
            }
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
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Block Manager: Plugin activated successfully' );
        }
    }
    
    public function deactivate() {
        // Clean up database options when plugin is deactivated
        delete_option( 'bm_disabled_blocks' );
        delete_option( 'bm_disabled_blocks_by_post_type' );
        delete_option( 'bm_plugin_activated' );
        
        // Clean up any transients
        delete_transient( 'bm_activation_redirect' );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Block Manager: Plugin deactivated and database cleaned up' );
        }
    }
    
    public function activation_redirect() {
        // Check if we should redirect after activation
        if ( get_transient( 'bm_activation_redirect' ) ) {
            // Delete the transient so we don't redirect again
            delete_transient( 'bm_activation_redirect' );
            
            // Don't redirect if we're already on the page or doing AJAX
            if ( isset( $_GET['page'] ) && $_GET['page'] === 'centralized-block-manager' ) {
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

// Temporary test function - remove after testing
add_action( 'admin_footer', function() {
    if ( current_user_can( 'manage_options' ) && isset( $_GET['test_bm'] ) ) {
        $disabled_global = get_option( 'bm_disabled_blocks', array() );
        $disabled_by_type = get_option( 'bm_disabled_blocks_by_post_type', array() );
        
        echo '<script>console.log("Block Manager Test:");</script>';
        echo '<script>console.log("Global disabled:", ' . json_encode( $disabled_global ) . ');</script>';
        echo '<script>console.log("By post type:", ' . json_encode( $disabled_by_type ) . ');</script>';
    }
});