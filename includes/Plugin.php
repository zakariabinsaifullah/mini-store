<?php
/**
 * Core plugin bootstrap class.
 *
 * @package MiniStore
 */

declare( strict_types=1 );

namespace MiniStore;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Entry point for the Mini Store plugin. Implemented as a Singleton so that
 * only one instance can exist during a request lifecycle, preventing duplicate
 * hook registrations or module initialisation.
 *
 * Usage:
 *   \MiniStore\Plugin::get_instance();
 *   // or via the helper function defined in mini-store.php:
 *   mini_store();
 */
final class Plugin {

	// -----------------------------------------------------------------------
	// Singleton implementation
	// -----------------------------------------------------------------------

	/**
	 * The single instance of this class.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Private constructor – use Plugin::get_instance() instead.
	 */
	private function __construct() {
		$this->define_hooks();
	}

	/**
	 * Retrieve (or lazily create) the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning of the singleton.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the singleton.
	 *
	 * @throws \LogicException Always – singletons must not be unserialized.
	 */
	public function __wakeup(): void {
		throw new \LogicException( 'The Plugin singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/**
	 * Register top-level WordPress hooks.
	 *
	 * Module-specific hooks are registered inside each module's constructor so
	 * that responsibility stays with the owning class.
	 *
	 * @return void
	 */
	private function define_hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'init_modules' ] );
	}

	// -----------------------------------------------------------------------
	// Initialisation
	// -----------------------------------------------------------------------

	/**
	 * Load the plugin's translated strings.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'mini-store',
			false,
			dirname( plugin_basename( MINI_STORE_FILE ) ) . '/languages'
		);
	}

	/**
	 * Instantiate all plugin modules.
	 *
	 * Each module's constructor is responsible for hooking itself into
	 * WordPress. Calling get_instance() here is enough to boot each one.
	 *
	 * @return void
	 */
	public function init_modules(): void {
		PostTypes::get_instance();
		Admin\Menu::get_instance();
		Admin\MetaBoxes::get_instance();
		Admin\ProductColumns::get_instance();
		Admin\ProductDuplicator::get_instance();
		Admin\ShippingSettings::get_instance();
	}
}
