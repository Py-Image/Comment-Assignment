<?php
/**
 * Contains modifications to the default WP_List_Table for the full Comments List
 *
 * @since		{{VERSION}}
 *
 * @package PYIS_Comment_Assignment
 * @subpackage PYIS_Comment_Assignment/core/admin
 */

defined( 'ABSPATH' ) || die();

global $pyis_edit_comments_ajax;

$pyis_edit_comments_ajax = false;

final class PYIS_Comment_Assignment_Edit_Comments {
	
	public $comments_list_table;
	
	/**
	 * PYIS_Comment_Assignment_Edit_Comments constructor.
	 * 
	 * @since		{{VERSION}}
	 */
	function __construct() {
		
		add_filter( 'manage_edit-comments_columns', array( $this, 'get_columns' ) );
		add_filter( 'manage_assigned-comments_columns', array( $this, 'get_columns' ) );
		
		add_action( 'manage_comments_custom_column', array( $this, 'assign_column' ), 10, 2 );
		
		// Inject User Assignment into Quick Edit screen
		add_action( 'init', array( $this, 'start_page_capture' ), 99 );
		add_action( 'shutdown', array( $this, 'add_assignment_to_quick_edit' ), 0 );
		
		add_action( 'wp_ajax_edit-comment', array( $this, 'wp_ajax_edit_comment' ), 1 );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
		add_action( 'add_meta_boxes_comment', array( $this, 'add_meta_boxes_comment' ) );
		
		add_filter( 'comment_edit_redirect', array( $this, 'save_comment' ), 10, 2 );
		
	}
	
	/**
	 * WordPress does not provide us with a "normal" way to add columns to the Comments List Table
	 * By grabbing the Class itself using WP's internal methods, we can grab the in-use Columns and then append our own
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		array Columns
	 */
	public function get_columns() {
		
		// If we don't do this, it will show the Admin Avatar twice. This does not appear to happen with anything else
		remove_filter( 'comment_author', array( PYISCOMMENTASSIGNMENT()->comments_list_table, 'floated_admin_avatar' ) );
		
		$columns = PYISCOMMENTASSIGNMENT()->comments_list_table->get_columns();
		
		global $pagenow;
		global $current_screen;
		
		global $pyis_edit_comments_ajax;
		
		// Allow AJAX for Edit Comments to go through but disallow the Assigned Column from showing on the Assigned Comments Page
		if ( ! is_admin() ||
			$current_screen->id == 'assigned-comments' || 
		   ( $pagenow !== 'edit-comments.php' && ! $pyis_edit_comments_ajax ) ) return $columns;
		
		// Reset
		$pyis_edit_comments_ajax = false;
		
		$columns['assigned_to'] = __( 'Assigned To', 'pyis-comment-assignment' );
		
		return $columns;
		
	}
	
	/**
	 * Place our own stuff within our custom Column
	 * 
	 * @param		string  $column_name Column Name
	 * @param		integer $comment_id  Comment ID
	 *                                      
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function assign_column( $column_name, $comment_id ) {
		
		if ( $column_name !== 'assigned_to' ) return;
		
		$user_id = get_comment_meta( $comment_id, $column_name, true );
		
		if ( ! $user_id ) return;
		
		$user_data = get_userdata( $user_id );

		?>

		<a href="<?php echo admin_url( 'user-edit.php?user_id=' . $user_id ); ?>" title="<?php echo $user_data->display_name; ?>">
			<?php echo $user_data->display_name; ?>
		</a>

		<div class="assigned-to hidden">
			<?php echo get_comment_meta( $comment_id, 'assigned_to', true ); ?>
		</div>
		
		<?php 
		
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
		global $current_screen;
		
		if ( ! is_admin() ||
		   $pagenow !== 'edit-comments.php' ) return;
		
		// We do not need the additions on the Assigned Comments Sub-Page
		if ( $current_screen->id == 'assigned-comments' ) echo ob_get_clean();
		
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
		
		$select_field .= '<select id="assigned-to-select">';
			$select_field .= '<option value="">' . __( 'Select a User', 'pyis-comment-assignment' ) . '</option>';
			foreach ( $users as $user_id => $user_data ) {
				$select_field .= '<option value="' . $user_id . '">' . $user_data->user_login . '</option>';
			}
		$select_field .= '</select>';
		
		// The Select Field is just for ease of use. The hidden Input field is what actually gets submitted by WordPress via AJAX
		$insert = '<div class="inside">';
			$insert .= '<label for="assigned-to">' . __( 'Assign', 'pyis-comment-assignment' ) . '</label>';
			$insert .= $select_field;
			$insert .= '<input type="hidden" id="assigned-to" name="assigned_to" value="" />';
		$insert .= '</div>';

		// Grab our Object Buffer
		$content = ob_get_clean();
		
		// Grab our <fieldset> from the Object Buffer
		// The "s" at the end is the DOT-ALL modifier. This allows us to match over line-breaks
		// Here's a good explaination: https://stackoverflow.com/a/2240607
		$match = preg_match( '#<fieldset class="comment-reply"(?:[^>]*)>(.*)<\/fieldset>#is', $content, $matches );
		
		// Remove any Line Breaks from the <fieldset> we just grabbed
		// If we remove the Line Breaks from the Object Buffer itself it produces errors for some reason
		$fields = preg_replace( "/\r|\n/", "", $matches[0] );
		
		// Place all of our injected fields after the last </div> in the <fieldset>
		$injected_fields = substr_replace( $fields, "{$insert}</div>", strrpos( $fields, '</div>' ), 6 );
		
		// Swap the <fieldset> if the Object Buffer with our modified one
		$content = preg_replace( '#<fieldset class="comment-reply"([^>]*)>(.*)<\/fieldset>#is', $injected_fields, $content );
		
		/**
		 * Allow modification of the Edit Comments page
		 * 
		 * @since		{{VERSION}}
		 * @return		string HTML
		 */
		$content = apply_filters( 'pyis_comment_assignment_edit_comments_html', $content );

