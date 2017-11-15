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
	
	public $comments_list_table;
	
	/**
	 * PYIS_Comment_Assignment_Reply_As constructor.
	 * 
	 * @since		{{VERSION}}
	 */
	function __construct() {
		
		add_filter( 'pyis_comment_assignment_edit_comments_html', array( $this, 'add_reply_as_field' ) );
		add_filter( 'pyis_comment_assignment_assigned_comments_html', array( $this, 'add_reply_as_field' ) );
		
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
		$user_query = new WP_User_Query( array(
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'order' => 'ASC'
		) );
		
		$users = array();
		$select_field = '';
		if ( $user_query->get_total() > 0 ) {
			$users += wp_list_pluck( $user_query->get_results(), 'data', 'ID' );
		}
		
		/**
		 * Allows Default User ID for the Reply As Select Dropdown to be changed
		 * 
		 * @since		{{VERSION}}
		 * @return		integer User ID
		 */
		$default_user_id = apply_filters( 'pyis_comment_assignment_reply_as_default', 1 );
		
		$select_field .= '<select id="assigned-to-select">';
			foreach ( $users as $user_id => $user_data ) {
				$select_field .= '<option value="' . $user_id . '"' . ( ( (int) $user_id == $default_user_id ) ? ' selected' : '' ) . '>' . $user_data->user_login . '</option>';
			}
		$select_field .= '</select>';
		
		// The Select Field is just for ease of use. The hidden Input field is what actually gets submitted by WordPress via AJAX
		$insert = '<span class="reply-as-container alignright hidden">';
			$insert .= '<label for="reply-as">' . __( 'Reply As', 'pyis-comment-assignment' ) . '</label>';
			$insert .= $select_field;
			$insert .= '<input type="hidden" id="reply-as" name="reply_as" value="" />';
		$insert .= '</span>';
		
		// Grab our Reply Submit Buttons from the Object Buffer
		// The "s" at the end is the DOT-ALL modifier. This allows us to match over line-breaks
		// Here's a good explaination: https://stackoverflow.com/a/2240607
		$match = preg_match( '#<p id="replysubmit"(?:[^>]*)>(.+?)<\/p>#is', $page_content, $matches );
		
		// Remove any Line Breaks from the Reply Submit Buttons we just grabbed
		// If we remove the Line Breaks from the Object Buffer itself it produces errors for some reason
		$buttons = preg_replace( "/\r|\n|\t/", "", $matches[0] );
		
		// Place all of our injected fields after the last </div> in the Reply Submit Buttons
		$injected_buttons = substr_replace( $buttons, $insert . '<span class="waiting', strpos( $buttons, '<span class="waiting' ), 20 );
		
		// Swap the Reply Submit Buttons if the Object Buffer with our modified one
		$page_content = preg_replace( '#<p id="replysubmit"(?:[^>]*)>(.+?)<\/p>#is', $injected_buttons, $page_content );

		return $page_content;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Reply_As();