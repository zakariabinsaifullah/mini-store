<?php
/**
 * Shipping Settings page for Mini Store.
 *
 * Registers a "Shipping" submenu under the Mini Store top-level menu and
 * provides a modern settings UI for configuring the store-wide shipping method.
 *
 * Shipping Methods:
 *   free   – No delivery charge.
 *   single – One flat charge applied to all orders.
 *   double – Separate charges for inside and outside Dhaka.
 *
 * Also stores the Cash on Delivery label shown at checkout.
 *
 * Option key : ms_shipping_settings  (wp_options table)
 *
 * @package MiniStore\Admin
 */

declare( strict_types=1 );

namespace MiniStore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingSettings
 */
final class ShippingSettings {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** wp_options key for the shipping settings array. */
	const OPTION_KEY   = 'ms_shipping_settings';

	/** admin-post.php action name. */
	const SAVE_ACTION  = 'ms_save_shipping';

	/** Nonce action string. */
	const NONCE_ACTION = 'ms_shipping_save';

	/** Nonce field name in the form. */
	const NONCE_FIELD  = 'ms_shipping_nonce';

	/** Admin page slug. */
	const PAGE_SLUG    = 'ms-shipping';

	/** Allowed shipping method values. */
	const METHODS = [ 'free', 'single', 'double' ];

	/** Default option values. */
	const DEFAULTS = [
		'method'         => 'free',
		'charge_single'  => '',
		'charge_dhaka'   => '',
		'charge_outside' => '',
		'cod_label'      => 'Cash on Delivery',
	];

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var self|null */
	private static ?self $instance = null;

	/** Screen hook returned by add_submenu_page() — used for asset scoping. */
	private string $page_hook = '';

