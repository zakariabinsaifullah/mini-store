<?php
/**
 * Custom admin columns for the ms_product list table.
 *
 * Adds: Thumbnail, Regular Price (sortable), Sale Price,
 *       Stock (sortable), Shipping costs, Free Delivery flag.
 *
 * @package MiniStore\Admin
 */

declare( strict_types=1 );

namespace MiniStore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductColumns
 */
final class ProductColumns {

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var self|null */
	private static ?self $instance = null;

	private function __construct() {
		// Define which columns exist and their order.
		add_filter( 'manage_ms_product_posts_columns',        [ $this, 'define_columns' ] );

		// Render cell content for each custom column.
		add_action( 'manage_ms_product_posts_custom_column', [ $this, 'render_column' ], 10, 2 );

		// Declare which custom columns are sortable.
		add_filter( 'manage_edit-ms_product_sortable_columns', [ $this, 'sortable_columns' ] );

		// Modify the WP_Query when a sortable column header is clicked.
		add_action( 'pre_get_posts', [ $this, 'handle_sorting' ] );

		// Column CSS – only on the products list table screen.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \LogicException( 'The ProductColumns singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Column definition
	// -----------------------------------------------------------------------

	/**
	 * Define the column set and their order.
	 *
	 * Returning a completely new array lets us control ordering precisely
	 * rather than inserting into WP's default array.
	 *
	 * @param array<string,string> $columns Default columns from WordPress.
	 * @return array<string,string>
	 */
	public function define_columns( array $columns ): array {
		return [
			'cb'               => $columns['cb'],
			'ms_thumbnail'     => __( 'Image', 'mini-store' ),
			'title'            => __( 'Product', 'mini-store' ),
			'ms_regular_price' => __( 'Price', 'mini-store' ),
			'ms_sale_price'    => __( 'Sale Price', 'mini-store' ),
			'ms_stock_qty'     => __( 'Stock', 'mini-store' ),
			'date'             => $columns['date'] ?? __( 'Date', 'mini-store' ),
		];
	}

	// -----------------------------------------------------------------------
	// Column rendering
	// -----------------------------------------------------------------------

	/**
	 * Dispatch cell rendering to the appropriate private method.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Current post ID.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		match ( $column ) {
			'ms_thumbnail'     => $this->render_thumbnail( $post_id ),
			'ms_regular_price' => $this->render_regular_price( $post_id ),
			'ms_sale_price'    => $this->render_sale_price( $post_id ),
			'ms_stock_qty'     => $this->render_stock( $post_id ),
			default            => null,
		};
	}

	/**
	 * Render the product thumbnail cell.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_thumbnail( int $post_id ): void {
		$thumb = get_the_post_thumbnail( $post_id, [ 48, 48 ] );

		if ( $thumb ) {
			printf( '<div class="ms-col-thumb">%s</div>', $thumb ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core handles thumbnail escaping.
		} else {
			echo '<div class="ms-col-thumb ms-col-thumb--empty">'
				. '<span class="dashicons dashicons-format-image"></span>'
				. '</div>';
		}
	}

	/**
	 * Render the regular price cell.
	 * Strikethrough styling is applied when an active sale price exists.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_regular_price( int $post_id ): void {
		$price    = (float) get_post_meta( $post_id, '_ms_regular_price', true );
		$sale_raw = get_post_meta( $post_id, '_ms_sale_price', true );
		$has_sale = '' !== $sale_raw && (float) $sale_raw > 0 && (float) $sale_raw < $price;

		if ( $price > 0 ) {
			$class = $has_sale ? 'ms-col-price ms-col-price--striked' : 'ms-col-price';
			printf(
				'<span class="%s">&#2547;%s</span>',
				esc_attr( $class ),
				esc_html( number_format_i18n( $price, 2 ) )
			);
		} else {
			echo '<span class="ms-col-empty">—</span>';
		}
	}

	/**
	 * Render the sale price cell with a discount percentage badge.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_sale_price( int $post_id ): void {
		$regular = (float) get_post_meta( $post_id, '_ms_regular_price', true );
		$sale    = get_post_meta( $post_id, '_ms_sale_price', true );

		if ( '' !== $sale && (float) $sale > 0 && (float) $sale < $regular ) {
			$sale_f = (float) $sale;
			$pct    = (int) round( ( ( $regular - $sale_f ) / $regular ) * 100 );

			printf(
				'<span class="ms-col-price ms-col-price--sale">&#2547;%s</span>'
				. '<span class="ms-col-badge ms-col-badge--discount">-%d%%</span>',
				esc_html( number_format_i18n( $sale_f, 2 ) ),
				$pct
			);
		} else {
			echo '<span class="ms-col-empty">—</span>';
		}
	}

	/**
	 * Render the stock quantity cell with a coloured status badge.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_stock( int $post_id ): void {
		$raw = get_post_meta( $post_id, '_ms_stock_qty', true );

		if ( '' === $raw ) {
			echo '<span class="ms-col-empty">—</span>';
			return;
		}

		$qty = (int) $raw;

		if ( 0 === $qty ) {
			echo '<span class="ms-col-badge ms-col-badge--out">'
				. esc_html__( 'Out of Stock', 'mini-store' )
				. '</span>';
		} elseif ( $qty <= 5 ) {
			printf(
				'<span class="ms-col-badge ms-col-badge--low">%s</span>',
				/* translators: %d: stock quantity */
				esc_html( sprintf( __( '%d left', 'mini-store' ), $qty ) )
			);
		} else {
			printf(
				'<span class="ms-col-badge ms-col-badge--in">%s</span>',
				/* translators: %s: formatted stock quantity */
				esc_html( sprintf( __( '%s in stock', 'mini-store' ), number_format_i18n( $qty ) ) )
			);
		}
	}



	// -----------------------------------------------------------------------
	// Sortable columns
	// -----------------------------------------------------------------------

	/**
	 * Register which columns are sortable and the URL parameter they produce.
	 *
	 * The value (right side) becomes the `orderby` query var — handled below
	 * in handle_sorting().
	 *
	 * @param array<string,string> $columns Existing sortable columns.
	 * @return array<string,string>
	 */
	public function sortable_columns( array $columns ): array {
		$columns['ms_regular_price'] = 'ms_price';   // orderby=ms_price in URL
		$columns['ms_stock_qty']     = 'ms_stock';   // orderby=ms_stock in URL
		return $columns;
	}

	/**
	 * Translate the custom orderby values into meta-based WP_Query args.
	 *
	 * Only runs on the main query for ms_product in the admin, so it cannot
	 * accidentally affect front-end or secondary queries.
	 *
	 * @param \WP_Query $query The current query object (passed by reference).
	 * @return void
	 */
	public function handle_sorting( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'ms_product' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'ms_price' === $orderby ) {
			$query->set( 'meta_key', '_ms_regular_price' );
			$query->set( 'orderby',  'meta_value_num' );
		}

		if ( 'ms_stock' === $orderby ) {
			$query->set( 'meta_key', '_ms_stock_qty' );
			$query->set( 'orderby',  'meta_value_num' );
		}
	}

	// -----------------------------------------------------------------------
	// Assets
	// -----------------------------------------------------------------------

	/**
	 * Enqueue column styles on the ms_product list table screen only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'ms_product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'ms-admin-columns',
			MINI_STORE_URL . 'assets/css/admin-columns.css',
			[],
			MINI_STORE_VERSION
		);
	}
}
