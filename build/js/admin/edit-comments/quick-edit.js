( function( $ ) {
	
	$( document ).on( 'ready', function() {
		
		$( '#assigned-to-select' ).on( 'change', function() {
			
			// Set value of Hidden Input to value of the Select
			$( '#assigned-to' ).val( $( this ).val() );
			
		} );
		
	} );
	
} )( jQuery );