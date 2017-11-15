( function( $ ) {
	
	$( document ).ready( function() {
		
		// Quick Edit and Reply have an inline comment editor.
		$( '#the-comment-list' ).on( 'click', '.comment-inline', function( event ) {

			event.preventDefault();

			var $el = $( this );
			
			var $replyRow = $( '#replyrow' );
			
			// Toggle visibility of the Reply As field, since Reply and Quick Edit share the same editor technically
			if ( $el.data( 'action' ) !== 'replyto' ) {
				$( '.reply-as-container', $replyRow ).addClass( 'hidden' );
			}
			else {
				
				var $select = $( '#reply-as-select', $replyRow ),
					$input = $( '#reply-as', $replyRow );
				
				// Restore to default values on each open
				$select.val( $select.data( 'default' ) );
				$input.val( $select.data( 'default' ) );
				
				$( '.reply-as-container', $replyRow ).removeClass( 'hidden' );
				
			}

		} );
		
		$( '#reply-as-select' ).on( 'change', function() {

			// Set value of Hidden Input to value of the Select
			$( '#reply-as' ).val( $( this ).val() );

		} );
		
	} );
	
} )( jQuery );