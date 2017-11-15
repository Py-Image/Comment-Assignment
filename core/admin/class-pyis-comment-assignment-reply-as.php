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
		
		// Inject Reply As into Reply screen
		add_action( 'init', array( $this, 'start_page_capture' ), 99 );
		add_action( 'shutdown', array( $this, 'add_assignment_to_quick_edit' ), 0 );
		
	}
	
	/**
	 * If we're on the generic Edit Comments screen, start an Object Buffer
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function start_page_capture() {
		
		global $pagenow;
		
		if ( is_admin() && 
		   $pagenow == 'edit-comments.php' ) {
			ob_start();
		}
		
	}
	
	/**
	 * WordPress has literally no way to add to the Quick Edit screen for Comments
	 * This is the best that can be done while hopefully working into the foreseeable future
	 * We run some Regex after the Page has loaded on our Object Buffer and inject the modified <fieldset> into the Page.
	 * By doing it this way, we don't have to worry about JavaScript having any kind of nasty delay
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function add_assignment_to_quick_edit() {
		
		global $pagenow;
		
		if ( ! is_admin() ||
		   $pagenow !== 'edit-comments.php' ) return;
		
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
		$insert = '<span class="reply-as-container alignright">';
			$insert .= '<label for="reply-as">' . __( 'Reply As', 'pyis-comment-assignment' ) . '</label>';
			$insert .= $select_field;
			$insert .= '<input type="hidden" id="reply-as" name="reply_as" value="" />';
		$insert .= '</span>';

		// Grab our Object Buffer
		$content = ob_get_clean();
		
		// Grab our Reply Submit Buttons from the Object Buffer
		// The "s" at the end is the DOT-ALL modifier. This allows us to match over line-breaks
		// Here's a good explaination: https://stackoverflow.com/a/2240607
		$match = preg_match( '#<p id="replysubmit"(?:[^>]*)>(.+?)<\/p>#is', $content, $matches );
		
		// Remove any Line Breaks from the Reply Submit Buttons we just grabbed
		// If we remove the Line Breaks from the Object Buffer itself it produces errors for some reason
		$buttons = preg_replace( "/\r|\n|\t/", "", $matches[0] );
		
		// Place all of our injected fields after the last </div> in the Reply Submit Buttons
		$injected_buttons = substr_replace( $buttons, $insert . '<span class="waiting', strpos( $buttons, '<span class="waiting' ), 20 );
		
		// Swap the Reply Submit Buttons if the Object Buffer with our modified one
		$content = preg_replace( '#<p id="replysubmit"(?:[^>]*)>(.+?)<\/p>#is', $injected_buttons, $content );
		
		file_put_contents( __DIR__ . '/content.txt', $content );

		// Echo out the modified Object Buffer. This works kind of like a Filter, but it is technically an Action
		echo $content;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Reply_As();