<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Block_Manager_Settings {
    
    private $page_slug = 'centralized-block-manager';
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Add AJAX endpoints
        add_action( 'wp_ajax_bm_auto_save_settings', array( $this, 'ajax_auto_save_settings' ) );
    }
    
    public function add_admin_menu() {
        $page_hook = add_menu_page(
            __( 'Block Manager', 'centralized-block-manager' ),
            __( 'Block Manager', 'centralized-block-manager' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'settings_page' ),
            'dashicons-screenoptions',
            30
        );
        
    }
    
    public function settings_init() {
        register_setting( 'centralized_block_manager_settings', 'bm_disabled_blocks', array(
            'sanitize_callback' => array( $this, 'sanitize_disabled_blocks' ),
            'default' => array()
        ) );
        register_setting( 'centralized_block_manager_settings', 'bm_disabled_blocks_by_post_type', array(
            'sanitize_callback' => array( $this, 'sanitize_post_type_data' ),
            'default' => array()
        ) );
        
        add_settings_section(
            'centralized_block_manager_section',
            __( 'Block Management Settings', 'centralized-block-manager' ),
            array( $this, 'settings_section_callback' ),
            'centralized_block_manager_settings'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Manage which blocks are available in the WordPress editor. You can disable blocks globally or for specific post types.', 'centralized-block-manager' ) . '</p>';
    }
    
    public function settings_page() {
        
        
        // Check for form submission
        if ( ! empty( $_POST ) && check_admin_referer( 'block_manager_pro_save', 'centralized_block_manager_nonce' ) ) {
            $this->handle_form_submission();
        }
        
        include_once BLOCK_MANAGER_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    private function handle_form_submission() {
        // Nonce is already verified by caller
        
        $disabled_blocks_global = isset( $_POST['disabled_blocks_global'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['disabled_blocks_global'] ) ) : array();
        $disabled_blocks_by_post_type = isset( $_POST['disabled_blocks_by_post_type'] ) ? $this->sanitize_post_type_data( wp_unslash( $_POST['disabled_blocks_by_post_type'] ) ) : array();
        
        // Auto-include child blocks when parent is disabled
        $disabled_blocks_global = $this->expand_with_child_blocks( $disabled_blocks_global );
        $disabled_blocks_by_post_type = $this->expand_post_type_with_child_blocks( $disabled_blocks_by_post_type );
        
        
        $global_updated = update_option( 'bm_disabled_blocks', $disabled_blocks_global );
        $post_type_updated = update_option( 'bm_disabled_blocks_by_post_type', $disabled_blocks_by_post_type );
        
        
        add_settings_error( 'centralized_block_manager_messages', 'block_manager_pro_message', __( 'Settings saved successfully!', 'centralized-block-manager' ), 'updated' );
    }
    
    public function ajax_auto_save_settings() {
        // Verify nonce for security
        if ( ! check_ajax_referer( 'bm_auto_save_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
            return;
        }
        
        try {
            // Get the posted data - nonce already verified above
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $disabled_blocks_global = isset( $_POST['disabled_blocks_global'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['disabled_blocks_global'] ) ) : array();
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $disabled_blocks_by_post_type = isset( $_POST['disabled_blocks_by_post_type'] ) ? $this->sanitize_post_type_data( wp_unslash( $_POST['disabled_blocks_by_post_type'] ) ) : array();
            
            // Auto-include child blocks when parent is disabled
            $disabled_blocks_global = $this->expand_with_child_blocks( $disabled_blocks_global );
            $disabled_blocks_by_post_type = $this->expand_post_type_with_child_blocks( $disabled_blocks_by_post_type );
            
            // Save to database
            $global_updated = update_option( 'bm_disabled_blocks', $disabled_blocks_global );
            $post_type_updated = update_option( 'bm_disabled_blocks_by_post_type', $disabled_blocks_by_post_type );
            
            
            // Return success response
            wp_send_json_success( array(
                'message' => __( 'Settings auto-saved', 'centralized-block-manager' ),
                'global_count' => count( $disabled_blocks_global ),
                'post_type_count' => count( $disabled_blocks_by_post_type ),
                'timestamp' => current_time( 'mysql' )
            ) );
            
        } catch ( Exception $e ) {
            
            wp_send_json_error( array( 'message' => 'Save failed: ' . $e->getMessage() ) );
        }
    }
    
    private function expand_with_child_blocks( $disabled_blocks ) {
        $hierarchy_map = $this->get_block_hierarchy_map();
        $expanded_blocks = $disabled_blocks;
        
        foreach ( $disabled_blocks as $block_slug ) {
            if ( isset( $hierarchy_map[ $block_slug ] ) ) {
                // Add all child blocks
                $expanded_blocks = array_merge( $expanded_blocks, $hierarchy_map[ $block_slug ] );
            }
        }
        
        return array_unique( $expanded_blocks );
    }
    
    private function expand_post_type_with_child_blocks( $disabled_blocks_by_post_type ) {
        $hierarchy_map = $this->get_block_hierarchy_map();
        $expanded = array();
        
        foreach ( $disabled_blocks_by_post_type as $block_slug => $post_types ) {
            $expanded[ $block_slug ] = $post_types;
            
            // If this is a parent block, add child blocks for the same post types
            if ( isset( $hierarchy_map[ $block_slug ] ) ) {
                foreach ( $hierarchy_map[ $block_slug ] as $child_block ) {
                    if ( isset( $expanded[ $child_block ] ) ) {
                        // Merge post types
                        $expanded[ $child_block ] = array_unique( array_merge( $expanded[ $child_block ], $post_types ) );
                    } else {
                        $expanded[ $child_block ] = $post_types;
                    }
                }
            }
        }
        
        return $expanded;
    }
    
    public function sanitize_disabled_blocks( $blocks ) {
        if ( ! is_array( $blocks ) ) {
            return array();
        }
        
        return array_map( 'sanitize_text_field', $blocks );
    }
    
    private function sanitize_post_type_data( $data ) {
        if ( ! is_array( $data ) ) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ( $data as $block_slug => $post_types ) {
            $block_slug = sanitize_text_field( $block_slug );
            if ( is_array( $post_types ) ) {
                $post_types = array_map( 'sanitize_text_field', $post_types );
            } else {
                $post_types = array( sanitize_text_field( $post_types ) );
            }
            $sanitized[ $block_slug ] = $post_types;
        }
        
        return $sanitized;
    }
    
    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( 'toplevel_page_' . $this->page_slug !== $hook_suffix ) {
            return;
        }
        
        $js_file = BLOCK_MANAGER_PLUGIN_DIR . 'admin/js/admin.js';
        $css_file = BLOCK_MANAGER_PLUGIN_DIR . 'admin/css/admin.css';
        
        if ( file_exists( $js_file ) ) {
            wp_enqueue_script(
                'block-manager-admin',
                BLOCK_MANAGER_PLUGIN_URL . 'admin/js/admin.js',
                array( 'jquery' ),
                BLOCK_MANAGER_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script( 'block-manager-admin', 'bmAjax', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'bm_auto_save_nonce' ),
                'strings' => array(
                    'saving' => __( 'Saving...', 'centralized-block-manager' ),
                    'saved' => __( 'Saved', 'centralized-block-manager' ),
                    'save_error' => __( 'Save failed', 'centralized-block-manager' ),
                    'auto_saved' => __( 'Auto-saved', 'centralized-block-manager' )
                )
            ) );
        }
        
        if ( file_exists( $css_file ) ) {
            wp_enqueue_style(
                'block-manager-admin',
                BLOCK_MANAGER_PLUGIN_URL . 'admin/css/admin.css',
                array(),
                BLOCK_MANAGER_VERSION
            );
        }
        
    }
    
    public function get_all_blocks() {
        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        $all_blocks = array();
        
        foreach ( $registered_blocks as $slug => $block ) {
            // Improved title handling with better fallbacks
            $title = $this->get_block_title( $block, $slug );
            
            $all_blocks[ $slug ] = array(
                'slug' => $slug,
                'title' => $title,
                'description' => isset( $block->description ) ? $block->description : '',
                'category' => isset( $block->category ) ? $block->category : 'common',
                'namespace' => strstr( $slug, '/', true ),
                'parent' => isset( $block->parent ) ? $block->parent : null,
                'is_parent' => false,
                'children' => array()
            );
        }
        
        // Build hierarchy
        $hierarchy = $this->build_block_hierarchy( $all_blocks );
        
        // Return only parent blocks (no children) for the admin interface
        $parent_blocks = array_filter( $hierarchy, function( $block ) {
            return $block['is_parent'] || empty( $block['parent'] );
        });
        
        uasort( $parent_blocks, function( $a, $b ) {
            return strcmp( $a['namespace'] . $a['title'], $b['namespace'] . $b['title'] );
        });
        
        return $parent_blocks;
    }
    
    public function get_all_blocks_including_children() {
        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        $all_blocks = array();
        
        foreach ( $registered_blocks as $slug => $block ) {
            // Use the same improved title handling
            $title = $this->get_block_title( $block, $slug );
            
            $all_blocks[ $slug ] = array(
                'slug' => $slug,
                'title' => $title,
                'description' => isset( $block->description ) ? $block->description : '',
                'category' => isset( $block->category ) ? $block->category : 'common',
                'namespace' => strstr( $slug, '/', true )
            );
        }
        
        return $all_blocks;
    }
    
    private function get_block_title( $block, $slug ) {
        // First, try the block's title property
        if ( isset( $block->title ) && ! empty( trim( $block->title ) ) ) {
            return $block->title;
        }
        
        // Try to get title from block.json metadata if available
        if ( isset( $block->block_type ) && isset( $block->block_type['title'] ) ) {
            return $block->block_type['title'];
        }
        
        // Check for common block name patterns and provide readable names
        $readable_titles = $this->get_readable_block_titles();
        if ( isset( $readable_titles[ $slug ] ) ) {
            return $readable_titles[ $slug ];
        }
        
        // Generate a readable title from the slug
        return $this->generate_title_from_slug( $slug );
    }
    
    private function get_readable_block_titles() {
        return array(
            // Core blocks that might be missing titles
            'core/freeform' => 'Classic Editor',
            'core/missing' => 'Missing Block',
            'core/legacy-widget' => 'Legacy Widget',
            'core/widget-group' => 'Widget Group',
            'core/html' => 'Custom HTML',
            
            // Common plugin blocks that might be missing titles
            'acf/acf-block' => 'ACF Block',
            'gravityforms/form' => 'Gravity Forms',
            'contact-form-7/contact-form-selector' => 'Contact Form 7',
            'mailchimp-for-wp/form' => 'Mailchimp Form',
            'woocommerce/product-price' => 'Product Price',
            'woocommerce/product-image' => 'Product Image',
            'jetpack/contact-form' => 'Contact Form',
            'jetpack/markdown' => 'Markdown',
            'kadence/spacer' => 'Spacer',
            'kadence/rowlayout' => 'Row Layout',
            'genesis-blocks/gb-container' => 'Container',
            'ultimate-addons-for-gutenberg/container' => 'UAG Container',
            'stackable/separator' => 'Stackable Separator',
            'blocksy/dynamic-data' => 'Dynamic Data',
        );
    }
    
    private function generate_title_from_slug( $slug ) {
        // Split the slug by namespace and block name
        $parts = explode( '/', $slug );
        
        if ( count( $parts ) === 2 ) {
            $namespace = $parts[0];
            $block_name = $parts[1];
            
            // Convert block name to readable format
            $readable_name = $this->slug_to_title( $block_name );
            
            // Add namespace context for non-core blocks
            if ( $namespace !== 'core' ) {
                return $readable_name . ' (' . ucfirst( $namespace ) . ')';
            }
            
            return $readable_name;
        }
        
        // Fallback: use the entire slug converted to title case
        return $this->slug_to_title( $slug );
    }
    
    private function slug_to_title( $slug ) {
        // Replace hyphens and underscores with spaces
        $title = str_replace( array( '-', '_' ), ' ', $slug );
        
        // Convert to title case
        $title = ucwords( $title );
        
        // Handle some common abbreviations
        $title = str_replace( 
            array( 'Acf', 'Html', 'Css', 'Js', 'Rss', 'Seo', 'Api', 'Url', 'Uag', 'Gb' ),
            array( 'ACF', 'HTML', 'CSS', 'JS', 'RSS', 'SEO', 'API', 'URL', 'UAG', 'Genesis' ),
            $title
        );
        
        return $title;
    }
    
    public function build_block_hierarchy( $all_blocks ) {
        $hierarchy_map = $this->get_block_hierarchy_map();
        
        // Mark blocks as parent/child based on known hierarchies
        foreach ( $all_blocks as $slug => &$block ) {
            if ( isset( $hierarchy_map[ $slug ] ) ) {
                $block['is_parent'] = true;
                $block['children'] = $hierarchy_map[ $slug ];
            } else {
                // Check if this block is a child of any parent
                foreach ( $hierarchy_map as $parent_slug => $children ) {
                    if ( in_array( $slug, $children ) ) {
                        $block['parent'] = $parent_slug;
                        break;
                    }
                }
            }
        }
        
        return $all_blocks;
    }
    
    public function get_block_hierarchy_map() {
        // Define parent-child relationships for core WordPress blocks and common plugins
        $core_hierarchy = array(
            'core/buttons' => array( 'core/button' ),
            'core/columns' => array( 'core/column' ),
            'core/list' => array( 'core/list-item' ),
            'core/social-links' => array( 'core/social-link' ),
            'core/navigation' => array( 'core/navigation-link', 'core/navigation-submenu' ),
            'core/page-list' => array( 'core/page-list-item' ),
            'core/comments' => array( 
                'core/comment-author-name', 
                'core/comment-content', 
                'core/comment-date', 
                'core/comment-edit-link', 
                'core/comment-reply-link',
                'core/comment-template'
            ),
            'core/comments-pagination' => array(
                'core/comments-pagination-next',
                'core/comments-pagination-numbers', 
                'core/comments-pagination-previous'
            ),
            'core/query' => array(
                'core/post-template',
                'core/query-pagination',
                'core/query-no-results',
                'core/query-title'
            ),
            'core/query-pagination' => array(
                'core/query-pagination-next',
                'core/query-pagination-numbers',
                'core/query-pagination-previous'
            ),
            'core/post-template' => array(
                'core/post-title',
                'core/post-content',
                'core/post-excerpt',
                'core/post-date',
                'core/post-author',
                'core/post-featured-image',
                'core/post-terms',
                'core/post-comments-count',
                'core/post-comments-link',
                'core/post-comments-form'
            ),
            'core/post-author' => array(
                'core/post-author-name',
                'core/post-author-biography',
                'core/avatar'
            ),
            'core/accordion' => array(
                'core/accordion-item',
                'core/accordion-heading', 
                'core/accordion-panel'
            ),
            'core/terms-query' => array(
                'core/term-template',
                'core/term-name',
                'core/term-description',
                'core/term-count'
            )
        );
        
        // Add common plugin hierarchies
        $plugin_hierarchy = $this->get_plugin_block_hierarchies();
        
        return array_merge( $core_hierarchy, $plugin_hierarchy );
    }
    
    public function get_plugin_block_hierarchies() {
        // Define hierarchies for common WordPress plugins
        $plugin_hierarchies = array();
        
        // Check for common plugins and add their hierarchies
        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        
        // Gutenberg/WordPress common plugin patterns
        if ( array_key_exists( 'woocommerce/product-price', $registered_blocks ) ) {
            // WooCommerce blocks
            $plugin_hierarchies['woocommerce/allducts'] = array(
                'woocommerce/product-price',
                'woocommerce/product-image',
                'woocommerce/product-title'
            );
        }
        
        if ( array_key_exists( 'jetpack/contact-form', $registered_blocks ) ) {
            // Jetpack contact form
            $plugin_hierarchies['jetpack/contact-form'] = array(
                'jetpack/field-text',
                'jetpack/field-email', 
                'jetpack/field-textarea',
                'jetpack/field-checkbox',
                'jetpack/field-select'
            );
        }
        
        // Kadence blocks
        if ( array_key_exists( 'kadence/accordion', $registered_blocks ) ) {
            $plugin_hierarchies['kadence/accordion'] = array(
                'kadence/pane'
            );
        }
        
        // Genesis blocks
        if ( array_key_exists( 'genesis-blocks/gb-columns', $registered_blocks ) ) {
            $plugin_hierarchies['genesis-blocks/gb-columns'] = array(
                'genesis-blocks/gb-column'
            );
        }
        
        return $plugin_hierarchies;
    }
    
    public function get_post_types() {
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        $post_types['template'] = (object) array( 'label' => 'Templates', 'name' => 'template' );
        $post_types['widget'] = (object) array( 'label' => 'Widgets', 'name' => 'widget' );
        
        return $post_types;
    }
    
}