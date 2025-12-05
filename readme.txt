=== Centralized Block Manager ===
Contributors: abhijitnage
Tags: blocks, gutenberg, editor, block-management, post-types
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage and control which blocks are available in the WordPress editor with granular control by post type.

== Description ==

Centralized Block Manager gives you complete control over which blocks are available in the WordPress Gutenberg editor. Perfect for agencies, developers, and site administrators who need to customize the editing experience for their clients or team members.

= Key Features =

* **Global Block Control** - Enable or disable any block across your entire website
* **Post Type Specific Management** - Control which blocks are available for specific post types (posts, pages, custom post types)
* **Clean, Modern Interface** - Intuitive grid-based interface for easy block management
* **Auto-Save Functionality** - Changes are automatically saved as you make them
* **Search and Filter** - Easily find blocks by name or namespace
* **Hierarchical Block Support** - Automatically handles parent-child block relationships
* **Plugin Block Detection** - Automatically detects and manages blocks from third-party plugins

= Perfect For =

* **Agencies** - Simplify the editing experience for clients by removing unnecessary blocks
* **Developers** - Control which blocks are available during theme development
* **Site Administrators** - Maintain consistency across multi-author websites
* **Content Managers** - Streamline workflows by focusing on essential blocks only

= How It Works =

1. Install and activate the plugin
2. Navigate to Tools > Block Manager in your WordPress admin
3. Use the toggle switches to enable/disable blocks globally or per post type
4. Changes are automatically saved - no need to click a save button!

The plugin respects existing content - disabling a block only prevents new instances from being added. Existing content using disabled blocks remains intact.

= Developer Friendly =

* Clean, well-documented code
* WordPress coding standards compliant
* Hooks and filters for customization
* Translation ready

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/centralized-block-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Tools > Block Manager to configure your block settings.

== Frequently Asked Questions ==

= What happens to existing content when I disable a block? =

Nothing! Disabling a block only prevents new instances from being added to posts. All existing content using that block will remain intact and functional.

= Can I control blocks for specific post types? =

Yes! You can enable or disable blocks globally, or choose to disable them only for specific post types like posts, pages, or custom post types.

= Does this work with blocks from other plugins? =

Absolutely! The plugin automatically detects and manages blocks from any source - WordPress core blocks, theme blocks, and third-party plugin blocks.

= Will this slow down my website? =

No. The plugin only loads on admin pages and uses efficient filtering methods that don't impact frontend performance.

= Can I export/import my block settings? =

Currently, settings are stored in your WordPress database. Export/import functionality may be added in future versions.

== Screenshots ==

1. Main block management interface showing grid layout of all available blocks
2. Individual block card with global and post-type specific controls
3. Search and filter functionality for finding specific blocks
4. Clean interface showing only enabled blocks for better user experience

== Changelog ==

= 1.0.0 =
* Initial release
* Global block management functionality
* Post type specific block controls
* Auto-save functionality with debouncing
* Search and filter capabilities
* Modern grid-based interface
* Plugin block detection and management
* Hierarchical block relationship support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Centralized Block Manager. Provides comprehensive block management for WordPress Gutenberg editor.

== Support ==

For support, feature requests, or bug reports, please visit our [GitHub repository](https://github.com/block-manager/centralized-block-manager) or the WordPress.org support forums.

== Privacy ==

This plugin does not collect, store, or transmit any user data. All settings are stored locally in your WordPress database.