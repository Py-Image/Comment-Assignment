<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
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
 * @since		1.0.0
 *
 * @return		PYIS_Comment_Assignment
 */
function PYISCOMMENTASSIGNMENT() {
	return PYIS_Comment_Assignment::instance();
}