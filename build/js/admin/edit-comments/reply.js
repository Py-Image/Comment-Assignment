( function( $ ) {
	
	$( document ).ready( function() {
		
		// Quick Edit and Reply have an inline comment editor.
		$( '#the-comment-list' ).on( 'click', '.comment-inline', function( event ) {

			event.preventDefault();

			var $el = $( this );
			
			var $editRow = $( '#replyrow' );
			
			// Toggle visibility of the Reply As field, since Reply and Quick Edit share the same editor technically
			if ( $el.data( 'action' ) !== 'replyto' ) {
				$( '.reply-as-container', $editRow ).addClass( 'hidden' );
			}
			else {
				$( '.reply-as-container', $editRow ).removeClass( 'hidden' );
			}

		} );
		
	} );
	
} )( jQuery );