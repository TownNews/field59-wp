<?php 
/**
 * @package  Field59 Video
 */
namespace Inc\Api\Callbacks;

use Inc\Base\BaseController;

class AdminCallBacks extends BaseController{
	public function adminField59(){
		return require_once( "$this->plugin_path/templates/admin.php" );
	}

	public function sectionCallback(){
		echo '<p>Don\'t have an account?<a href="https://www.field59.com/"> Click here</a> for more information.</p>';
    }
    
    public function field59UsernameCallback(){
		echo '<input name="field59_username" title="Field59 Email/Username"  id="field59_username" type="text" value="'.esc_attr(get_option('field59_username')).'" />';
	}

	public function field59PasswordCallback(){
		echo '<input name="field59_password" title="Field59 Password" autocomplete="off" id="field59_password" type="password" value="'.esc_attr(get_option('field59_password')).'" />';
	}

    public function field59OwnerOverrideCallback(){
		echo '<input name="field59_owner_override" title="Field59 Owner Override" id="field59_owner_override" type="text" value="'.esc_attr(get_option('field59_owner_override')).'" />';
	}
}