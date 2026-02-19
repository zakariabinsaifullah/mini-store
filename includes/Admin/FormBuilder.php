<?php
/**
 * Checkout Form Builder page for Mini Store.
 *
 * Registers a "Checkout Form" submenu under the Mini Store top-level menu and
 * provides a drag-and-drop form builder UI.  Administrators can pick from a
 * palette of predefined field types, reorder them, customise labels /
 * placeholders / required status, then persist the configuration to wp_options.
 *
 * Option key : ms_checkout_fields  (wp_options table)
 *
 * Each saved field is an associative array:
 *   [
 *       'id'          => string   // field identifier (e.g. 'name', 'email')
 *       'label'       => string   // display label
 *       'placeholder' => string   // input placeholder text
 *       'required'    => bool     // whether the field is required at checkout
 *       'order'       => int      // 0-based position in the form
 *   ]
 *
 * @package MiniStore\Admin
 */

declare( strict_types=1 );

namespace MiniStore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class FormBuilder
 */
final class FormBuilder {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** wp_options key for the saved field configuration. */
	const OPTION_KEY = 'ms_checkout_fields';

	/** wp_ajax_{action} hook name. */
	const AJAX_ACTION = 'ms_save_checkout_fields';

	/** Nonce action string used for verification. */
	const NONCE_ACTION = 'ms_form_builder_save';

	/** POST key that carries the nonce value. */
	const NONCE_FIELD = 'ms_form_builder_nonce';

	/** Admin page slug. */
	const PAGE_SLUG = 'ms-checkout-form';

	/**
	 * All field types available in the palette.
	 *
	 * Keys are stable field identifiers used in the saved option.
	 * 'type' mirrors the underlying HTML input type (informational).
	 *
	 * @var array<string, array{label: string, placeholder: string, icon: string, type: string}>
	 */
	const AVAILABLE_FIELDS = [
		'name'     => [
			'label'       => 'Name',
			'placeholder' => 'Enter your name',
			'icon'        => 'dashicons-admin-users',
			'type'        => 'text',
		],
		'email'    => [
			'label'       => 'Email',
			'placeholder' => 'Enter your email address',
			'icon'        => 'dashicons-email-alt',
			'type'        => 'email',
		],
		'phone'    => [
			'label'       => 'Phone',
			'placeholder' => 'Enter your phone number',
			'icon'        => 'dashicons-phone',
			'type'        => 'tel',
		],
		'message'  => [
			'label'       => 'Message',
			'placeholder' => 'Enter your message',
			'icon'        => 'dashicons-format-chat',
			'type'        => 'textarea',
		],
		'address'  => [
			'label'       => 'Address',
			'placeholder' => 'Enter your full address',
			'icon'        => 'dashicons-location',
			'type'        => 'text',
		],
		'district' => [
			'label'       => 'District',
			'placeholder' => 'Select your district',
			'icon'        => 'dashicons-building',
			'type'        => 'select',
		],
		'thana'    => [
			'label'       => 'Thana',
			'placeholder' => 'Select your thana',
			'icon'        => 'dashicons-flag',
			'type'        => 'select',
		],
		'tnc'      => [
			'label'       => 'T&C Checkbox',
			'placeholder' => '',
			'icon'        => 'dashicons-yes-alt',
			'type'        => 'checkbox',
		],
	];

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var self|null */
	private static ?self $instance = null;

	/** Screen hook suffix returned by add_submenu_page() — used for asset scoping. */
	private string $page_hook = '';

