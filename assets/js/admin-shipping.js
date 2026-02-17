/* =============================================================================
   Mini Store — Shipping Settings Page Interactions
   Runs only on the Shipping settings page (enqueued conditionally).
   ============================================================================= */

/* jshint esversion: 6 */
( function () {
	'use strict';

	// ── DOM references ──────────────────────────────────────────────────────────
	const methodRadios   = document.querySelectorAll( 'input[name="ms_shipping[method]"]' );
	const methodCards    = document.querySelectorAll( '.ms-method-card' );
	const singleSection  = document.getElementById( 'ms-single-section' );
	const doubleSection  = document.getElementById( 'ms-double-section' );

	if ( ! methodRadios.length ) return;

	// ── Show / hide conditional sections based on selected method ──────────────
	function applyMethod( method ) {
		// Update card selected state.
		methodCards.forEach( function ( card ) {
			const radio = card.querySelector( 'input[type="radio"]' );
			card.classList.toggle( 'is-selected', radio && radio.value === method );
		} );

		// Show/hide charge sections with a smooth transition.
		toggleSection( singleSection, method === 'single' );
		toggleSection( doubleSection, method === 'double' );
	}

	function toggleSection( section, show ) {
		if ( ! section ) return;

		if ( show ) {
			section.style.display = 'block';
			// Trigger the CSS animation by briefly removing and re-adding the class.
			section.classList.remove( 'ms-conditional' );
			void section.offsetWidth; // force reflow
			section.classList.add( 'ms-conditional' );
		} else {
			section.style.display = 'none';
		}
	}

	// ── Attach listeners ────────────────────────────────────────────────────────
	methodRadios.forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			applyMethod( this.value );
		} );
	} );

	// Also handle clicking the card label directly.
	methodCards.forEach( function ( card ) {
		card.addEventListener( 'click', function () {
			const radio = card.querySelector( 'input[type="radio"]' );
			if ( radio ) {
				radio.checked = true;
				applyMethod( radio.value );
			}
		} );
	} );

	// ── Sync state on initial page load ─────────────────────────────────────────
	( function initState() {
		let selected = 'free';

		methodRadios.forEach( function ( radio ) {
			if ( radio.checked ) selected = radio.value;
		} );

		applyMethod( selected );
	} )();

} )();
