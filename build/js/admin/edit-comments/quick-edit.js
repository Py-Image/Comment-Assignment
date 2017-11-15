( function( $ ) {

	$( document ).on( 'ready', function() {

		// Quick Edit and Reply have an inline comment editor.
		$( '#the-comment-list' ).on( 'click', '.comment-inline', function( event ) {

			event.preventDefault();

			var $el = $( this );
			
			var $editRow = $( '#replyrow' ),
				$row = $el.closest( 'tr.comment' );
			
			$( '#assigned-to-select', $editRow ).val( $row.find( 'td.assigned_to div.assigned-to' ).text().trim() ).trigger( 'change' );

		} );

		$( '#assigned-to-select' ).on( 'change', function() {

			// Set value of Hidden Input to value of the Select
			$( '#assigned-to' ).val( $( this ).val() );

		} );

	} );

} )( jQuery );