<?php
/**
 * Plugin Name:       Mini Store
 * Plugin URI:        https://example.com/mini-store
 * Description:       A lightweight, custom store plugin with Products and Orders CPTs.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mini-store
 * Domain Path:       /languages
 *
 * @package MiniStore
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Plugin constants
// ---------------------------------------------------------------------------

/** Absolute path to the plugin directory, with trailing slash. */
define( 'MINI_STORE_DIR', plugin_dir_path( __FILE__ ) );

/** Public URL to the plugin directory, with trailing slash. */
define( 'MINI_STORE_URL', plugin_dir_url( __FILE__ ) );

/** Plugin version string. */
define( 'MINI_STORE_VERSION', '1.0.0' );

/** Absolute path to the main plugin file. */
define( 'MINI_STORE_FILE', __FILE__ );

// ---------------------------------------------------------------------------
// Autoloader
// ---------------------------------------------------------------------------

require_once MINI_STORE_DIR . 'includes/Autoloader.php';

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

/**
 * Returns the singleton instance of the plugin.
 *
 * Using a function wrapper keeps the global namespace clean and
 * provides a convenient accessor from outside the package.
 *
 * @return \MiniStore\Plugin
 */
function mini_store(): \MiniStore\Plugin {
	return \MiniStore\Plugin::get_instance();
}

mini_store();
