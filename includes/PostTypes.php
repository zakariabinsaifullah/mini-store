<?php
/**
 * Custom Post Type registrations for Mini Store.
 *
 * Registers:
 *  - ms_product   Public, searchable product listings.
 *  - ms_order     Private (admin-only) orders, created programmatically.
 *  - ms_customer  Private (admin-only) customer records, created programmatically.
 *
 * @package MiniStore
 */

declare( strict_types=1 );

namespace MiniStore;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostTypes
 *
 * Handles registration of all Custom Post Types used by the plugin.
 * Implemented as a Singleton; Plugin::init_modules() boots it once.
 */
final class PostTypes {

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
	 * Private constructor – attaches hooks on instantiation.
	 */
	private function __construct() {
		add_action( 'init',   [ $this, 'register' ] );

		// Disable Gutenberg for ms_product — classic TinyMCE editor is used
		// instead. REST API access is preserved via show_in_rest = true.
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_block_editor' ], 10, 2 );
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
	 * @throws \LogicException Always.
	 */
	public function __wakeup(): void {
		throw new \LogicException( 'The PostTypes singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register all Custom Post Types.
	 *
	 * Hooked to 'init' so that rewrite rules are available.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_product();
		$this->register_order();
		$this->register_customer();
	}

	/**
	 * Register the 'ms_product' CPT.
	 *
	 * Public, front-end accessible, included in search results.
	 * Supports title, block editor content, and a featured image.
	 *
	 * @return void
	 */
	private function register_product(): void {
		$labels = [
			'name'                  => _x( 'Products', 'post type general name', 'mini-store' ),
			'singular_name'         => _x( 'Product', 'post type singular name', 'mini-store' ),
			'add_new'               => __( 'Add New', 'mini-store' ),
			'add_new_item'          => __( 'Add New Product', 'mini-store' ),
			'edit_item'             => __( 'Edit Product', 'mini-store' ),
			'new_item'              => __( 'New Product', 'mini-store' ),
			'all_items'             => __( 'All Products', 'mini-store' ),
			'view_item'             => __( 'View Product', 'mini-store' ),
			'search_items'          => __( 'Search Products', 'mini-store' ),
			'not_found'             => __( 'No products found.', 'mini-store' ),
			'not_found_in_trash'    => __( 'No products found in Trash.', 'mini-store' ),
			'featured_image'        => __( 'Product Image', 'mini-store' ),
			'set_featured_image'    => __( 'Set product image', 'mini-store' ),
			'remove_featured_image' => __( 'Remove product image', 'mini-store' ),
			'menu_name'             => __( 'Products', 'mini-store' ),
		];

		register_post_type(
			'ms_product',
			[
				'labels'        => $labels,
				'description'   => __( 'Store products.', 'mini-store' ),
				'public'        => true,   // Visible on the front-end.
				'has_archive'   => true,   // Enables /products/ archive.
				'show_in_rest'  => true,   // Enables REST API; block editor disabled via filter below.
				'show_in_menu'  => false,  // Menu.php owns the sidebar entry.
				'rewrite'       => [ 'slug' => 'products', 'with_front' => false ],
				'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			]
		);
	}

	/**
	 * Register the 'ms_order' CPT.
	 *
	 * Not publicly accessible (public=false). The admin list table is kept
	 * visible (show_ui=true) for manual inspection, but new orders can only
	 * be created programmatically – the "Add New" capability is locked at
	 * the WordPress capability level ('do_not_allow').
	 *
	 * @return void
	 */
	private function register_order(): void {
		$labels = [
			'name'               => _x( 'Orders', 'post type general name', 'mini-store' ),
			'singular_name'      => _x( 'Order', 'post type singular name', 'mini-store' ),
			'edit_item'          => __( 'Edit Order', 'mini-store' ),
			'all_items'          => __( 'All Orders', 'mini-store' ),
			'not_found'          => __( 'No orders found.', 'mini-store' ),
			'not_found_in_trash' => __( 'No orders found in Trash.', 'mini-store' ),
			'menu_name'          => __( 'Orders', 'mini-store' ),
		];

		register_post_type(
			'ms_order',
			[
				'labels'             => $labels,
				'description'        => __( 'Store orders (programmatic only).', 'mini-store' ),

				// Keep off the front-end entirely.
				'public'             => false,
				'publicly_queryable' => false,
				'show_in_rest'       => false,

				// UI visible in admin; Menu.php owns the sidebar entry.
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_nav_menus'  => false,

				'supports'           => [ 'title' ],
				'rewrite'            => false,
				'query_var'          => false,

				// Lock "Add New" at the capability level so no code path
				// (REST, admin, direct wp_insert_post) can create orders
				// unless granted explicitly.
				'capability_type'    => 'post',
				'capabilities'       => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap'       => true,
			]
		);
	}

	/**
	 * Register the 'ms_customer' CPT.
	 *
	 * Not publicly accessible (public=false). Admin UI is kept visible for
	 * inspection and editing. Customer records are created programmatically
	 * on order completion – manual "Add New" is locked at the capability level.
	 *
	 * @return void
	 */
	private function register_customer(): void {
		$labels = [
			'name'               => _x( 'Customers', 'post type general name', 'mini-store' ),
			'singular_name'      => _x( 'Customer', 'post type singular name', 'mini-store' ),
			'edit_item'          => __( 'Edit Customer', 'mini-store' ),
			'all_items'          => __( 'All Customers', 'mini-store' ),
			'not_found'          => __( 'No customers found.', 'mini-store' ),
			'not_found_in_trash' => __( 'No customers found in Trash.', 'mini-store' ),
			'menu_name'          => __( 'Customers', 'mini-store' ),
		];

		register_post_type(
			'ms_customer',
			[
				'labels'             => $labels,
				'description'        => __( 'Store customers (programmatic only).', 'mini-store' ),

				// Keep off the front-end entirely.
				'public'             => false,
				'publicly_queryable' => false,
				'show_in_rest'       => false,

				// UI visible in admin; Menu.php owns the sidebar entry.
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_nav_menus'  => false,

				'supports'           => [ 'title' ],
				'rewrite'            => false,
				'query_var'          => false,

				// Lock "Add New" – customers are created on order completion only.
				'capability_type'    => 'post',
				'capabilities'       => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap'       => true,
			]
		);
	}

	// -----------------------------------------------------------------------
	// Block editor
	// -----------------------------------------------------------------------

	/**
	 * Force the classic TinyMCE editor for ms_product.
	 *
	 * Returning false here tells WordPress to skip Gutenberg regardless of the
	 * 'show_in_rest' value. The REST API remains available for programmatic
	 * access; only the admin editing experience reverts to the classic editor.
	 *
	 * @param bool   $use_block_editor Whether the block editor should be used.
	 * @param string $post_type        The post type slug being evaluated.
	 * @return bool
	 */
	public function disable_block_editor( bool $use_block_editor, string $post_type ): bool {
		if ( 'ms_product' === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}
}
