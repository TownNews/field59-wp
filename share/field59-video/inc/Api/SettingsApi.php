<?php
/**
 * @package Field59 Video
 */

namespace Inc\Api;

class SettingsApi
{
	public $admin_subpages = array();

	public function register(){
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
	}
	public function addSubPages( array $pages ){
		$this->admin_subpages = array_merge( $this->admin_subpages, $pages );

		return $this;
	}

	public function addAdminMenu(){
		foreach ( $this->admin_subpages as $page ) {
			add_submenu_page( $page['parent_slug'], $page['page_title'], $page['menu_title'], $page['capability'], $page['menu_slug'], $page['callback'] );
		}
	}
}