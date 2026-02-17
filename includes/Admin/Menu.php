<?php
/**
 * Admin menu registration for Mini Store.
 *
 * Builds the full left-nav tree:
 *
 *   Mini Store              ← top-level (opens Dashboard)
 *   ├── Dashboard
 *   ├── Products            ← edit.php?post_type=ms_product
 *   ├── Orders              ← edit.php?post_type=ms_order
 *   └── Customers           ← edit.php?post_type=ms_customer
 *
 * Both CPTs have show_in_menu=false so this class owns the entire
 * menu structure. The parent_file / submenu_file filters restore
 * correct menu highlighting when navigating CPT screens.
 *
 * @package MiniStore\Admin
 */

declare( strict_types=1 );

namespace MiniStore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Menu
 */
final class Menu {

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	/** Slug shared by the top-level menu and Dashboard submenu. */
	const SLUG = 'mini-store';

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var self|null */
	private static ?self $instance = null;

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register' ] );

		// Restore correct highlighting when browsing CPT screens.
		add_filter( 'parent_file', [ $this, 'fix_parent_highlight' ] );
		add_filter( 'submenu_file', [ $this, 'fix_submenu_highlight' ] );
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \LogicException( 'The Menu singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Menu registration
	// -----------------------------------------------------------------------

	/**
	 * Register the full Mini Store admin menu tree.
	 *
	 * @return void
	 */
	public function register(): void {
		// Top-level entry — callback renders the Dashboard.
		add_menu_page(
			__( 'Mini Store', 'mini-store' ),   // <title>
			__( 'Mini Store', 'mini-store' ),   // sidebar label
			'manage_options',
			self::SLUG,
			[ $this, 'render_dashboard' ],
			'dashicons-store',
			25
		);

		// Dashboard — same slug as parent so clicking "Mini Store" lands here.
		add_submenu_page(
			self::SLUG,
			__( 'Dashboard – Mini Store', 'mini-store' ),
			__( 'Dashboard', 'mini-store' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render_dashboard' ]
		);

		// Products — points directly to the CPT list table.
		add_submenu_page(
			self::SLUG,
			__( 'Products – Mini Store', 'mini-store' ),
			__( 'Products', 'mini-store' ),
			'manage_options',
			'edit.php?post_type=ms_product'
		);

		// Orders — points directly to the CPT list table.
		add_submenu_page(
			self::SLUG,
			__( 'Orders – Mini Store', 'mini-store' ),
			__( 'Orders', 'mini-store' ),
			'manage_options',
			'edit.php?post_type=ms_order'
		);

		// Customers — points directly to the CPT list table.
		add_submenu_page(
			self::SLUG,
			__( 'Customers – Mini Store', 'mini-store' ),
			__( 'Customers', 'mini-store' ),
			'manage_options',
			'edit.php?post_type=ms_customer'
		);
	}

	// -----------------------------------------------------------------------
	// Menu highlight correction
	// -----------------------------------------------------------------------

	/**
	 * Ensure "Mini Store" stays highlighted in the top-level nav when the
	 * user is on a Products or Orders screen.
	 *
	 * @param string $parent_file Currently active top-level menu file.
	 * @return string
	 */
	public function fix_parent_highlight( string $parent_file ): string {
		$screen = get_current_screen();

		if ( $screen && in_array( $screen->post_type, [ 'ms_product', 'ms_order', 'ms_customer' ], true ) ) {
			return self::SLUG;
		}

		return $parent_file;
	}

	/**
	 * Ensure the correct submenu item stays highlighted when on a CPT screen
	 * (list table, edit, or "Add New" for ms_product).
	 *
	 * @param string|null $submenu_file Currently active submenu file.
	 * @return string|null
	 */
	public function fix_submenu_highlight( ?string $submenu_file ): ?string {
		$screen = get_current_screen();

		if ( $screen && in_array( $screen->post_type, [ 'ms_product', 'ms_order', 'ms_customer' ], true ) ) {
			return 'edit.php?post_type=' . $screen->post_type;
		}

		return $submenu_file;
	}

	// -----------------------------------------------------------------------
	// Page renderer
	// -----------------------------------------------------------------------

	/**
	 * Render the Dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mini-store' ) );
		}
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Mini Store — Dashboard', 'mini-store' ); ?></h1>

			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'Welcome to Mini Store! This dashboard will display your store\'s key metrics and quick links once fully implemented.', 'mini-store' ); ?>
				</p>
			</div>

			<!-- Stat cards -->
			<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:24px;">

				<?php
				$cards = [
					[
						'icon'  => 'dashicons-cart',
						'label' => __( 'Total Products', 'mini-store' ),
						'value' => '—',
						'color' => '#2271b1',
					],
					[
						'icon'  => 'dashicons-clipboard',
						'label' => __( 'Total Orders', 'mini-store' ),
						'value' => '—',
						'color' => '#d63638',
					],
					[
						'icon'  => 'dashicons-chart-bar',
						'label' => __( 'Revenue (this month)', 'mini-store' ),
						'value' => '—',
						'color' => '#00a32a',
					],
				];

				foreach ( $cards as $card ) :
					?>
					<div style="
						background:#fff;
						border:1px solid #c3c4c7;
						border-top:4px solid <?php echo esc_attr( $card['color'] ); ?>;
						border-radius:4px;
						padding:20px 24px;
						min-width:180px;
						flex:1;
					">
						<span
							class="dashicons <?php echo esc_attr( $card['icon'] ); ?>"
							style="font-size:32px;width:32px;height:32px;color:<?php echo esc_attr( $card['color'] ); ?>;"
						></span>
						<p style="font-size:28px;font-weight:700;margin:8px 0 4px;">
							<?php echo esc_html( $card['value'] ); ?>
						</p>
						<p style="color:#646970;margin:0;">
							<?php echo esc_html( $card['label'] ); ?>
						</p>
					</div>
					<?php
				endforeach;
				?>

			</div><!-- /.stat-cards -->

			<!-- Quick links -->
			<div style="margin-top:32px;">
				<h2 style="font-size:14px;text-transform:uppercase;color:#646970;letter-spacing:.05em;">
					<?php esc_html_e( 'Quick Links', 'mini-store' ); ?>
				</h2>
				<a
					href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ms_product' ) ); ?>"
					class="button button-primary"
					style="margin-right:8px;"
				>
					+ <?php esc_html_e( 'Add New Product', 'mini-store' ); ?>
				</a>
				<a
					href="<?php echo esc_url( admin_url( 'edit.php?post_type=ms_order' ) ); ?>"
					class="button button-secondary"
				>
					<?php esc_html_e( 'View All Orders', 'mini-store' ); ?>
				</a>
			</div>

			<p style="margin-top:40px;color:#646970;font-style:italic;">
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'Mini Store v%s — more features coming soon.', 'mini-store' ),
					esc_html( MINI_STORE_VERSION )
				);
				?>
			</p>

		</div><!-- /.wrap -->
		<?php
	}
}
