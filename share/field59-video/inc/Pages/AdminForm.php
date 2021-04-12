<?php
/**
 * @package Field59 Video
 */
namespace Inc\Pages;

use Inc\Api\SettingsApi;
use Inc\Base\BaseController;
use Inc\Api\Callbacks\AdminCallbacks;

class AdminForm extends BaseController{
	
	public function register(){
		add_action('admin_init', array($this, 'registerSettings'));
		add_action('admin_init', array($this,'addSection'));
		add_action('admin_init', array($this,'addSettingsFields'));
	}
    
    /*public static function runHook(): void {
		$instance = new static();
		$instance->addSection();
		$instance->registerSettings();
		$instance->addSettingsFields();
	}*/

    public function addSection(){
		add_settings_section(
			'field59_login_credentials',
			'Field59 Login Information', 
			array($this,'sectionCallback'),
			'field59_video_settings'
        );
        add_settings_section(
			'field59_options',
			'Field59 Options', false,
			//array($this,'sectionCallback'), 
			'field59_video_settings'
		);
    }
    
    public function addSettingsFields(){
		add_settings_field(
			'field59_username',
			'Email/Username',
			array($this,'field59UsernameCallback'),
			'field59_video_settings',
			'field59_login_credentials'
		);
		add_settings_field(
			'field59_password',
			'Field59 Password',
			array($this,'field59PasswordCallback'),
			'field59_video_settings',
			'field59_login_credentials'
        );
        add_settings_field(
			'field59_owner_override',
			'Field59 Owner Override',
			array($this,'field59OwnerOverrideCallback'),
			'field59_video_settings',
			'field59_options'
		);
    }
    
    public function registerSettings(){
		register_setting( 'field59_video_settings', 'field59_username', array(
			'type' => 'string',
			'description' => 'Please enter Field59 Username or Email',
			'sanitize_callback' => static function(?string $string): ?string {
				return (string) $string;
			}
        ));

		register_setting( 'field59_video_settings', 'field59_password', array(
			'type' => 'string',
			'description' => 'Please enter your Field59 Password',
			'sanitize_callback' => static function(?string $string): ?string {
				return (string) $string;
			}
		));
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

	public static function deleteSettings(){
		$options = array(
			'field59_username',
            'field59_password',
            'field59_owner_override'
        );

		foreach ($options as $option) {
			delete_option($option);
			delete_site_option($option);
		}
	}
}