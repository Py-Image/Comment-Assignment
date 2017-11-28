<?php
/**
 * Adds the "Reply As" field to all Comment List Tables
 *
 * @since		{{VERSION}}
 *
 * @package PYIS_Comment_Assignment
 * @subpackage PYIS_Comment_Assignment/core/admin
 */

defined( 'ABSPATH' ) || die();

final class PYIS_Comment_Assignment_Reply_As {
	
	/**
	 * PYIS_Comment_Assignment_Reply_As constructor.
	 * 
	 * @since		{{VERSION}}
	 */
	function __construct() {
		
		add_filter( 'pyis_comment_assignment_edit_comments_html', array( $this, 'add_reply_as_field' ) );
		
		add_action( 'wp_ajax_replyto-comment', array( $this, 'wp_ajax_replyto_comment' ), 1 );
		
	}
	
	/**
	 * Since we've done the dirty work on the Edit Comments and Assigned Comments pages, we have a reasonable place to Filter
	 * Since this goes on both pages, may as well DRY it up a bit
	 * 
	 * @param		string $page_content HTML
	 *                              
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		string HTML
	 */
	public function add_reply_as_field( $page_content ) {
		
		// Grab all the Users and build a Select Field
		$user_query = new WP_User_Query( apply_filters( 'pyis_comment_assignment_reply_as_dropdown_query', array(
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'order' => 'ASC'
		) ) );
		
		$users = array();
		$select_field = '';
		if ( $user_query->get_total() > 0 ) {
			
			// Alter User Query to only have results for Users with the edit_posts Capability
			// WP_User_Query does not support this
			PYISCOMMENTASSIGNMENT()->user_query_filter_cap( $user_query, 'edit_posts' );
			
			$users += wp_list_pluck( $user_query->get_results(), 'data', 'ID' );
			
		}
		
		/**
		 * Allows Default User ID for the Reply As Select Dropdown to be changed
		 * 
		 * @since		{{VERSION}}
		 * @return		integer User ID
		 */
		$default_user_id = apply_filters( 'pyis_comment_assignment_reply_as_default', 1 );
		
		$select_field .= '<select id="reply-as-select" data-default="' . $default_user_id . '">';
			foreach ( $users as $user_id => $user_data ) {
				$select_field .= '<option value="' . $user_id . '"' . ( ( (int) $user_id == $default_user_id ) ? ' selected' : '' ) . '>' . $user_data->user_login . '</option>';
			}
		$select_field .= '</select>';
		
		// The Select Field is just for ease of use. The hidden Input field is what actually gets submitted by WordPress via AJAX
		$insert = '<span class="reply-as-container alignright hidden">';
			$insert .= '<label for="reply-as">' . __( 'Reply As', 'pyis-comment-assignment' ) . '</label>';
			$insert .= $select_field;
			$insert .= '<input type="hidden" id="reply-as" name="reply_as" value="' . $default_user_id . '" />';
		$insert .= '</span>';
		
		// Grab our Reply Submit Buttons from the Object Buffer
		// The "s" at the end is the DOT-ALL modifier. This allows us to match over line-breaks
		// Here's a good explaination: https://stackoverflow.com/a/2240607
		$match = preg_match( '#id="replysubmit"(?:[^>]*)>(.+?)<\/p>#is', $page_content, $matches );
		
		// Remove any Line Breaks from the Reply Submit Buttons we just grabbed
		// If we remove the Line Breaks from the Object Buffer itself it produces errors for some reason
		$buttons = preg_replace( "/\r|\n|\t/", "", $matches[0] );
		
		// Place all of our injected fields just before the Waiting Spinner. With CSS it appears just after due to floating
		$injected_buttons = substr_replace( $buttons, $insert . '<span class="waiting', strpos( $buttons, '<span class="waiting' ), 20 );
		
		// Swap the Reply Submit Buttons if the Object Buffer with our modified one
		$page_content = preg_replace( '#id="replysubmit"(?:[^>]*)>(.+?)<\/p>#is', $injected_buttons, $page_content );

		return $page_content;
		
	}
	
	/**
	 * Override the Current User for the AJAX Call made for Replying from the WP Dashboard
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function wp_ajax_replyto_comment() {
		
		global $current_user;
		
		$current_user = false;
		
		if ( ! isset( $_POST['reply_as'] ) ) return;
		
		// Temporarily fake our Current User
		wp_set_current_user( (int) $_POST['reply_as'] );
		
		// Here were are forcing our Nonce to be valid after changing the Current User
		// This is necessary as the one passed with the Form is for the Logged in User, not the one you're replying as
		$force_nonce = wp_create_nonce( 'replyto-comment' );
		$_REQUEST['_ajax_nonce-replyto-comment'] = $force_nonce;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Reply_As();