<?php
/**
 * PSR-4-inspired autoloader for the MiniStore namespace.
 *
 * Maps the top-level namespace prefix "MiniStore\" to the includes/
 * directory so that every class file can be resolved automatically
 * without a Composer dependency.
 *
 * Mapping convention:
 *   MiniStore\Plugin          → includes/Plugin.php
 *   MiniStore\Admin\Settings  → includes/Admin/Settings.php
 *
 * @package MiniStore
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/** The namespace prefix this autoloader is responsible for. */
const MINI_STORE_NAMESPACE_PREFIX = 'MiniStore\\';

spl_autoload_register(
	static function ( string $class ): void {

		// Bail early if the class does not belong to our namespace.
		if ( strncmp( MINI_STORE_NAMESPACE_PREFIX, $class, strlen( MINI_STORE_NAMESPACE_PREFIX ) ) !== 0 ) {
			return;
		}

		// Strip the prefix to get the relative class path.
		$relative_class = substr( $class, strlen( MINI_STORE_NAMESPACE_PREFIX ) );

		// Convert namespace separators to directory separators and append .php.
		$file = MINI_STORE_DIR . 'includes' . DIRECTORY_SEPARATOR
				. str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class )
				. '.php';

		// Only require the file when it actually exists (avoids fatal errors
		// caused by other autoloaders that may share the same stack).
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
