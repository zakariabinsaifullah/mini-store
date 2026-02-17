/* =============================================================================
   Mini Store — Product Meta Box Interactions
   Runs only on ms_product edit screens (enqueued conditionally in MetaBoxes.php).
   ============================================================================= */

/* jshint esversion: 6 */
( function () {
	'use strict';

	// ── DOM references ──────────────────────────────────────────────────────────
	const freeDeliveryToggle = document.getElementById( '_ms_is_free_delivery' );
	const shippingFields     = document.getElementById( 'ms-shipping-fields' );
	const toggleText         = document.querySelector( '.ms-toggle-text' );
	const regularPriceInput  = document.getElementById( '_ms_regular_price' );
	const salePriceInput     = document.getElementById( '_ms_sale_price' );
	const saleBadge          = document.querySelector( '.ms-sale-badge' );
	const savingsBar         = document.getElementById( 'ms-savings-bar' );
	const savingsText        = document.getElementById( 'ms-savings-text' );
	const stockInput         = document.getElementById( '_ms_stock_qty' );
	const stockStatus        = document.getElementById( 'ms-stock-status' );

	// ── Helper: format number with commas ───────────────────────────────────────
	function fmt( number ) {
		return number.toLocaleString( 'en-BD', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		} );
	}

	// ============================================================================
	// 1. Free Delivery Toggle
	// ============================================================================
	function applyFreeDeliveryState() {
		if ( ! freeDeliveryToggle || ! shippingFields ) return;

		const isFree = freeDeliveryToggle.checked;

		shippingFields.classList.toggle( 'ms-is-disabled', isFree );

		if ( toggleText ) {
			toggleText.textContent = isFree ? 'On' : 'Off';
		}
	}

	if ( freeDeliveryToggle ) {
		freeDeliveryToggle.addEventListener( 'change', applyFreeDeliveryState );
		applyFreeDeliveryState(); // Sync with server-rendered state on page load.
	}

	// ============================================================================
	// 2. Pricing — sale badge + savings bar + validation
	// ============================================================================
	function updatePricingUi() {
		if ( ! salePriceInput ) return;

		const saleVal = salePriceInput.value.trim();
		const reg     = parseFloat( regularPriceInput ? regularPriceInput.value : '' );
		const sale    = parseFloat( saleVal );

		// ── Green highlight + SALE badge ──────────────────────────────────────
		if ( saleVal !== '' && ! isNaN( sale ) ) {
			salePriceInput.classList.add( 'has-value' );
		} else {
			salePriceInput.classList.remove( 'has-value' );
		}

		// ── Savings indicator ─────────────────────────────────────────────────
		const showSavings = savingsBar &&
			savingsText &&
			! isNaN( reg ) &&
			! isNaN( sale ) &&
			sale < reg &&
			sale >= 0 &&
			reg > 0;

		if ( showSavings ) {
			const savings = reg - sale;
			const pct     = Math.round( ( savings / reg ) * 100 );
			savingsText.textContent =
				'Customer saves ৳' + fmt( savings ) + ' — ' + pct + '% off';
			savingsBar.style.display = 'flex';
		} else if ( savingsBar ) {
			savingsBar.style.display = 'none';
		}

		// ── Inline validation ─────────────────────────────────────────────────
		if ( ! isNaN( reg ) && ! isNaN( sale ) && sale >= reg ) {
			salePriceInput.setCustomValidity(
				'Sale price must be lower than the regular price.'
			);
		} else {
			salePriceInput.setCustomValidity( '' );
		}
	}

	if ( regularPriceInput ) {
		regularPriceInput.addEventListener( 'input', updatePricingUi );
	}
	if ( salePriceInput ) {
		salePriceInput.addEventListener( 'input', updatePricingUi );
	}
	updatePricingUi(); // Sync on page load (existing saved values).

	// ============================================================================
	// 3. Stock Quantity — live status badge
	// ============================================================================
	function updateStockStatus() {
		if ( ! stockInput || ! stockStatus ) return;

		const raw = stockInput.value.trim();
		const qty = parseInt( raw, 10 );

		// Reset classes.
		stockStatus.className = 'ms-stock-status';

		if ( raw === '' || isNaN( qty ) ) {
			stockStatus.textContent = '';
			return;
		}

		if ( qty === 0 ) {
			stockStatus.classList.add( 'ms-stock-status--out' );
			stockStatus.textContent = '✕  Out of Stock';
		} else if ( qty <= 5 ) {
			stockStatus.classList.add( 'ms-stock-status--low' );
			stockStatus.textContent = '⚠  Low Stock (' + qty + ' left)';
		} else {
			stockStatus.classList.add( 'ms-stock-status--in' );
			stockStatus.textContent = '✓  In Stock (' + qty + ')';
		}
	}

	if ( stockInput ) {
		stockInput.addEventListener( 'input', updateStockStatus );
		updateStockStatus(); // Sync on page load.
	}

	// ============================================================================
	// 4. Keep #ms_product_details always expanded
	// ============================================================================
	const metaBox = document.getElementById( 'ms_product_details' );

	if ( metaBox ) {
		// Strip 'closed' immediately on load in case user prefs stored it.
		metaBox.classList.remove( 'closed' );

		// Watch for any future class mutations (WP postboxes.js or user prefs
		// via postboxes.save_state) and remove 'closed' the instant it appears.
		new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				if (
					mutation.type === 'attributes' &&
					mutation.attributeName === 'class' &&
					metaBox.classList.contains( 'closed' )
				) {
					metaBox.classList.remove( 'closed' );
				}
			} );
		} ).observe( metaBox, { attributes: true, attributeFilter: [ 'class' ] } );
	}

} )();
