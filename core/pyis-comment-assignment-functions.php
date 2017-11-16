<?php
/**
 * Provides helper functions.
 *
 * @since	  {{VERSION}}
 *
 * @package	PYIS_Comment_Assignment
 * @subpackage PYIS_Comment_Assignment/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		{{VERSION}}
 *
 * @return		PYIS_Comment_Assignment
 */
function PYISCOMMENTASSIGNMENT() {
	return PYIS_Comment_Assignment::instance();
}