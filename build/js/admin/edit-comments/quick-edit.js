( function( $ ) {

	$( document ).on( 'ready', function() {

		// Quick Edit and Reply have an inline comment editor.
		$( '#the-comment-list' ).on( 'click', '.comment-inline', function( event ) {

			event.preventDefault();

			var $el = $( this );
			
			var comment_id = $el.data( 'commentId' ),
				$editRow = $( '#replyrow' ),
				$rowData = $( '#inline-' + comment_id );
			
			$( '#assigned-to-select', $editRow ).val( $( 'div.assigned-to', $rowData ).text() ).trigger( 'change' );

		} );

		$( '#assigned-to-select' ).on( 'change', function() {

			// Set value of Hidden Input to value of the Select
			$( '#assigned-to' ).val( $( this ).val() );

		} );

	} );

} )( jQuery );