/* global ajaxurl, cnesFunciones */
(function ( $ ) {
	'use strict';

	$( function () {

		var $selectAll  = $( '#cnes-select-all' );
		var $checks     = $( '.cnes-funcion-check' );
		var $bulkBtn    = $( '#cnes-bulk-delete-btn' );
		var $countSpan  = $( '#cnes-selected-count' );

		function syncSelectAll() {
			var total   = $checks.length;
			var checked = $checks.filter( ':checked' ).length;
			$selectAll.prop( 'indeterminate', checked > 0 && checked < total );
			$selectAll.prop( 'checked', total > 0 && checked === total );
			$countSpan.text( checked );
			$bulkBtn.prop( 'disabled', checked === 0 );
		}

		$selectAll.on( 'change', function () {
			$checks.prop( 'checked', this.checked );
			syncSelectAll();
		} );

		$checks.on( 'change', syncSelectAll );

		$bulkBtn.on( 'click', function () {
			var ids = $checks.filter( ':checked' ).map( function () {
				return $( this ).val();
			} ).get();

			if ( ! ids.length ) return;

			var msg = cnesFunciones.confirmDeleteMsg.replace( '%d', ids.length );
			if ( ! window.confirm( msg ) ) return;

			var $btn = $( this );
			$btn.prop( 'disabled', true ).text( cnesFunciones.deletingText );

			$.post( ajaxurl, {
				action : 'cnes_eliminar_funciones_masivo',
				nonce  : cnesFunciones.nonce,
				ids    : ids
			} )
			.done( function ( response ) {
				if ( response.success ) {
					window.location.reload();
				} else {
					window.alert( ( response.data && response.data.message ) || cnesFunciones.errorText );
					$btn.prop( 'disabled', false ).text( cnesFunciones.deleteText );
					syncSelectAll();
				}
			} )
			.fail( function () {
				window.alert( cnesFunciones.errorText );
				$btn.prop( 'disabled', false ).text( cnesFunciones.deleteText );
				syncSelectAll();
			} );
		} );

	} );

}( jQuery ) );
