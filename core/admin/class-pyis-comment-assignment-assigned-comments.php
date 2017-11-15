<?php
/**
 * Holds the Assigned Comments Page which shows only the Assigned Comments for a User
 *
 * @since		{{VERSION}}
 *
 * @package PYIS_Comment_Assignment
 * @subpackage PYIS_Comment_Assignment/core/admin
 */

defined( 'ABSPATH' ) || die();

final class PYIS_Comment_Assignment_Assigned_Comments {
	
	/**
	 * PYIS_Comment_Assignment_Assigned_Comments constructor.
	 * 
	 * @since		{{VERSION}}
	 */
	function __construct() {
		
		add_action( 'current_screen', array( $this, 'alter_screen' ) );
		
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		
		add_filter( 'comment_status_links', array( $this, 'comment_status_links' ) );
		
	}
	
	/**
	 * Change our Screen to something that WP_Comments_List_Table likes more for some reason
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function alter_screen() {
		
		global $current_screen;
		
		if ( $current_screen->id == 'comments_page_assigned_comments' ) {
		
			$current_screen->id = 'assigned-comments';
			
		}
		
	}
	
	/**
	 * Add our Submenu Page
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function add_submenu_page() {
		
		add_submenu_page(
			'edit-comments.php',
			__( 'Assigned Comments', 'pyis-comment-assignment' ),
			__( 'Assigned Comments', 'pyis-comment-assignment' ),
			'edit_posts', // This is what is actually checked for the Comments Menu to appear in WP Core
			'assigned_comments',
			array( $this, 'submenu_page' )
		);
		
	}
	
	/**
	 * For the Assigned Comments Submenu Page, ensure all the "Views" links match our Submenu Page
	 * 
	 * @param		array $status_links Array of Comment Status <a> Links
	 *                                                         
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		array Array of Comment Status <a> Links
	 */
	public function comment_status_links( $status_links ) {
		
		global $current_screen;
		
		if ( ! is_admin() || 
		   $current_screen->id !== 'assigned-comments' ) return $status_links;
		
		foreach ( $status_links as &$status_link ) {
			
			$status_link = str_replace( "edit-comments.php?", "edit-comments.php?page=assigned_comments&", $status_link );
			
		}
		
		return $status_links;
		
	}
	
	/**
	 * Output the Submenu Page, which is basically just edit-comments.php but stripped down
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function submenu_page() {
		
		ob_start();
		include ABSPATH . '/wp-admin/edit-comments.php';
		$edit_comments = ob_get_clean();
		
		// Extract all JavaScript from the page so we can preserve it
		$match = preg_match_all( '#<script(?:.+?)>(?:.+?)<\/script>#is', $edit_comments, $script_matches );
		
		// /wp-admin/admin-footer.php is at the very end of the file and we need to remove it
		// This HTML is how it starts and it should be unique enough to prevent removal of things we don't want to remove
		$edit_comments = preg_replace( '#<div class="clear"><\/div><\/div><!-- wpbody-content -->(?:.*)#is', '', $edit_comments );
		
		// Change Title to "Assigned Comments", regardless of Langugage setting
		$edit_comments = preg_replace( '#<h1>(?:.*?)' . __( 'Comments' ) . '(?:.*?)<\/h1>#is', '<h1>' . __( 'Assigned Comments' . '</h1>', 'pyis-comment-assignment' ), $edit_comments );
		
		// Add a hidden Input with our Page Slug so that any Searching or other Form Interactions always return us to our page
		$edit_comments = preg_replace( '#<form id="comments-form" method="get">#is', '$0<input type="hidden" name="page" value="assigned_comments" />', $edit_comments );
		
		// Add back in the JavaScript
		foreach( $script_matches[0] as $script ) {
			
			$edit_comments = $edit_comments . $script;
			
		}
		
		echo $edit_comments;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Assigned_Comments();