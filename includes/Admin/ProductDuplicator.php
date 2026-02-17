<?php
/**
 * Product Duplicator — adds a "Duplicate" row action to the ms_product list table.
 *
 * Clicking the link copies the post record, all post meta (including product
 * fields and the featured image reference), and all taxonomy terms to a new
 * post with status = 'draft'. The user is then redirected to the new draft's
 * edit screen so they can review and adjust before publishing.
 *
 * Security:
 *   - Nonce is scoped per-post:  ms_duplicate_product_{post_id}
 *   - current_user_can( 'edit_post' ) gate on both link generation and handler
 *   - Post-type check prevents the handler being misused on other CPTs
 *
 * @package MiniStore\Admin
 */

declare( strict_types=1 );

namespace MiniStore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductDuplicator
 */
final class ProductDuplicator {

	/** Admin action name used in the URL and admin_post hook. */
	const ACTION = 'ms_duplicate_product';

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var self|null */
	private static ?self $instance = null;

	private function __construct() {
		// Inject the Duplicate link into row actions.
		add_filter( 'post_row_actions', [ $this, 'add_row_action' ], 10, 2 );

		// Handle the duplicate request (admin-post.php dispatcher).
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle_duplicate' ] );

		// Show a success banner on the new post's edit screen.
		add_action( 'admin_notices', [ $this, 'show_success_notice' ] );
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \LogicException( 'The ProductDuplicator singleton cannot be unserialized.' );
	}

	// -----------------------------------------------------------------------
	// Row action
	// -----------------------------------------------------------------------

	/**
	 * Insert a "Duplicate" link before the "Trash" action.
	 *
	 * @param array<string,string> $actions Default row actions.
	 * @param \WP_Post             $post    Current post object.
	 * @return array<string,string>
	 */
	public function add_row_action( array $actions, \WP_Post $post ): array {
		if ( 'ms_product' !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url(
				sprintf( 'admin-post.php?action=%s&post=%d', self::ACTION, $post->ID )
			),
			self::ACTION . '_' . $post->ID
		);

		$link = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr(
				sprintf(
					/* translators: %s: product title */
					__( 'Duplicate "%s" as a draft', 'mini-store' ),
					get_the_title( $post )
				)
			),
			esc_html__( 'Duplicate', 'mini-store' )
		);

		// Rebuild the array to insert Duplicate just before Trash.
		$new_actions = [];
		foreach ( $actions as $key => $action ) {
			if ( 'trash' === $key ) {
				$new_actions[ self::ACTION ] = $link;
			}
			$new_actions[ $key ] = $action;
		}

		// Fallback: Trash action may not exist (e.g. already in trash view).
		if ( ! isset( $new_actions[ self::ACTION ] ) ) {
			$new_actions[ self::ACTION ] = $link;
		}

		return $new_actions;
	}

	// -----------------------------------------------------------------------
	// Duplicate handler
	// -----------------------------------------------------------------------

	/**
	 * Perform the duplication and redirect.
	 *
	 * Hooked to admin_post_{action} — only logged-in users reach this point.
	 *
	 * @return void
	 */
	public function handle_duplicate(): void {

		// 1 ── Resolve and validate the source post ───────────────────────────
		$post_id  = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$original = $post_id ? get_post( $post_id ) : null;

		if ( ! $original || 'ms_product' !== $original->post_type ) {
			wp_die(
				esc_html__( 'Product not found.', 'mini-store' ),
				esc_html__( 'Duplicate Error', 'mini-store' ),
				[ 'response' => 400, 'back_link' => true ]
			);
		}

		// 2 ── Nonce verification ─────────────────────────────────────────────
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::ACTION . '_' . $post_id ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'mini-store' ),
				esc_html__( 'Duplicate Error', 'mini-store' ),
				[ 'response' => 403, 'back_link' => true ]
			);
		}

		// 3 ── Capability check ───────────────────────────────────────────────
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die(
				esc_html__( 'You do not have permission to duplicate this product.', 'mini-store' ),
				esc_html__( 'Permission Denied', 'mini-store' ),
				[ 'response' => 403, 'back_link' => true ]
			);
		}

		// 4 ── Create the new post ─────────────────────────────────────────────
		$new_post_id = wp_insert_post(
			[
				'post_title'     => $original->post_title . ' ' . __( '(Copy)', 'mini-store' ),
				'post_content'   => $original->post_content,
				'post_excerpt'   => $original->post_excerpt,
				'post_status'    => 'draft',                   // Always a draft.
				'post_type'      => 'ms_product',
				'post_author'    => get_current_user_id(),
				'post_parent'    => $original->post_parent,
				'menu_order'     => $original->menu_order,
				'comment_status' => $original->comment_status,
				'ping_status'    => $original->ping_status,
			],
			true   // Return WP_Error on failure.
		);

		if ( is_wp_error( $new_post_id ) ) {
			wp_die(
				esc_html( $new_post_id->get_error_message() ),
				esc_html__( 'Duplicate Error', 'mini-store' ),
				[ 'response' => 500, 'back_link' => true ]
			);
		}

		// 5 ── Copy all post meta ─────────────────────────────────────────────
		// get_post_meta() without a key returns every meta row already unserialized.
		// add_post_meta() is used (not update_post_meta) to correctly preserve
		// keys that legitimately have multiple values.
		$all_meta = get_post_meta( $post_id );

		if ( ! empty( $all_meta ) ) {
			// Internal WP bookkeeping keys that must NOT be copied.
			$skip_keys = [ '_edit_lock', '_edit_last' ];

			foreach ( $all_meta as $meta_key => $meta_values ) {
				if ( in_array( $meta_key, $skip_keys, true ) ) {
					continue;
				}

				foreach ( $meta_values as $meta_value ) {
					add_post_meta( $new_post_id, $meta_key, $meta_value );
				}
			}
		}

		// 6 ── Copy taxonomy terms ─────────────────────────────────────────────
		$taxonomies = get_object_taxonomies( 'ms_product', 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			$term_ids = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! empty( $term_ids ) && ! is_wp_error( $term_ids ) ) {
				wp_set_object_terms( $new_post_id, $term_ids, $taxonomy );
			}
		}

		// 7 ── Redirect to the new draft's edit screen ─────────────────────────
		wp_safe_redirect(
			add_query_arg(
				[ 'ms_duplicated' => '1' ],
				get_edit_post_link( $new_post_id, 'url' )
			)
		);
		exit;
	}

	// -----------------------------------------------------------------------
	// Success notice
	// -----------------------------------------------------------------------

	/**
	 * Display a dismissible banner on the edit screen of the newly created draft.
	 *
	 * Only fires when the ms_duplicated query arg is present, which is appended
	 * by handle_duplicate() to the redirect URL.
	 *
	 * @return void
	 */
	public function show_success_notice(): void {
		if ( empty( $_GET['ms_duplicated'] ) || '1' !== $_GET['ms_duplicated'] ) {
			return;
		}

		$list_url = admin_url( 'edit.php?post_type=ms_product' );

		printf(
			'<div class="notice notice-success is-dismissible">'
			. '<p><strong>%s</strong> &mdash; %s</p>'
			. '</div>',
			esc_html__( 'Product duplicated successfully.', 'mini-store' ),
			sprintf(
				/* translators: %s: URL to the products list */
				wp_kses(
					__( 'You are now editing the draft copy. <a href="%s">Back to Products</a>.', 'mini-store' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( $list_url )
			)
		);
	}
}
