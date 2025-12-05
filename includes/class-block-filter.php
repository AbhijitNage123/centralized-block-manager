<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Block_Filter {
    
    public function __construct() {
        add_filter( 'allowed_block_types_all', array( $this, 'filter_allowed_blocks' ), 10, 2 );
    }
    
    public function filter_allowed_blocks( $allowed_blocks, $block_editor_context ) {
        $disabled_blocks_global = get_option( 'bm_disabled_blocks', array() );
        $disabled_blocks_by_post_type = get_option( 'bm_disabled_blocks_by_post_type', array() );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Block Manager: Filter called with disabled_blocks_global: ' . print_r( $disabled_blocks_global, true ) );
            error_log( 'Block Manager: Block editor context: ' . print_r( $block_editor_context, true ) );
        }
        
        // If no blocks are disabled, return early
        if ( empty( $disabled_blocks_global ) && empty( $disabled_blocks_by_post_type ) ) {
            return $allowed_blocks;
        }
        
        $current_post_type = $this->get_current_post_type( $block_editor_context );
        
        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        $all_block_slugs = array_keys( $registered_blocks );
        
        // Start with all blocks allowed
        $filtered_blocks = $all_block_slugs;
        
        foreach ( $all_block_slugs as $slug ) {
            $should_disable = false;
            
            // Check global disable
            if ( in_array( $slug, $disabled_blocks_global ) ) {
                $should_disable = true;
            }
            
            // Check post-type specific disable
            if ( $current_post_type && isset( $disabled_blocks_by_post_type[ $slug ] ) ) {
                if ( in_array( $current_post_type, $disabled_blocks_by_post_type[ $slug ] ) ) {
                    $should_disable = true;
                }
            }
            
            if ( $should_disable ) {
                $filtered_blocks = array_diff( $filtered_blocks, array( $slug ) );
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Block Manager: Disabling block: ' . $slug );
                }
            }
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Block Manager: Returning filtered blocks count: ' . count( $filtered_blocks ) . ' of ' . count( $all_block_slugs ) );
        }
        
        return array_values( $filtered_blocks );
    }
    
    private function get_current_post_type( $block_editor_context ) {
        if ( ! $block_editor_context ) {
            return null;
        }
        
        if ( 'core/edit-post' === $block_editor_context->name && isset( $block_editor_context->post ) ) {
            return $block_editor_context->post->post_type;
        }
        
        if ( 'core/edit-site' === $block_editor_context->name ) {
            return 'template';
        }
        
        if ( 'core/edit-widgets' === $block_editor_context->name ) {
            return 'widget';
        }
        
        return null;
    }
    
    public function get_disabled_post_types_for_block( $block_slug ) {
        $disabled_blocks_by_post_type = get_option( 'bm_disabled_blocks_by_post_type', array() );
        
        return isset( $disabled_blocks_by_post_type[ $block_slug ] ) 
            ? $disabled_blocks_by_post_type[ $block_slug ] 
            : array();
    }
}