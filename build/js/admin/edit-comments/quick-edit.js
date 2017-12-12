( function( $ ) {

	$( document ).on( 'ready', function() {

		// Quick Edit and Reply have an inline comment editor.
		$( '#the-comment-list' ).on( 'click', '.comment-inline', function( event ) {

			event.preventDefault();

			var $el = $( this ),
				commentID = $el.data( 'comment-id' );
			
			var $editRow = $( '#replyrow' ),
				$row = $el.closest( 'tr.comment' );
			
			var assignedTo = function() {
				
				var temp = '';
				
				$.ajax( {
					'async': false,
					'type': 'POST',
					'url': ajaxurl,
					'data': {
						'comment_ID': commentID,
						'action': 'pyis_get_comment_assignment',
						'_ajax_nonce-replyto-comment': $( '#_ajax_nonce-replyto-comment' ).val(),
					},
					'success': function ( response ) {
						temp = response.data;
					}
				} );
				
				return temp;
			
			}();
			
			$( '#assigned-to-select', $editRow ).val( assignedTo ).trigger( 'change' );

		} );

		$( '#assigned-to-select' ).on( 'change', function() {

			// Set value of Hidden Input to value of the Select
			$( '#assigned-to' ).val( $( this ).val() );

		} );

	} );

} )( jQuery );