<?php 
/**
 * @package  Field59 Video
 */
namespace Inc\Api\Callbacks;

use Inc\Base\BaseController;

class AdminCallbacks extends BaseController
{
	public function adminField59()
	{
		return require_once( "$this->plugin_path/templates/admin.php" );
	}
}