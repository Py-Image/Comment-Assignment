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

final class PYIS_Comment_Assignment_Edit_Comments {
	
	public $comments_list_table;
	
	/**
	 * PYIS_Comment_Assignment_Edit_Comments constructor.
	 * 
	 * @since		{{VERSION}}
	 */
	function __construct() {
		
		add_filter( 'manage_edit-comments_columns', array( $this, 'get_columns' ) );
		
		add_action( 'manage_comments_custom_column', array( $this, 'assign_column' ), 10, 2 );
		
		//add_filter( 'comment_row_actions', array( $this, 'comment_row_actions' ), 10, 2 );
		
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
		
		$this->comments_list_table = _get_list_table( 'WP_Comments_List_Table' );
		
		// If we don't do this, it will show the Admin Avatar twice. This does not appear to happen with anything else
		remove_filter( 'comment_author', array( $this->comments_list_table, 'floated_admin_avatar' ) );
		
		$columns = $this->comments_list_table->get_columns();
		
		$columns['assign'] = __( 'Assigned To', 'pyis-comment-assignment' );
		
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
		
		if ( $column_name !== 'assign' ) return;
		
		echo 'test';
		
	}
	
	public function comment_row_actions( $actions, $comment ) {
		
		$actions['assign'] = 'assign';
		
		return $actions;
		
	}
	
}

$instance = new PYIS_Comment_Assignment_Edit_Comments();