	private function __construct() {
		add_action( 'admin_menu',          [ $this, 'register_submenu' ] );
		add_action( 'admin_post_' . self::SAVE_ACTION, [ $this, 'handle_save' ] );
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
		throw new \LogicException( 'The ShippingSettings singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Public helper — read settings anywhere in the plugin
	// -----------------------------------------------------------------------

	/**
	 * Return the saved shipping settings merged with defaults.
	 *
	 * @return array<string,string>
	 */
	public static function get_settings(): array {
		return wp_parse_args(
			(array) get_option( self::OPTION_KEY, [] ),
			self::DEFAULTS
		);
	}

	// -----------------------------------------------------------------------
	// Submenu registration
	// -----------------------------------------------------------------------

	/**
	 * Add the Shipping page as a submenu of the Mini Store menu.
	 *
	 * @return void
	 */
	public function register_submenu(): void {
		$this->page_hook = (string) add_submenu_page(
			Menu::SLUG,
			__( 'Shipping – Mini Store', 'mini-store' ),
			__( 'Shipping', 'mini-store' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	// -----------------------------------------------------------------------
	// Asset enqueue
	// -----------------------------------------------------------------------

	/**
	 * Enqueue CSS and JS only on the Shipping settings page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'ms-admin-shipping',
			MINI_STORE_URL . 'assets/css/admin-shipping.css',
			[],
			MINI_STORE_VERSION
		);

		wp_enqueue_script(
			'ms-admin-shipping',
			MINI_STORE_URL . 'assets/js/admin-shipping.js',
			[],
			MINI_STORE_VERSION,
			true
		);
	}

	// -----------------------------------------------------------------------
	// Page renderer
	// -----------------------------------------------------------------------

	/**
	 * Render the Shipping settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mini-store' ) );
		}

		$s       = self::get_settings();
		$method  = $s['method'];
		$saved   = isset( $_GET['settings-saved'] ) && '1' === $_GET['settings-saved'];
		$form_url = admin_url( 'admin-post.php' );
		?>

		<div class="wrap ms-shipping-wrap">

			<div class="ms-shipping-header">
				<h1><?php esc_html_e( 'Shipping', 'mini-store' ); ?></h1>
				<p><?php esc_html_e( 'Configure how shipping charges are applied to all orders in your store.', 'mini-store' ); ?></p>
			</div>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible ms-shipping-notice">
					<p><?php esc_html_e( 'Shipping settings saved.', 'mini-store' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $form_url ); ?>" id="ms-shipping-form">

				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>">

				<div class="ms-settings-card">

					<!-- ═══════════════════════ SHIPPING METHOD ══ -->
					<div class="ms-card-section">

						<div class="ms-card-section__header">
							<span class="dashicons dashicons-car"></span>
							<div>
								<h2><?php esc_html_e( 'Shipping Method', 'mini-store' ); ?></h2>
								<p><?php esc_html_e( 'Choose how delivery charges are calculated for all orders.', 'mini-store' ); ?></p>
							</div>
						</div>

						<div class="ms-method-grid" id="ms-method-grid">

							<!-- Free Delivery -->
							<label class="ms-method-card <?php echo 'free' === $method ? 'is-selected' : ''; ?>">
								<input
									type="radio"
									name="ms_shipping[method]"
									value="free"
									<?php checked( $method, 'free' ); ?>
								>
								<span class="ms-method-card__inner">
									<span class="ms-method-icon ms-method-icon--free dashicons dashicons-yes-alt"></span>
									<strong><?php esc_html_e( 'Free Delivery', 'mini-store' ); ?></strong>
									<span><?php esc_html_e( 'No charges on any order', 'mini-store' ); ?></span>
								</span>
							</label>

							<!-- Single Flat Charge -->
							<label class="ms-method-card <?php echo 'single' === $method ? 'is-selected' : ''; ?>">
								<input
									type="radio"
									name="ms_shipping[method]"
									value="single"
									<?php checked( $method, 'single' ); ?>
								>
								<span class="ms-method-card__inner">
									<span class="ms-method-icon ms-method-icon--single dashicons dashicons-money-alt"></span>
									<strong><?php esc_html_e( 'Single Flat Charge', 'mini-store' ); ?></strong>
									<span><?php esc_html_e( 'One rate for all locations', 'mini-store' ); ?></span>
								</span>
							</label>

							<!-- Double Flat Charge -->
							<label class="ms-method-card <?php echo 'double' === $method ? 'is-selected' : ''; ?>">
								<input
									type="radio"
									name="ms_shipping[method]"
									value="double"
									<?php checked( $method, 'double' ); ?>
								>
								<span class="ms-method-card__inner">
									<span class="ms-method-icon ms-method-icon--double dashicons dashicons-location-alt"></span>
									<strong><?php esc_html_e( 'Double Flat Charge', 'mini-store' ); ?></strong>
									<span><?php esc_html_e( 'Different rates inside/outside Dhaka', 'mini-store' ); ?></span>
								</span>
							</label>

						</div><!-- /.ms-method-grid -->

					</div><!-- /.ms-card-section -->

					<!-- ════════════════ SINGLE FLAT CHARGE (conditional) ══ -->
					<div
						class="ms-card-section ms-conditional"
						id="ms-single-section"
						<?php echo 'single' !== $method ? 'style="display:none;"' : ''; ?>
					>
						<div class="ms-card-section__header">
							<span class="dashicons dashicons-money-alt"></span>
							<div>
								<h2><?php esc_html_e( 'Delivery Charge', 'mini-store' ); ?></h2>
								<p><?php esc_html_e( 'This amount will be added to every order regardless of location.', 'mini-store' ); ?></p>
							</div>
						</div>

						<div class="ms-setting-row">
							<label for="ms_charge_single" class="ms-setting-label">
								<?php esc_html_e( 'Delivery Charge', 'mini-store' ); ?>
							</label>
							<div class="ms-input-wrap">
								<span class="ms-bdt">&#2547;</span>
								<input
									type="number"
									id="ms_charge_single"
									name="ms_shipping[charge_single]"
									value="<?php echo esc_attr( $s['charge_single'] ); ?>"
									min="0"
									step="1"
									placeholder="0"
								>
							</div>
						</div>

					</div><!-- /#ms-single-section -->

					<!-- ═══════════════ DOUBLE FLAT CHARGE (conditional) ══ -->
					<div
						class="ms-card-section ms-conditional"
						id="ms-double-section"
						<?php echo 'double' !== $method ? 'style="display:none;"' : ''; ?>
					>
						<div class="ms-card-section__header">
							<span class="dashicons dashicons-location-alt"></span>
							<div>
								<h2><?php esc_html_e( 'Delivery Charges by Location', 'mini-store' ); ?></h2>
								<p><?php esc_html_e( 'Set separate rates for inside and outside Dhaka.', 'mini-store' ); ?></p>
							</div>
						</div>

						<div class="ms-setting-row-group">

							<div class="ms-setting-row">
								<label for="ms_charge_dhaka" class="ms-setting-label">
									<?php esc_html_e( 'Delivery Charge (Inside Dhaka)', 'mini-store' ); ?>
								</label>
								<div class="ms-input-wrap">
									<span class="ms-bdt">&#2547;</span>
									<input
										type="number"
										id="ms_charge_dhaka"
										name="ms_shipping[charge_dhaka]"
										value="<?php echo esc_attr( $s['charge_dhaka'] ); ?>"
										min="0"
										step="1"
										placeholder="0"
									>
								</div>
							</div>

							<div class="ms-setting-row">
								<label for="ms_charge_outside" class="ms-setting-label">
									<?php esc_html_e( 'Delivery Charge (Outside Dhaka)', 'mini-store' ); ?>
								</label>
								<div class="ms-input-wrap">
									<span class="ms-bdt">&#2547;</span>
									<input
										type="number"
										id="ms_charge_outside"
										name="ms_shipping[charge_outside]"
										value="<?php echo esc_attr( $s['charge_outside'] ); ?>"
										min="0"
										step="1"
										placeholder="0"
									>
								</div>
							</div>

						</div><!-- /.ms-setting-row-group -->

					</div><!-- /#ms-double-section -->

					<!-- ══════════════════════════════ PAYMENT LABEL ══ -->
					<div class="ms-card-section ms-card-section--last">

						<div class="ms-card-section__header">
							<span class="dashicons dashicons-money"></span>
							<div>
								<h2><?php esc_html_e( 'Cash on Delivery', 'mini-store' ); ?></h2>
								<p><?php esc_html_e( 'Customize the label shown to customers at checkout.', 'mini-store' ); ?></p>
							</div>
						</div>

						<div class="ms-setting-row">
							<label for="ms_cod_label" class="ms-setting-label">
								<?php esc_html_e( 'Payment Method Label', 'mini-store' ); ?>
							</label>
							<input
								type="text"
								id="ms_cod_label"
								name="ms_shipping[cod_label]"
								value="<?php echo esc_attr( $s['cod_label'] ); ?>"
								placeholder="<?php esc_attr_e( 'Cash on Delivery', 'mini-store' ); ?>"
								class="ms-text-input"
							>
						</div>

					</div><!-- /.ms-card-section -->

				</div><!-- /.ms-settings-card -->

				<div class="ms-form-footer">
					<?php submit_button( __( 'Save Settings', 'mini-store' ), 'primary large', 'submit', false ); ?>
				</div>

			</form>

		</div><!-- /.ms-shipping-wrap -->
		<?php
	}

	// -----------------------------------------------------------------------
	// Save handler
	// -----------------------------------------------------------------------

	/**
	 * Validate, sanitize, and persist the shipping settings.
	 *
	 * @return void
	 */
	public function handle_save(): void {

		// 1 ── Nonce ─────────────────────────────────────────────────────────
		$nonce = sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'mini-store' ),
				esc_html__( 'Error', 'mini-store' ),
				[ 'response' => 403, 'back_link' => true ]
			);
		}

		// 2 ── Capability ─────────────────────────────────────────────────────
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to change these settings.', 'mini-store' ),
				esc_html__( 'Permission Denied', 'mini-store' ),
				[ 'response' => 403, 'back_link' => true ]
			);
		}

		// 3 ── Sanitize ───────────────────────────────────────────────────────
		$raw    = isset( $_POST['ms_shipping'] ) && is_array( $_POST['ms_shipping'] )
			? $_POST['ms_shipping']  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			: [];

		$method = sanitize_key( $raw['method'] ?? '' );
		if ( ! in_array( $method, self::METHODS, true ) ) {
			$method = 'free';
		}

		$settings = [
			'method'         => $method,
			'charge_single'  => floatval( sanitize_text_field( wp_unslash( $raw['charge_single']  ?? '' ) ) ),
			'charge_dhaka'   => floatval( sanitize_text_field( wp_unslash( $raw['charge_dhaka']   ?? '' ) ) ),
			'charge_outside' => floatval( sanitize_text_field( wp_unslash( $raw['charge_outside'] ?? '' ) ) ),
			'cod_label'      => sanitize_text_field( wp_unslash( $raw['cod_label'] ?? 'Cash on Delivery' ) ),
		];

		// 4 ── Persist ────────────────────────────────────────────────────────
		update_option( self::OPTION_KEY, $settings );

		// 5 ── Redirect back with success flag ────────────────────────────────
		wp_safe_redirect(
			add_query_arg(
				[
					'page'            => self::PAGE_SLUG,
					'settings-saved'  => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
