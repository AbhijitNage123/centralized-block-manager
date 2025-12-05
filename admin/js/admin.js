jQuery(document).ready(function($) {
    
    let saveTimeout = null;
    let isCurrentlySaving = false;
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Auto-save function
    function autoSaveSettings() {
        if (isCurrentlySaving) {
            return;
        }
        
        isCurrentlySaving = true;
        
        // Collect all form data
        const formData = {
            action: 'bm_auto_save_settings',
            nonce: bmAjax.nonce,
            disabled_blocks_global: [],
            disabled_blocks_by_post_type: {}
        };
        
        // Collect global disabled blocks
        $('.global-checkbox:checked').each(function() {
            formData.disabled_blocks_global.push($(this).val());
        });
        
        // Collect post type disabled blocks
        $('.post-type-checkbox:checked').each(function() {
            const blockSlug = $(this).closest('.block-card').find('.global-checkbox').val();
            const postType = $(this).val();
            
            if (!formData.disabled_blocks_by_post_type[blockSlug]) {
                formData.disabled_blocks_by_post_type[blockSlug] = [];
            }
            formData.disabled_blocks_by_post_type[blockSlug].push(postType);
        });
        
        // Send AJAX request
        $.ajax({
            url: bmAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    console.log('Auto-save successful:', response.data);
                } else {
                    console.error('Auto-save failed:', response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            },
            complete: function() {
                isCurrentlySaving = false;
            }
        });
    }
    
    // Debounced auto-save (500ms delay)
    const debouncedAutoSave = debounce(autoSaveSettings, 500);
    
    
    // Search functionality
    $('#block-search').on('input', function() {
        filterBlocks();
    });
    
    // Namespace filter
    $('#namespace-filter').on('change', function() {
        filterBlocks();
    });
    
    // Smart Toggle
    $('#toggle-all-global').on('click', function() {
        const visibleCards = $('.block-card:visible');
        const checkedCount = visibleCards.find('.global-checkbox:checked').length;
        const totalCount = visibleCards.length;
        
        // If all are checked, uncheck all; otherwise check all
        const shouldCheck = checkedCount < totalCount;
        
        visibleCards.find('.global-checkbox').prop('checked', shouldCheck);
        updateToggleAllButton();
        
        // Trigger auto-save
        debouncedAutoSave();
        
        const message = shouldCheck ? 'All visible blocks deactivated!' : 'All visible blocks activated!';
        const type = shouldCheck ? 'warning' : 'success';
        showSnackbar(message, type);
    });
    
    function filterBlocks() {
        const searchTerm = $('#block-search').val().toLowerCase();
        const selectedNamespace = $('#namespace-filter').val();
        let visibleCount = 0;
        
        $('.block-card').each(function() {
            const $card = $(this);
            const searchText = $card.data('search-text');
            const namespace = $card.data('namespace');
            
            let shouldShow = true;
            
            // Search filter
            if (searchTerm && searchText.indexOf(searchTerm) === -1) {
                shouldShow = false;
            }
            
            // Namespace filter
            if (selectedNamespace && namespace !== selectedNamespace) {
                shouldShow = false;
            }
            
            if (shouldShow) {
                $card.removeClass('hidden').show();
                visibleCount++;
            } else {
                $card.addClass('hidden').hide();
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            if ($('.no-results').length === 0) {
                $('.blocks-grid').append(
                    '<div class="no-results">No blocks match your search criteria. Try adjusting your search terms or filters.</div>'
                );
            }
        } else {
            $('.no-results').remove();
        }
        
        updateToggleAllButton();
    }
    
    
    // Enhanced checkbox interactions with auto-save
    $('.global-checkbox').on('change', function() {
        const $card = $(this).closest('.block-card');
        const isChecked = $(this).is(':checked');
        
        // When global is checked, uncheck post-type specific ones
        if (isChecked) {
            $card.find('.post-type-checkbox').prop('checked', false);
        }
        
        updateToggleAllButton();
        debouncedAutoSave(); // Trigger auto-save
    });
    
    $('.post-type-checkbox').on('change', function() {
        const $card = $(this).closest('.block-card');
        const anyPostTypeChecked = $card.find('.post-type-checkbox:checked').length > 0;
        
        // When any post-type is checked, uncheck global
        if (anyPostTypeChecked) {
            $card.find('.global-checkbox').prop('checked', false);
        }
        
        updateToggleAllButton();
        debouncedAutoSave(); // Trigger auto-save
    });
    
    // Update toggle all button text based on current state
    function updateToggleAllButton() {
        const visibleCards = $('.block-card:visible');
        const checkedCount = visibleCards.find('.global-checkbox:checked').length;
        const totalCount = visibleCards.length;
        
        const $toggleButton = $('#toggle-all-global');
        
        if (checkedCount === 0) {
            $toggleButton.html('<span class="dashicons dashicons-hidden"></span> Deactivate All (' + totalCount + ')');
        } else if (checkedCount === totalCount) {
            $toggleButton.html('<span class="dashicons dashicons-visibility"></span> Activate All (' + totalCount + ')');
        } else {
            $toggleButton.html('<span class="dashicons dashicons-update"></span> Smart Toggle (' + checkedCount + '/' + totalCount + ')');
        }
    }
    
    // Show snackbar notification
    function showSnackbar(message, type = 'info') {
        // Remove existing snackbars
        $('.bm-snackbar').remove();
        
        // Create snackbar element
        const iconClass = type === 'success' ? 'dashicons-yes-alt' : 
                         type === 'warning' ? 'dashicons-warning' : 
                         type === 'error' ? 'dashicons-dismiss' : 'dashicons-info';
        
        const snackbar = $(`
            <div class="bm-snackbar bm-snackbar-${type}">
                <span class="dashicons ${iconClass}"></span>
                <span class="bm-snackbar-message">${message}</span>
                <button type="button" class="bm-snackbar-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `);
        
        // Add to body
        $('body').append(snackbar);
        
        // Show with slide-up animation
        setTimeout(() => {
            snackbar.addClass('bm-snackbar-show');
        }, 100);
        
        // Auto-dismiss after 4 seconds
        setTimeout(function() {
            hideSnackbar(snackbar);
        }, 4000);
        
        // Handle manual dismiss
        snackbar.on('click', '.bm-snackbar-close', function() {
            hideSnackbar(snackbar);
        });
    }
    
    // Hide snackbar with animation
    function hideSnackbar(snackbar) {
        snackbar.removeClass('bm-snackbar-show');
        setTimeout(() => {
            snackbar.remove();
        }, 300);
    }
    
    
    // Initial setup
    updateToggleAllButton();
    
    // Hide other plugin notifications
    hideOtherPluginNotifications();
    
    // Set up observer to remove notifications added after page load
    setupNotificationObserver();
    
});

// Function to hide other plugin notifications
function hideOtherPluginNotifications() {
    // Hide existing notifications
    $('.notice:not(.bm-notice), .error:not(.bm-notice), .updated:not(.bm-notice), .update-nag:not(.bm-notice), .admin-notice:not(.bm-notice)').hide();
    
    // Also hide common plugin notification containers
    $('.notice-info, .notice-warning, .notice-error, .notice-success').not('.bm-notice').hide();
}

// Set up MutationObserver to catch dynamically added notifications
function setupNotificationObserver() {
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const $node = $(node);
                            
                            // Check if the added node is a notification
                            if ($node.hasClass('notice') || $node.hasClass('error') || 
                                $node.hasClass('updated') || $node.hasClass('update-nag') || 
                                $node.hasClass('admin-notice')) {
                                
                                // Hide it unless it's our notification
                                if (!$node.hasClass('bm-notice')) {
                                    $node.hide();
                                }
                            }
                            
                            // Check for notifications within the added node
                            $node.find('.notice:not(.bm-notice), .error:not(.bm-notice), .updated:not(.bm-notice), .update-nag:not(.bm-notice), .admin-notice:not(.bm-notice)').hide();
                        }
                    });
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}


// Add CSS for snackbar notifications
const style = document.createElement('style');
style.textContent = `
    
    /* Snackbar Styles */
    .bm-snackbar {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: #333;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 300px;
        max-width: 500px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 100000;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .bm-snackbar-show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    .bm-snackbar-success {
        background: #00a32a;
    }
    
    .bm-snackbar-warning {
        background: #dba617;
    }
    
    .bm-snackbar-error {
        background: #d63384;
    }
    
    .bm-snackbar-info {
        background: #2271b1;
    }
    
    .bm-snackbar .dashicons {
        font-size: 16px;
        line-height: 1;
        flex-shrink: 0;
    }
    
    .bm-snackbar-message {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
    }
    
    .bm-snackbar-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    
    .bm-snackbar-close:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .bm-snackbar-close .dashicons {
        font-size: 14px;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .bm-snackbar {
            left: 20px;
            right: 20px;
            transform: translateY(100px);
            min-width: auto;
            max-width: none;
        }
        
        .bm-snackbar-show {
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);