<?php
/**
 * Gutenberg Blocks registration.
 *
 * @package MiniStore
 */

declare( strict_types=1 );

namespace MiniStore;

defined( 'ABSPATH' ) || exit;

/**
 * Class Blocks
 *
 * Handles registration of all Gutenberg blocks for the Mini Store plugin.
 */
final class Blocks {

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
	 * Private constructor – use Blocks::get_instance() instead.
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
		throw new \LogicException( 'The Blocks singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function define_hooks(): void {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_category' ], 10, 2 );
	}

	// -----------------------------------------------------------------------
	// Block Registration
	// -----------------------------------------------------------------------

	/**
	 * Register all Gutenberg blocks.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		// Register product-grid block
		register_block_type( MINI_STORE_DIR . 'build/blocks/product-grid' );
	}

	/**
	 * Register custom block category.
	 *
	 * @param array                   $categories Array of block categories.
	 * @param \WP_Block_Editor_Context $context    Block editor context.
	 * @return array Modified block categories.
	 */
	public function register_block_category( array $categories, $context ): array {
		// Check if we're in the post editor
		if ( ! ( $context instanceof \WP_Block_Editor_Context ) ) {
			return $categories;
		}

		// Add custom category at the beginning
		return array_merge(
			[
				[
					'slug'  => 'mini-store',
					'title' => __( 'Mini Store', 'mini-store' ),
					'icon'  => 'store',
				],
			],
			$categories
		);
	}
}
