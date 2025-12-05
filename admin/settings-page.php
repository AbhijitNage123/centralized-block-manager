<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$blocks = $this->get_all_blocks();
$all_blocks_for_reference = $this->get_all_blocks_including_children();
$post_types = $this->get_post_types();
$disabled_blocks_global = get_option( 'bm_disabled_blocks', array() );
$disabled_blocks_by_post_type = get_option( 'bm_disabled_blocks_by_post_type', array() );

?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php settings_errors( 'centralized_block_manager_messages' ); ?>
    
    <div class="block-manager-notice">
        <p><strong><?php esc_html_e( 'Important:', 'centralized-block-manager' ); ?></strong> <?php esc_html_e( 'Disabling blocks only prevents new instances from being added. Existing content using these blocks will remain intact.', 'centralized-block-manager' ); ?></p>
    </div>
    
    <div class="block-manager-container">
        <div class="block-manager-controls">
            <div class="search-container">
                <input type="text" id="block-search" placeholder="<?php esc_attr_e( 'Search blocks...', 'centralized-block-manager' ); ?>" />
            </div>
            
            <div class="filter-container">
                <select id="namespace-filter">
                    <option value=""><?php esc_html_e( 'All namespaces', 'centralized-block-manager' ); ?></option>
                    <?php
                    $namespaces = array_unique( array_map( function( $block ) {
                        return $block['namespace'];
                    }, $blocks ) );
                    sort( $namespaces );
                    
                    foreach ( $namespaces as $namespace ) {
                        echo '<option value="' . esc_attr( $namespace ) . '">' . esc_html( $namespace ) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="bulk-actions">
                <button type="button" id="toggle-all-global" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Smart Toggle', 'centralized-block-manager' ); ?>
                </button>
            </div>
        </div>
        
        <div class="blocks-grid" id="blocks-grid">
            <?php foreach ( $blocks as $slug => $block ) : ?>
                <div class="block-card" data-namespace="<?php echo esc_attr( $block['namespace'] ); ?>" data-search-text="<?php echo esc_attr( strtolower( $block['title'] . ' ' . $slug ) ); ?>">
                    
                    <!-- Block Header -->
                    <div class="block-card-header">
                        <div class="block-title">
                            <strong><?php echo esc_html( $block['title'] ); ?></strong>
                            <?php if ( ! empty( $block['children'] ) ) : ?>
                                <span class="parent-indicator" title="Parent block - disabling will also disable child blocks">📁</span>
                            <?php endif; ?>
                        </div>
                        <div class="block-namespace-badge <?php echo $block['namespace'] === 'core' ? 'core-badge' : 'plugin-badge'; ?>">
                            <?php echo esc_html( $block['namespace'] ); ?>
                            <?php if ( $block['namespace'] !== 'core' ) : ?>
                                <span class="plugin-indicator">🔌</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    
                    <!-- Block Controls -->
                    <div class="block-card-footer">
                        <!-- Global Toggle -->
                        <div class="control-section">
                            <label class="control-label">
                                <span>Disable Globally</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           name="disabled_blocks_global[]" 
                                           value="<?php echo esc_attr( $slug ); ?>"
                                           class="global-checkbox"
                                           <?php checked( in_array( $slug, $disabled_blocks_global ) ); ?> />
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                        
                        <!-- Post Type Toggles -->
                        <div class="control-section">
                            <span class="control-label-text">Disable for Post Types:</span>
                            <div class="post-type-toggles">
                                <?php
                                $block_disabled_post_types = isset( $disabled_blocks_by_post_type[ $slug ] ) ? $disabled_blocks_by_post_type[ $slug ] : array();
                                
                                foreach ( $post_types as $post_type ) :
                                    $post_type_name = is_object( $post_type ) ? $post_type->name : $post_type;
                                    $post_type_label = is_object( $post_type ) ? $post_type->label : $post_type;
                                ?>
                                    <label class="post-type-toggle">
                                        <input type="checkbox" 
                                               name="disabled_blocks_by_post_type[<?php echo esc_attr( $slug ); ?>][]" 
                                               value="<?php echo esc_attr( $post_type_name ); ?>"
                                               class="post-type-checkbox"
                                               <?php checked( in_array( $post_type_name, $block_disabled_post_types ) ); ?> />
                                        <span><?php echo esc_html( $post_type_label ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
</div>