	private function __construct() {
		add_action( 'admin_menu',                            [ $this, 'register_submenu' ] );
		add_action( 'admin_enqueue_scripts',                 [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION,          [ $this, 'handle_ajax_save' ] );
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \LogicException( 'The FormBuilder singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Public helper — read saved fields anywhere in the plugin
	// -----------------------------------------------------------------------

	/**
	 * Return the saved checkout field configuration.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_fields(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		return is_array( $saved ) ? $saved : [];
	}

	// -----------------------------------------------------------------------
	// Submenu registration
	// -----------------------------------------------------------------------

	/**
	 * Add the Checkout Form page as a submenu of the Mini Store menu.
	 *
	 * @return void
	 */
	public function register_submenu(): void {
		$this->page_hook = (string) add_submenu_page(
			Menu::SLUG,
			__( 'Checkout Form – Mini Store', 'mini-store' ),
			__( 'Checkout Form', 'mini-store' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	// -----------------------------------------------------------------------
	// Asset enqueue
	// -----------------------------------------------------------------------

	/**
	 * Enqueue CSS and JS only on the Checkout Form builder page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'ms-admin-form-builder',
			MINI_STORE_URL . 'assets/css/admin-form-builder.css',
			[ 'dashicons' ],
			MINI_STORE_VERSION
		);

		wp_enqueue_script(
			'ms-admin-form-builder',
			MINI_STORE_URL . 'assets/js/admin-form-builder.js',
			[ 'jquery', 'jquery-ui-sortable' ],
			MINI_STORE_VERSION,
			true
		);

		// Pass config to the script.
		wp_localize_script(
			'ms-admin-form-builder',
			'msFB',
			[
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
				'nonceField' => self::NONCE_FIELD,
				'action'     => self::AJAX_ACTION,
				'fields'     => self::AVAILABLE_FIELDS,
				'saved'      => self::get_fields(),
				'i18n'       => [
					'saving'      => __( 'Saving…',                        'mini-store' ),
					'saved'       => __( 'Changes saved!',                 'mini-store' ),
					'error'       => __( 'Something went wrong. Please try again.', 'mini-store' ),
					'remove'      => __( 'Remove',                         'mini-store' ),
					'label'       => __( 'Label',                          'mini-store' ),
					'placeholder' => __( 'Placeholder',                    'mini-store' ),
					'required'    => __( 'Required field',                 'mini-store' ),
					'dragHint'    => __( 'Drag to reorder',                'mini-store' ),
				],
			]
		);
	}

	// -----------------------------------------------------------------------
	// Page renderer
	// -----------------------------------------------------------------------

	/**
	 * Render the Checkout Form builder page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mini-store' ) );
		}
		?>

		<div class="wrap ms-fb-wrap">

			<!-- ── Page header ─────────────────────────────────────── -->
			<div class="ms-fb-header">
				<div class="ms-fb-header__title">
					<span class="dashicons dashicons-feedback"></span>
					<div>
						<h1><?php esc_html_e( 'Checkout Form', 'mini-store' ); ?></h1>
						<p><?php esc_html_e( 'Click a field on the right to add it to your form. Drag to reorder.', 'mini-store' ); ?></p>
					</div>
				</div>

				<button id="ms-fb-save" type="button" class="button button-primary button-large ms-fb-save-btn">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Changes', 'mini-store' ); ?>
				</button>
			</div>

			<!-- ── AJAX notice (hidden until save) ─────────────────── -->
			<div id="ms-fb-notice" class="ms-fb-notice" style="display:none;" role="alert" aria-live="polite"></div>

			<!-- ── Two-column builder layout ───────────────────────── -->
			<div class="ms-fb-builder">

				<!-- Left column: Active Form canvas -->
				<div class="ms-fb-panel ms-fb-panel--canvas">

					<div class="ms-fb-panel__header">
						<span class="dashicons dashicons-forms"></span>
						<h2><?php esc_html_e( 'Active Form', 'mini-store' ); ?></h2>
					</div>

					<div id="ms-fb-canvas" class="ms-fb-canvas">

						<!-- Empty-state hint; hidden once fields are present -->
						<div class="ms-fb-canvas__empty" id="ms-fb-empty-hint" aria-hidden="true">
							<span class="dashicons dashicons-plus-alt2"></span>
							<p><?php esc_html_e( 'Click a field on the right to add it here.', 'mini-store' ); ?></p>
						</div>

					</div><!-- /#ms-fb-canvas -->

				</div><!-- /.ms-fb-panel--canvas -->

				<!-- Right column: Palette of available fields -->
				<div class="ms-fb-panel ms-fb-panel--palette">

					<div class="ms-fb-panel__header">
						<span class="dashicons dashicons-database-add"></span>
						<h2><?php esc_html_e( 'Available Fields', 'mini-store' ); ?></h2>
					</div>

					<div class="ms-fb-palette" role="list">
						<?php foreach ( self::AVAILABLE_FIELDS as $field_id => $field ) : ?>
							<button
								type="button"
								class="ms-fb-palette-btn"
								role="listitem"
								data-field-id="<?php echo esc_attr( $field_id ); ?>"
								data-default-label="<?php echo esc_attr( $field['label'] ); ?>"
								data-default-placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
								data-field-type="<?php echo esc_attr( $field['type'] ); ?>"
								aria-label="<?php
									/* translators: %s: field label */
									printf( esc_attr__( 'Add %s field', 'mini-store' ), $field['label'] );
								?>"
							>
								<span class="dashicons <?php echo esc_attr( $field['icon'] ); ?>"></span>
								<span class="ms-fb-palette-btn__label"><?php echo esc_html( $field['label'] ); ?></span>
								<span class="ms-fb-palette-btn__add dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							</button>
						<?php endforeach; ?>
					</div><!-- /.ms-fb-palette -->

				</div><!-- /.ms-fb-panel--palette -->

			</div><!-- /.ms-fb-builder -->

		</div><!-- /.ms-fb-wrap -->
		<?php
	}

	// -----------------------------------------------------------------------
	// AJAX save handler
	// -----------------------------------------------------------------------

	/**
	 * Validate, sanitize, and persist the checkout field configuration.
	 *
	 * Expected POST payload:
	 *   ms_form_builder_nonce  – WP nonce
	 *   fields[]               – array of field objects (id, label, placeholder, required, order)
	 *
	 * @return void
	 */
	public function handle_ajax_save(): void {

		// 1 ── Nonce ─────────────────────────────────────────────────────────
		$nonce = sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Security check failed. Please refresh and try again.', 'mini-store' ) ],
				403
			);
		}

		// 2 ── Capability ─────────────────────────────────────────────────────
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You do not have permission to perform this action.', 'mini-store' ) ],
				403
			);
		}

		// 3 ── Sanitize ───────────────────────────────────────────────────────
		$raw_fields  = isset( $_POST['fields'] ) && is_array( $_POST['fields'] )
			? $_POST['fields']  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			: [];

		$allowed_ids = array_keys( self::AVAILABLE_FIELDS );
		$sanitized   = [];
		$order       = 0;

		foreach ( $raw_fields as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			// Validate field ID against the allowlist.
			$id = sanitize_key( wp_unslash( $raw['id'] ?? '' ) );
			if ( ! in_array( $id, $allowed_ids, true ) ) {
				continue;
			}

			$sanitized[] = [
				'id'          => $id,
				'label'       => sanitize_text_field( wp_unslash( $raw['label']       ?? '' ) ),
				'placeholder' => sanitize_text_field( wp_unslash( $raw['placeholder'] ?? '' ) ),
				'required'    => isset( $raw['required'] ) && '1' === (string) $raw['required'],
				'order'       => $order++,
			];
		}

		// 4 ── Persist ────────────────────────────────────────────────────────
		update_option( self::OPTION_KEY, $sanitized );

		wp_send_json_success(
			[ 'message' => __( 'Checkout form saved successfully.', 'mini-store' ) ]
		);
	}
}