		// Echo out the modified Object Buffer. This works kind of like a Filter, but it is technically an Action
		echo $content;
		
	}
	
	/**
	 * Hook into the AJAX Callback that the Quick Edit screen uses so that any changes to our Hidden Input are saved
	 * This fires off before WP Core's does, which means when it redraws the Table Row everything is taken care of for us
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function wp_ajax_edit_comment() {
		
		check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment' );

		$comment_id = (int) $_POST['comment_ID'];
		if ( ! current_user_can( 'edit_comment', $comment_id ) )
			wp_die( -1 );
		
		// Allow unassignment
		if ( isset( $_POST['assigned_to'] ) && 
			$_POST['assigned_to'] == '' ) {
			$delete = delete_comment_meta( $comment_id, 'assigned_to' );
		}
		else if ( isset( $_POST['assigned_to'] ) && 
				$_POST['assigned_to'] !== '' ) {
			$success = update_comment_meta( $comment_id, 'assigned_to', $_POST['assigned_to'] );
		}
		
		if ( isset( $_POST['assigned_to'] ) ) {
			
			global $pyis_edit_comments_ajax;
			$pyis_edit_comments_ajax = true;
			
		}
		
	}
	
	/**
	 * Enqueue Script to update the Hidden Input with the value of the Select Field
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function admin_enqueue_scripts() {
		
		global $pagenow;
		
		if ( ! is_admin() ||
		   $pagenow !== 'edit-comments.php' ) return;
		
		wp_enqueue_script( 'pyis-comment-assignment-admin-edit-comments' );
		
	}
	
	/**
	 * Add Meta Box for Comment Assignment when on the actual Comment Editing page
	 * 
	 * @param		object $comment WP_Comment object
	 *                                    
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function add_meta_boxes_comment( $comment ) {
		
		// WordPress Core _doing_it_wrong()
		// The whole Comment Editing experience is hardcoded and so terrible
		add_meta_box(
            'pyis-comment-assignment',
            __( 'Assigned To', 'pyis-comment-assignment' ),
            array( $this, 'comment_assignment_metabox' ),
            null,
			'normal' // We cannot use "side" because the Comment Editing screen simply doesn't allow that
        );
		
	}
	
	/**
	 * Meta Box Content for the Comment Assignment Meta Box
	 * 
	 * @param		object $comment WP_Comment Object
	 *                                    
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function comment_assignment_metabox( $comment ) {
		
		// Grab all the Users and build a Select Field
		$user_query = new WP_User_Query( array(
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'order' => 'ASC'
		) );
		
		$users = array();
		if ( $user_query->get_total() > 0 ) {
			$users += wp_list_pluck( $user_query->get_results(), 'data', 'ID' );
		}
		
		$assigned_to = get_comment_meta( $comment->comment_ID, 'assigned_to', true );
		
		wp_nonce_field( 'save_comment_assignment_' . $comment->comment_ID, 'comment_assignment_nonce' );
		
		?>

		<select name="assigned_to">
			
			<option value="">
				<?php _e( 'Select a User', 'pyis-comment-assignment' ); ?>
			</option>
		
			<?php foreach ( $users as $user_id => $user_data ) : ?>
			
				<option value="<?php echo $user_id; ?>"<?php echo ( (string) $user_id == $assigned_to ) ? ' selected' : ''; ?>>
					<?php echo $user_data->display_name; ?>
				</option>

			<?php endforeach; ?>
			
		</select>

		<?php
		
	}
	
	/**
	 * This is the only way to hook into the saving of the Edit Comment form. Thanks, WP Core
	 * 
	 * @param		string  $location   The URI the user will be redirected to
	 * @param		integer $comment_id The ID of the comment being edited
	 *                                                         
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		string  The URI the user will be redirected to
	 */
	public function save_comment( $location, $comment_id ) {
		
		// Not allowed, return regular value without updating meta
		if ( ! wp_verify_nonce( $_POST['comment_assignment_nonce'], 'save_comment_assignment_' . $comment_id ) && 
			! isset( $_POST['assigned_to'] ) ) 
			return $location;

		// Update meta
		$update = update_comment_meta( 
			$comment_id, 
			'assigned_to', 
			$_POST['assigned_to']
		);

		// Return regular value after updating  
		return $location;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Edit_Comments();