/**
 * WooCommerce My Account Links – Admin JS
 *
 * Handles:
 *  1. jQuery UI Sortable drag-and-drop reordering.
 *  2. Order serialization before form submission.
 *  3. Toggle switch row-level disabled visual state.
 *  4. Per-item label reset button.
 *  5. Global "Reset All" confirmation dialog.
 * 
 */

/* global mjWcAccountLinks, jQuery */

( function ( $, config ) {
	'use strict';

	// Cache frequently used selectors.
	var $sortableList = $( '#mj-sortable-list' );
	var $settingsForm = $( '#mj-settings-form' );
	var $resetForm    = $( '#mj-reset-form' );
	var $orderInput   = $( '#mj-order-input' );

	// ------------------------------------------------------------------
	// 1. DRAG-AND-DROP SORTING
	// ------------------------------------------------------------------

	/**
	 * Initialise jQuery UI Sortable on the list.
	 *
	 * The drag handle is restricted to the `.mj-drag-handle` element so that
	 * inputs and buttons inside the rows remain fully interactive.
	 */
	function initSortable() {
		if ( ! $sortableList.length ) {
			return;
		}

		$sortableList.sortable( {
			handle:      '.mj-drag-handle',
			placeholder: 'ui-sortable-placeholder',
			axis:        'y',
			tolerance:   'pointer',
			cursor:      'grabbing',
			opacity:     0.95,

			/** Announce drag start for screen readers. */
			start: function ( _event, ui ) {
				ui.item.attr( 'aria-grabbed', 'true' );
			},

			/** Clean up aria attribute on drop. */
			stop: function ( _event, ui ) {
				ui.item.removeAttr( 'aria-grabbed' );
			},
		} );

		$sortableList.disableSelection();
	}

	// ------------------------------------------------------------------
	// 2. ORDER SERIALIZATION
	// ------------------------------------------------------------------

	/**
	 * Build the ordered array of slugs from the current DOM order
	 * and populate the hidden `mj_order[]` inputs before submission.
	 *
	 * We clear the single hidden input (used only as a placeholder in PHP)
	 * and instead inject one input per slug so the server receives a proper
	 * ordered array.
	 */
	function serializeOrder() {
		// Remove the placeholder hidden input.
		$orderInput.remove();

		// Append one hidden input per row, in DOM order.
		$sortableList.find( '.mj-wc-al-item' ).each( function () {
			var slug = $( this ).data( 'slug' );
			if ( slug ) {
				$settingsForm.append(
					$( '<input>' ).attr( {
						type:  'hidden',
						name:  'mj_order[]',
						value: slug,
					} )
				);
			}
		} );
	}

	// ------------------------------------------------------------------
	// 3. TOGGLE SWITCH – ROW VISUAL STATE
	// ------------------------------------------------------------------

	/**
	 * Update row opacity to reflect the enabled/disabled toggle state.
	 *
	 * @param {jQuery} $checkbox The toggle checkbox element.
	 */
	function updateRowState( $checkbox ) {
		var $row = $checkbox.closest( '.mj-wc-al-item' );
		if ( $checkbox.is( ':checked' ) ) {
			$row.removeClass( 'mj-wc-al-item--disabled' );
		} else {
			$row.addClass( 'mj-wc-al-item--disabled' );
		}
	}

	/**
	 * Bind toggle change events (using event delegation for sortable compat).
	 */
	function bindToggleEvents() {
		$sortableList.on( 'change', '.mj-toggle__input', function () {
			updateRowState( $( this ) );
		} );
	}

	// ------------------------------------------------------------------
	// 4. PER-ITEM LABEL RESET
	// ------------------------------------------------------------------

	/**
	 * Reset an individual item's custom label to the WooCommerce default.
	 *
	 * The default label is stored in the button's `data-default` attribute
	 * (written by PHP from the WooCommerce filter output).
	 */
	function bindItemResetButtons() {
		$sortableList.on( 'click', '.mj-btn-reset-item', function () {
			var $btn         = $( this );
			var defaultLabel = $btn.data( 'default' ) || '';
			var $row         = $btn.closest( '.mj-wc-al-item' );
			var $input       = $row.find( '.mj-label-input' );

			// Clear custom value; the placeholder already shows the default.
			$input.val( '' );

			// Brief visual feedback.
			$btn.addClass( 'updated' );
			setTimeout( function () {
				$btn.removeClass( 'updated' );
			}, 600 );

			// Focus the input so the user sees the placeholder immediately.
			$input.trigger( 'focus' );
		} );
	}

	// ------------------------------------------------------------------
	// 5. GLOBAL RESET CONFIRMATION
	// ------------------------------------------------------------------

	/**
	 * Show a native confirmation dialog before the "Reset All" form submits.
	 */
	function bindResetAllConfirm() {
		$resetForm.on( 'submit', function ( e ) {
			var message = config.confirmReset || 'Are you sure you want to reset all settings?';
			if ( ! window.confirm( message ) ) { // eslint-disable-line no-alert
				e.preventDefault();
			}
		} );
	}

	// ------------------------------------------------------------------
	// 6. FORM SUBMIT – SERIALIZE BEFORE SENDING
	// ------------------------------------------------------------------

	/**
	 * Hook into the settings form submit event to serialize sort order first.
	 */
	function bindFormSubmit() {
		$settingsForm.on( 'submit', function () {
			serializeOrder();
			// Allow the default form submit to proceed.
		} );
	}

	// ------------------------------------------------------------------
	// 7. SAVE BAR LEFT OFFSET (sidebar collapse/expand)
	// ------------------------------------------------------------------

	/**
	 * Adjust the sticky save bar's `left` offset when the WP admin sidebar
	 * is toggled so it always aligns with the content area.
	 *
	 * WordPress adds / removes `body.folded` when the sidebar collapses.
	 * We replicate that check in CSS, but the MutationObserver approach
	 * below ensures immediate JS-side response if needed for dynamic layouts.
	 */
	function watchSidebarToggle() {
		if ( typeof MutationObserver === 'undefined' ) {
			return;
		}
		// No JS work needed – CSS handles `.folded .mj-wc-al-save-bar`.
		// This stub is here for future extensibility.
	}

	// ------------------------------------------------------------------
	// INIT
	// ------------------------------------------------------------------

	/**
	 * Kick everything off once the DOM is ready.
	 */
	$( function () {
		initSortable();
		bindToggleEvents();
		bindItemResetButtons();
		bindResetAllConfirm();
		bindFormSubmit();
		watchSidebarToggle();

		// Initialise existing rows' visual state on page load.
		$sortableList.find( '.mj-toggle__input' ).each( function () {
			updateRowState( $( this ) );
		} );
	} );

}( jQuery, window.mjWcAccountLinks || {} ) );
