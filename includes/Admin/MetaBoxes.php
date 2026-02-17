<?php
/**
 * Product meta box – Pricing, Inventory, and Shipping fields.
 *
 * Registers a single "Product Details" meta box on the ms_product edit screen
 * and handles secure save logic for all custom meta fields.
 *
 * Meta keys managed:
 *   _ms_regular_price  (float)  Regular / list price.
 *   _ms_sale_price     (float)  Optional discounted price.
 *   _ms_stock_qty      (int)    Stock quantity.
 *   _ms_product_label  (string) Short badge label (max 40 chars).
 *   _ms_weight         (string) Product weight, e.g. '500g' or '1kg'.
 *   _ms_dimensions     (string) Dimensions or size, e.g. '10x5x2 cm'.
 *
 * @package MiniStore\Admin
 */

declare( strict_types=1 );

namespace MiniStore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class MetaBoxes
 */
final class MetaBoxes {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	const NONCE_ACTION = 'ms_product_meta_save';
	const NONCE_FIELD  = 'ms_product_meta_nonce';

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var self|null */
	private static ?self $instance = null;

	private function __construct() {
		add_action( 'add_meta_boxes',         [ $this, 'register' ] );
		add_action( 'save_post_ms_product',   [ $this, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \LogicException( 'The MetaBoxes singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Asset enqueue
	// -----------------------------------------------------------------------

	/**
	 * Enqueue CSS and JS only on the ms_product edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'ms_product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'ms-admin-metabox',
			MINI_STORE_URL . 'assets/css/admin-metabox.css',
			[],
			MINI_STORE_VERSION
		);

		wp_enqueue_script(
			'ms-admin-metabox',
			MINI_STORE_URL . 'assets/js/admin-metabox.js',
			[],
			MINI_STORE_VERSION,
			true   // Load in footer.
		);
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Register the Product Details meta box.
	 *
	 * @return void
	 */
	public function register(): void {
		add_meta_box(
			'ms_product_details',
			__( 'Product Details', 'mini-store' ),
			[ $this, 'render' ],
			'ms_product',
			'normal',   // Main column, below the editor.
			'high'      // Appears before other normal meta boxes.
		);
	}

	// -----------------------------------------------------------------------
	// Renderer
	// -----------------------------------------------------------------------

	/**
	 * Output the meta box HTML.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		// Retrieve saved values.
		$regular_price  = get_post_meta( $post->ID, '_ms_regular_price',  true );
		$sale_price     = get_post_meta( $post->ID, '_ms_sale_price',     true );
		$stock_qty      = get_post_meta( $post->ID, '_ms_stock_qty',      true );
		$product_label  = get_post_meta( $post->ID, '_ms_product_label',  true );
		$weight         = get_post_meta( $post->ID, '_ms_weight',         true );
		$dimensions     = get_post_meta( $post->ID, '_ms_dimensions',     true );

		// Nonce field for save verification.
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>

		<div class="ms-meta-box">

			<!-- ═══════════════════════════════════════════ PRICING ══ -->
			<div class="ms-section">

				<h3 class="ms-section-title">
					<span class="dashicons dashicons-tag"></span>
					<?php esc_html_e( 'Pricing', 'mini-store' ); ?>
				</h3>

				<div class="ms-fields-grid">

					<!-- Regular Price -->
					<div class="ms-field">
						<label for="_ms_regular_price">
							<?php esc_html_e( 'Regular Price', 'mini-store' ); ?>
						</label>
						<div class="ms-input-wrap">
							<span class="ms-prefix">৳</span>
							<input
								type="number"
								id="_ms_regular_price"
								name="_ms_regular_price"
								value="<?php echo esc_attr( $regular_price ); ?>"
								step="0.01"
								min="0"
								placeholder="0.00"
							>
						</div>
					</div>

					<!-- Sale Price -->
					<div class="ms-field ms-field--sale">
						<label for="_ms_sale_price">
							<?php esc_html_e( 'Sale Price', 'mini-store' ); ?>
						</label>
						<div class="ms-input-wrap">
							<span class="ms-prefix">৳</span>
							<input
								type="number"
								id="_ms_sale_price"
								name="_ms_sale_price"
								value="<?php echo esc_attr( $sale_price ); ?>"
								step="0.01"
								min="0"
								placeholder="0.00"
								<?php echo '' !== $sale_price ? 'class="has-value"' : ''; ?>
							>
							<span class="ms-sale-badge"><?php esc_html_e( 'SALE', 'mini-store' ); ?></span>
						</div>
					</div>

				</div><!-- /.ms-fields-grid -->

				<!-- Live savings indicator (JS-driven) -->
				<div id="ms-savings-bar" class="ms-savings-bar" style="display:none;">
					<span class="dashicons dashicons-yes-alt"></span>
					<span id="ms-savings-text"></span>
				</div>

			</div><!-- /.ms-section -->

			<!-- ══════════════════════════════════════════ INVENTORY ══ -->
			<div class="ms-section">

				<h3 class="ms-section-title">
					<span class="dashicons dashicons-archive"></span>
					<?php esc_html_e( 'Inventory', 'mini-store' ); ?>
				</h3>

				<div class="ms-fields-grid">

					<!-- Stock Quantity -->
					<div class="ms-field">
						<label for="_ms_stock_qty">
							<?php esc_html_e( 'Stock Quantity', 'mini-store' ); ?>
						</label>
						<div class="ms-input-wrap ms-input-wrap--no-prefix">
							<input
								type="number"
								id="_ms_stock_qty"
								name="_ms_stock_qty"
								value="<?php echo esc_attr( $stock_qty ); ?>"
								min="0"
								step="1"
								placeholder="0"
							>
						</div>
						<span id="ms-stock-status" class="ms-stock-status"></span>
					</div>

					<!-- Product Label -->
					<div class="ms-field">
						<label for="_ms_product_label">
							<?php esc_html_e( 'Product Label', 'mini-store' ); ?>
						</label>
						<input
							type="text"
							id="_ms_product_label"
							name="_ms_product_label"
							value="<?php echo esc_attr( $product_label ); ?>"
							placeholder="<?php esc_attr_e( 'e.g. New Arrival, Best Seller…', 'mini-store' ); ?>"
							maxlength="40"
							class="ms-label-input"
						>
						<span class="ms-label-hint">
							<?php esc_html_e( 'Shown as a badge on the product. Leave blank to hide.', 'mini-store' ); ?>
						</span>
					</div>

				</div><!-- /.ms-fields-grid -->

			</div><!-- /.ms-section -->

			<!-- ══════════════════════════════════════ SPECIFICATIONS ══ -->
			<div class="ms-section ms-section--last">

				<h3 class="ms-section-title">
					<span class="dashicons dashicons-info-outline"></span>
					<?php esc_html_e( 'Specifications', 'mini-store' ); ?>
				</h3>

				<div class="ms-fields-grid">

					<!-- Weight -->
					<div class="ms-field">
						<label for="_ms_weight">
							<?php esc_html_e( 'Weight', 'mini-store' ); ?>
						</label>
						<input
							type="text"
							id="_ms_weight"
							name="_ms_weight"
							value="<?php echo esc_attr( $weight ); ?>"
							placeholder="<?php esc_attr_e( 'e.g. 500g or 1kg', 'mini-store' ); ?>"
							maxlength="60"
							class="ms-label-input"
						>
					</div>

					<!-- Dimensions / Size -->
					<div class="ms-field">
						<label for="_ms_dimensions">
							<?php esc_html_e( 'Dimensions / Size', 'mini-store' ); ?>
						</label>
						<input
							type="text"
							id="_ms_dimensions"
							name="_ms_dimensions"
							value="<?php echo esc_attr( $dimensions ); ?>"
							placeholder="<?php esc_attr_e( 'e.g. 10×5×2 cm or L / XL / XXL', 'mini-store' ); ?>"
							maxlength="60"
							class="ms-label-input"
						>
					</div>

				</div><!-- /.ms-fields-grid -->

			</div><!-- /.ms-section -->

		</div><!-- /.ms-meta-box -->

		<?php
	}

	// -----------------------------------------------------------------------
	// Save handler
	// -----------------------------------------------------------------------

	/**
	 * Persist meta field values on post save.
	 *
	 * Security chain:
	 *   1. Nonce verification.
	 *   2. Skip autosave / revision requests.
	 *   3. Current-user capability check.
	 *   4. Sanitize and store each field.
	 *
	 * @param int       $post_id Post ID being saved.
	 * @param \WP_Post  $post    Post object.
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {

		// 1 ── Nonce verification ────────────────────────────────────────────
		$nonce = isset( $_POST[ self::NONCE_FIELD ] )
			? sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		// 2 ── Skip autosave and post revisions ──────────────────────────────
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// 3 ── Capability check ───────────────────────────────────────────────
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 4a ── Float fields (price) ─────────────────────────────────────────
		$float_keys = [
			'_ms_regular_price',
			'_ms_sale_price',
		];

		foreach ( $float_keys as $key ) {
			$raw   = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$value = floatval( $raw );
			update_post_meta( $post_id, $key, $value );
		}

		// 4b ── Integer fields (stock) ────────────────────────────────────────
		$int_keys = [ '_ms_stock_qty' ];

		foreach ( $int_keys as $key ) {
			$value = isset( $_POST[ $key ] ) ? absint( wp_unslash( $_POST[ $key ] ) ) : 0;
			update_post_meta( $post_id, $key, $value );
		}

		// 4c ── Text fields (label, weight, dimensions) ───────────────────
		$text_keys = [ '_ms_product_label', '_ms_weight', '_ms_dimensions' ];

		foreach ( $text_keys as $key ) {
			update_post_meta(
				$post_id,
				$key,
				sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) )
			);
		}

	}
}
