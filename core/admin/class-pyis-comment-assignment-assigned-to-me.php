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

final class PYIS_Comment_Assignment_Assigned_To_Me {
	
	/**
	 * PYIS_Comment_Assignment_Assigned_To_Me constructor.
	 * 
	 * @since		{{VERSION}}
	 */
	function __construct() {
		
		add_action( 'current_screen', array( $this, 'alter_screen' ) );
		
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		
		add_filter( 'comment_status_links', array( $this, 'comment_status_links' ) );
		
		add_action( 'pre_get_comments', array( $this, 'pre_get_comments' ) );
		
		add_filter( 'wp_count_comments', array( $this, 'wp_count_comments' ) );
		
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
		
		if ( $current_screen->id == 'comments_page_assigned_to_me' ) {
		
			$current_screen->id = 'assigned-to-me';
			
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
			__( 'Assigned to Me', 'pyis-comment-assignment' ),
			__( 'Assigned to Me', 'pyis-comment-assignment' ),
			'edit_posts', // This is what is actually checked for the Comments Menu to appear in WP Core
			'assigned_to_me',
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
		   $current_screen->id !== 'assigned-to-me' ) return $status_links;
		
		foreach ( $status_links as &$status_link ) {
			
			$status_link = str_replace( "edit-comments.php?", "edit-comments.php?page=assigned_to_me&", $status_link );
			
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
		$edit_comments = preg_replace( '#<h1([^>]*)>(.*?)' . __( 'Comments' ) . '(.*?)<\/h1>#is', '<h1$1>$2' . __( 'Assigned to Me', 'pyis-comment-assignment' ) . '$3</h1>', $edit_comments );
		
		// Add a hidden Input with our Page Slug so that any Searching or other Form Interactions always return us to our page
		$edit_comments = preg_replace( '#<form id="comments-form" method="get">#is', '$0<input type="hidden" name="page" value="assigned_to_me" /><input type="hidden" name="comment_status" value="assigned_to_me" />', $edit_comments );
		
		// Add back in the JavaScript
		foreach( $script_matches[0] as $script ) {
			
			$edit_comments = $edit_comments . $script;
			
		}
		
		/**
		 * Allow modification of the Assigned Comments page
		 * 
		 * @since		{{VERSION}}
		 * @return		string HTML
		 */
		$edit_comments = apply_filters( 'pyis_comment_assignment_assigned_to_me_html', $edit_comments );
		
		echo $edit_comments;
		
	}
	
	/**
	 * Restrict the Assigned Comments Page to only querying comments Assigned to that User
	 * 
	 * @param		object $query WP_Comment_Query
	 *                       
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function pre_get_comments( $query ) {
		
		global $current_screen;
		
		if ( $current_screen->id !== 'assigned-to-me' ) return;
			
		$query->meta_query = new WP_Meta_Query( array(
			'relation' => 'OR',
			array(
				'key' => 'assigned_to',
				'value' => get_current_user_id(),
				'compare' => '=',
			),
		) );
		
	}
	
	/**
	 * Filter wp_count_comments() on Assigned Comments pages so that it only counts for Assigned Comments
	 * This only changes the numbers in the "Views" row above the Table. Every other instance of the numbers is correct by default
	 * 
	 * @param		array   Return an empty Array to let WP handle it the way it does by default
	 * @param		integer Post ID to check for Assigned Comments in. Leave 0 for all Posts.
	 *                                                                              
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		object  Comment Stats object
	 */
	public function wp_count_comments( $comment_stats = array(), $post_id = 0 ) {
		
		global $current_screen;
		
		$user_id = get_current_user_id();
		
		// Proceed as normal if not on the Assigned Comments screen
		if ( $current_screen->id !== 'assigned-to-me' ) return $comment_stats;
		
		$count = wp_cache_get( "comments-assigned-me-{$user_id}-{$post_id}", 'counts' );
		if ( false !== $count ) {
			return $count;
		}
		
		$stats = $this->get_comment_count( $post_id );
		
		$stats['moderated'] = $stats['awaiting_moderation'];
		unset( $stats['awaiting_moderation'] );
		
		$stats_object = (object) $stats;
		
		wp_cache_set( "comments-assigned-me-{$user_id}-{$post_id}", $stats_object, 'counts' );
		
		return $stats_object;
		
	}
	
	/**
	 * Gets the Comment Count for Assigned Comments to a User
	 * This is basically a carbon copy of get_comment_count() in WP Core, but it had no way to modify the SQL to match what we needed
	 * 
	 * @param		integer $post_id Post ID to check Assigned Comments for. If 0, it checks all Posts
	 * @param		integer $user_id User ID to check Assigned Comments. If 0, it grabs the current User ID
	 *                                                                                              
	 * @access		private
	 * @since		{{VERSION}}
	 * @return		array   Comment Count data used by wp_count_comments()
	 */
	private function get_comment_count( $post_id = 0, $user_id = false ) {
		
		global $wpdb;

		$post_id = (int) $post_id;
		$user_id = ( ! $user_id ) ? get_current_user_id() : (int) $user_id;

		$where = $wpdb->prepare( "WHERE {$wpdb->commentmeta}.meta_key = 'assigned_to' AND {$wpdb->commentmeta}.meta_value = '%d'", $user_id );
		if ( $post_id > 0 ) {
			$where .= $wpdb->prepare("AND {$wpdb->comments}.comment_post_ID = %d", $post_id);
		}

		$totals = (array) $wpdb->get_results("
			SELECT comment_approved, COUNT( * ) AS total
			FROM {$wpdb->comments}
			LEFT JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id
			{$where}
			GROUP BY comment_approved
		", ARRAY_A);

		$comment_count = array(
			'approved'            => 0,
			'awaiting_moderation' => 0,
			'spam'                => 0,
			'trash'               => 0,
			'post-trashed'        => 0,
			'total_comments'      => 0,
			'all'                 => 0,
		);

		foreach ( $totals as $row ) {
			switch ( $row['comment_approved'] ) {
				case 'trash':
					$comment_count['trash'] = $row['total'];
					break;
				case 'post-trashed':
					$comment_count['post-trashed'] = $row['total'];
					break;
				case 'spam':
					$comment_count['spam'] = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					break;
				case '1':
					$comment_count['approved'] = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					$comment_count['all'] += $row['total'];
					break;
				case '0':
					$comment_count['awaiting_moderation'] = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					$comment_count['all'] += $row['total'];
					break;
				default:
					break;
			}
		}

		return $comment_count;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Assigned_To_Me();