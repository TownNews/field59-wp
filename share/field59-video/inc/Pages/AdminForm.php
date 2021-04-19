<?php
/**
 * @package Field59 Video
 */
namespace Inc\Pages;

use Inc\Api\SettingsApi;
use Inc\Api\CallBacks\AdminCallBacks;

class AdminForm {
	
	public function register(){
		add_action('admin_init', array($this, 'registerSettings'));
		add_action('admin_init', array($this,'addSection'));
		add_action('admin_init', array($this,'addSettingsFields'));
		$this->callbacks = new AdminCallBacks();
	}

    public function addSection(){
		add_settings_section(
			'field59_login_credentials',
			'Field59 Login Information', 
			array($this->callbacks,'sectionCallback'),
			'field59_video_settings'
        );
        add_settings_section(
			'field59_options',
			'Field59 Options', 
			false,
			'field59_video_settings'
		);
    }
    
    public function addSettingsFields(){
		add_settings_field(
			'field59_username',
			'Email/Username',
			array($this->callbacks,'field59UsernameCallback'),
			'field59_video_settings',
			'field59_login_credentials'
		);
		add_settings_field(
			'field59_password',
			'Field59 Password',
			array($this->callbacks,'field59PasswordCallback'),
			'field59_video_settings',
			'field59_login_credentials'
        );
        add_settings_field(
			'field59_owner_override',
			'Field59 Owner Override',
			array($this->callbacks,'field59OwnerOverrideCallback'),
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