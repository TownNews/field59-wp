<?php
/**
 * Helper class to allow use of the WordPress List Table pagination methods without
 * significant duplication of code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Is not already included via WP bootstrapping for `media-upload.php`.
require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';

/**
 * Field59 List Table.
 *
 * Extension of WP List Table to reuse pagination logic.
 */
class Field59_List_Table extends WP_List_Table {

	/**
	 * Handles initializing this class and returning the singleton instance after it's been cached.
	 *
	 * @return null|Field59_List_Table
	 */
	public static function get_instance() {
		// Store instance locally to avoid private static replication.
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Update the pagination arguments for the parent's class use.
	 *
	 * @param array $args List of arguemnts.
	 * @return void
	 */
	public function set_pagination_args( $args ) {
		$this->_pagination_args = $args;
	}
	/**
	 * Calls the protected method of the parent class to display the pagination HTML.
	 *
	 * @param string $which Location of pagination (top|bottom). See WP_List_Table::pagination.
	 * @return void
	 */
	public function print_pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}
		$this->pagination( $which );
	}
}
