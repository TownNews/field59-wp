<?php
/**
 * Class to manage:
 *  - Field59 api Authentication.
 *  - Authentication and Activation notices
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}
if ( ! class_exists( 'Field59_Video_Authentication' ) ) :

    class Field59_Video_Authentication {

        public static function get_instance() {
			// Store instance locally to avoid private static replication.
			static $instance = null;

			if ( null === $instance ) {
				$instance = new self();
				self::init_hooks();
			}
			return $instance;
		}

         static function init_hooks() {
            add_filter('plugin_action_links_'. plugin_basename(dirname(__FILE__ )).'/field59-video.php', array( __CLASS__, 'field59_settings_links'));
            add_action('load-plugins.php',
                function(){
                    add_filter('gettext', array( __CLASS__,'field59_activation_notice'), 99, 3 );
                }
            );
            add_action('admin_notices', array(__CLASS__,'make_field59_api_call'));
        }
        
        /**
         * Custom Plugin activation notice
        */
        public static function field59_activation_notice($translated_text, $untranslated_text){
			$old_text = array('Plugin activated.');
			$new_text = 'Thank you for using the F59 Video Integration plugin. <a href="https://manager.field59.com/setup"><strong>Click Here</strong></a> to login into your F59 account to get started. Don\'t have an account? <a href="https://www.field59.com/field59-faq"><strong>Click Here</strong></a> for more information.';

			if ( in_array( $untranslated_text, $old_text, true ) )
        		{
					$translated_text = $new_text;
					remove_filter( current_filter(), __FUNCTION__, 99 );
        		}
        	return $translated_text;
		}
        /**
         * Add Settings link on plugins page
        */
        public static function field59_settings_links($links) { 
			$settings_link = '<a href="admin.php?page=field59_video_settings">Settings</a>'; 
			array_push($links, $settings_link); 
			return $links; 
		} 
        /**
         * Make API call 
         *  
        */
        public static function make_field59_api_call(){
            $f59_user = get_field( 'field59_video_username', 'option' );
            $f59_pass = get_field( 'field59_video_password', 'option' );

            $url = "https://api.field59.com/v2/user";
            $auth = base64_encode( $f59_user .':'. $f59_pass );

            $headers = array(
                'headers' => array(
                    'Authorization' => "Basic $auth",  
                    ),
                    'sslverify' => false 
                );

            $response = wp_remote_get($url, $headers);
            $body     = wp_remote_retrieve_body( $response );
            $xml  = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

            $login_success = 'Authentication success!!';
            $login_failure = 'Email/Username & Password combination are incorrect. <a href="admin.php?page=field59_video_settings">Click Here</a> ';

            if($response['response']['code'] === 200){
                echo '<div class="notice notice-success is-dismissible">
                        <p>'.$login_success.'</p>
                    </div>';
            }else{
                echo '<div class="notice notice-error">
                        <p>'.$login_failure.'</p>
                    </div>';
                
            }
        }  
    }
Field59_Video_Authentication::get_instance();

endif;