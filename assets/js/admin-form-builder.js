/**
 * Mini Store — Checkout Form Builder
 *
 * Handles:
 *  - Restoring previously saved fields from wp_options on page load.
 *  - Adding fields to the canvas when a palette button is clicked.
 *  - jQuery UI Sortable drag-and-drop reordering.
 *  - Live label sync between the label input and the card header.
 *  - Removing fields and re-enabling their palette button.
 *  - Collecting all field data and saving via AJAX.
 */
( function ( $ ) {
	'use strict';

	/* Guard: abort if the config object wasn't localised. */
	if ( typeof window.msFB === 'undefined' ) {
		return;
	}

	var cfg  = window.msFB;
	var i18n = cfg.i18n || {};

	/* ── Helpers ──────────────────────────────────────────────────────────── */

	/**
	 * Minimal HTML-entity escaper for safe innerHTML insertion.
	 *
	 * @param  {*}      str
	 * @return {string}
	 */
	function esc( str ) {
		return String( str == null ? '' : str )
			.replace( /&/g,  '&amp;'  )
			.replace( /</g,  '&lt;'   )
			.replace( />/g,  '&gt;'   )
			.replace( /"/g,  '&quot;' )
			.replace( /'/g,  '&#039;' );
	}

	/**
	 * Build the HTML for a single active-form field card.
	 *
	 * @param  {string}  id
	 * @param  {string}  label
	 * @param  {string}  placeholder
	 * @param  {boolean} required
	 * @param  {string}  fieldType
	 * @return {string}  HTML string
	 */
	function buildFieldItem( id, label, placeholder, required, fieldType ) {
		var reqAttr = required ? ' checked="checked"' : '';

		return (
			'<div class="ms-fb-field-item"' +
				' data-field-id="'   + esc( id )        + '"' +
				' data-field-type="' + esc( fieldType ) + '">' +

				'<div class="ms-fb-field-item__header">' +
					'<span' +
						' class="ms-fb-drag-handle dashicons dashicons-move"' +
						' title="' + esc( i18n.dragHint ) + '"' +
						' aria-hidden="true">' +
					'</span>' +
					'<span class="ms-fb-field-item__name">' + esc( label ) + '</span>' +
					'<button' +
						' type="button"' +
						' class="ms-fb-remove-btn"' +
						' aria-label="' + esc( i18n.remove ) + '">' +
						'<span class="dashicons dashicons-trash" aria-hidden="true"></span>' +
						esc( i18n.remove ) +
					'</button>' +
				'</div>' +

				'<div class="ms-fb-field-item__body">' +

					'<div class="ms-fb-field-row">' +
						'<label>' + esc( i18n.label ) + '</label>' +
						'<input' +
							' type="text"' +
							' class="ms-fb-input ms-fb-field-label"' +
							' value="' + esc( label ) + '">' +
					'</div>' +

					'<div class="ms-fb-field-row">' +
						'<label>' + esc( i18n.placeholder ) + '</label>' +
						'<input' +
							' type="text"' +
							' class="ms-fb-input ms-fb-field-placeholder"' +
							' value="' + esc( placeholder ) + '">' +
					'</div>' +

					'<div class="ms-fb-field-row ms-fb-field-row--check">' +
						'<label>' +
							'<input' +
								' type="checkbox"' +
								' class="ms-fb-field-required"' +
								reqAttr + '>' +
							' ' + esc( i18n.required ) +
						'</label>' +
					'</div>' +

				'</div>' +

			'</div>'
		);
	}

	/* ── Shorthand selectors ──────────────────────────────────────────────── */

	function $canvas()    { return $( '#ms-fb-canvas' ); }
	function $emptyHint() { return $( '#ms-fb-empty-hint' ); }
	function $notice()    { return $( '#ms-fb-notice' ); }

	function paletteBtn( id ) {
		return $( '.ms-fb-palette-btn[data-field-id="' + id + '"]' );
	}

	/* ── Empty-state hint visibility ──────────────────────────────────────── */

	function updateEmptyHint() {
		var isEmpty = $canvas().find( '.ms-fb-field-item' ).length === 0;
		$emptyHint().css( 'display', isEmpty ? 'flex' : 'none' );
	}

	/* ── Notice helper ────────────────────────────────────────────────────── */

	function showNotice( message, type ) {
		var icon = ( type === 'success' ) ? 'dashicons-yes-alt' : 'dashicons-warning';
		$notice()
			.removeClass( 'ms-fb-notice--success ms-fb-notice--error' )
			.addClass( 'ms-fb-notice--' + type )
			.html( '<span class="dashicons ' + icon + '" aria-hidden="true"></span> ' + message )
			.show();
	}

	/* ── jQuery UI Sortable ───────────────────────────────────────────────── */

	$canvas().sortable( {
		items:       '.ms-fb-field-item',
		handle:      '.ms-fb-drag-handle',
		placeholder: 'ms-fb-sortable-placeholder',
		tolerance:   'pointer',
		start: function ( event, ui ) {
			/* Keep the placeholder the same height as the dragged item. */
			ui.placeholder.height( ui.item.outerHeight() - 2 );
		},
	} );

	/* ── Restore saved fields on page load ────────────────────────────────── */

	var savedFields = cfg.saved || [];

	if ( savedFields.length ) {
		$.each( savedFields, function ( i, f ) {
			var fieldDef  = ( cfg.fields || {} )[ f.id ] || {};
			var $item     = $( buildFieldItem(
				f.id,
				f.label,
				f.placeholder,
				!! f.required,
				fieldDef.type || 'text'
			) );

			$canvas().append( $item );
			paletteBtn( f.id ).addClass( 'is-added' ).prop( 'disabled', true );
		} );

		updateEmptyHint();
	}

	/* ── Add field from palette ───────────────────────────────────────────── */

	$( document ).on( 'click', '.ms-fb-palette-btn', function () {
		var $btn = $( this );

		if ( $btn.hasClass( 'is-added' ) ) {
			return; /* Already on canvas — ignore. */
		}

		var id          = String( $btn.data( 'field-id' )            || '' );
		var label       = String( $btn.data( 'default-label' )       || '' );
		var placeholder = String( $btn.data( 'default-placeholder' ) || '' );
		var fieldType   = String( $btn.data( 'field-type' )          || 'text' );

		var $item = $( buildFieldItem( id, label, placeholder, false, fieldType ) );

		$canvas().append( $item );
		$btn.addClass( 'is-added' ).prop( 'disabled', true );
		updateEmptyHint();

		/* Scroll newly added field into view. */
		$item[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	} );

	/* ── Remove field from canvas ─────────────────────────────────────────── */

	$( document ).on( 'click', '.ms-fb-remove-btn', function () {
		var $item = $( this ).closest( '.ms-fb-field-item' );
		var id    = String( $item.data( 'field-id' ) );

		$item.remove();
		paletteBtn( id ).removeClass( 'is-added' ).prop( 'disabled', false );
		updateEmptyHint();
	} );

	/* ── Live label sync ──────────────────────────────────────────────────── */

	$( document ).on( 'input', '.ms-fb-field-label', function () {
		var val = $( this ).val().trim() || '—';
		$( this )
			.closest( '.ms-fb-field-item' )
			.find( '.ms-fb-field-item__name' )
			.text( val );
	} );

	/* ── Save via AJAX ────────────────────────────────────────────────────── */

	$( '#ms-fb-save' ).on( 'click', function () {
		var $btn     = $( this );
		var origHtml = $btn.html();

		/* Collect fields in current DOM order. */
		var fields = [];
		$canvas().find( '.ms-fb-field-item' ).each( function ( idx ) {
			var $item = $( this );
			fields.push( {
				id:          String( $item.data( 'field-id' ) ),
				label:       $item.find( '.ms-fb-field-label' ).val(),
				placeholder: $item.find( '.ms-fb-field-placeholder' ).val(),
				required:    $item.find( '.ms-fb-field-required' ).is( ':checked' ) ? '1' : '0',
				order:       idx,
			} );
		} );

		/* Disable button and show saving state. */
		$btn.prop( 'disabled', true ).text( i18n.saving || 'Saving\u2026' );
		$notice().hide();

		/* Build POST data with dynamic nonce key. */
		var postData     = { action: cfg.action, fields: fields };
		postData[ cfg.nonceField ] = cfg.nonce;

		$.post( cfg.ajaxUrl, postData )
			.done( function ( res ) {
				if ( res && res.success ) {
					showNotice(
						( res.data && res.data.message ) || i18n.saved,
						'success'
					);
				} else {
					showNotice(
						( res && res.data && res.data.message ) || i18n.error,
						'error'
					);
				}
			} )
			.fail( function () {
				showNotice( i18n.error, 'error' );
			} )
			.always( function () {
				$btn.prop( 'disabled', false ).html( origHtml );

				/* Scroll to notice so the user sees the feedback. */
				$( 'html, body' ).animate( { scrollTop: 0 }, 280 );
			} );
	} );

} )( jQuery );
