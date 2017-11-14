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
		
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		
		add_filter( 'comment_status_links', array( $this, 'comment_status_links' ) );
		
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
		   $current_screen->id !== 'comments_page_assigned_comments' ) return $status_links;
		
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
		
		global $current_screen;
		
		//$current_screen->id = 'edit-comments';
		?>
		<div class="wrap">
<h1><?php
if ( $post_id ) {
	/* translators: %s: link to post */
	printf( __( 'Assigned Comments on &#8220;%s&#8221;', 'pyis-comment-assignment' ),
		sprintf( '<a href="%1$s">%2$s</a>',
			get_edit_post_link( $post_id ),
			wp_html_excerpt( _draft_or_post_title( $post_id ), 50, '&hellip;' )
		)
	);
} else {
	_e( 'Assigned Comments', 'pyis-comment-assignment' );
}

if ( isset($_REQUEST['s']) && strlen( $_REQUEST['s'] ) ) {
	echo '<span class="subtitle">';
	/* translators: %s: search keywords */
	printf( __( 'Search results for &#8220;%s&#8221;', 'pyis-comment-assignment' ),
		wp_html_excerpt( esc_html( wp_unslash( $_REQUEST['s'] ) ), 50, '&hellip;' )
	);
	echo '</span>';
}
?></h1>
		
		<?php 
		
		wp_enqueue_script('admin-comments');
		enqueue_comment_hotkeys_js();

		if ( $post_id ) {
			$comments_count = wp_count_comments( $post_id );
			$draft_or_post_title = wp_html_excerpt( _draft_or_post_title( $post_id ), 50, '&hellip;' );
			if ( $comments_count->moderated > 0 ) {
				/* translators: 1: comments count 2: post title */
				$title = sprintf( __( 'Comments (%1$s) on &#8220;%2$s&#8221;' ),
					number_format_i18n( $comments_count->moderated ),
					$draft_or_post_title
				);
			} else {
				/* translators: %s: post title */
				$title = sprintf( __( 'Comments on &#8220;%s&#8221;' ),
					$draft_or_post_title
				);
			}
		} else {
			$comments_count = wp_count_comments();
			if ( $comments_count->moderated > 0 ) {
				/* translators: %s: comments count */
				$title = sprintf( __( 'Comments (%s)' ),
					number_format_i18n( $comments_count->moderated )
				);
			} else {
				$title = __( 'Comments' );
			}
		}

		add_screen_option( 'per_page' );
		
		PYISCOMMENTASSIGNMENT()->comments_list_table->prepare_items();
		
		PYISCOMMENTASSIGNMENT()->comments_list_table->views(); ?>

<form id="comments-form">

<?php PYISCOMMENTASSIGNMENT()->comments_list_table->search_box( __( 'Search Comments' ), 'comment' ); ?>

<?php if ( $post_id ) : ?>
<input type="hidden" name="p" value="<?php echo esc_attr( intval( $post_id ) ); ?>" />
<?php endif; ?>
<input type="hidden" name="comment_status" value="<?php echo esc_attr($comment_status); ?>" />
<input type="hidden" name="pagegen_timestamp" value="<?php echo esc_attr(current_time('mysql', 1)); ?>" />

<input type="hidden" name="_total" value="<?php echo esc_attr( PYISCOMMENTASSIGNMENT()->comments_list_table->get_pagination_arg('total_items') ); ?>" />
<input type="hidden" name="_per_page" value="<?php echo esc_attr( PYISCOMMENTASSIGNMENT()->comments_list_table->get_pagination_arg('per_page') ); ?>" />
<input type="hidden" name="_page" value="<?php echo esc_attr( PYISCOMMENTASSIGNMENT()->comments_list_table->get_pagination_arg('page') ); ?>" />

<?php if ( isset($_REQUEST['paged']) ) { ?>
	<input type="hidden" name="paged" value="<?php echo esc_attr( absint( $_REQUEST['paged'] ) ); ?>" />
<?php } ?>

<?php PYISCOMMENTASSIGNMENT()->comments_list_table->display(); ?>
</form>
</div>

<div id="ajax-response"></div>

<?php
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Assigned_Comments();