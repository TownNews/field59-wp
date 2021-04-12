<?php
/**
 * @package Field59 Video
 */
namespace Inc\Pages;

use Inc\Api\SettingsApi;
use Inc\Base\BaseController;
use Inc\Api\AuthenticationApi;
use Inc\Api\Callbacks\AdminCallbacks;

class AdminNotices extends BaseController{

    public function register(){
      add_action('admin_notices', array($this,'field59_authentication_notice'));
      add_action('load-plugins.php',
                function(){
                add_filter('gettext', array( $this,'field59_activation_notice'), 99, 3 );
                }
            );
    }  

    public  function field59_activation_notice($translated_text, $untranslated_text){
        $old_text = array('Plugin activated.');
        $new_text = 'Thank you for using the F59 Video Integration plugin. <a href="https://manager.field59.com/setup"><strong>Click Here</strong></a> to login into your F59 account to get started. Don\'t have an account? <a href="https://www.field59.com/field59-faq"><strong>Click Here</strong></a> for more information.';

        if ( in_array( $untranslated_text, $old_text, true ) )
            {
                $translated_text = $new_text;
                remove_filter( current_filter(), __FUNCTION__, 99 );
            }
        return $translated_text;
    }

  public function field59_authentication_notice(){
        $login_success = 'Authentication success!!';
        $login_failure = 'Email/Username & Password combination are incorrect. <a href="admin.php?page=field59_video_settings">Click Here</a> ';

        $response = AuthenticationApi::make_field59_api_call( 'https://api.field59.com/v2/user', 'GET');

        $error         = false;
        //$error_details = '';
        //$body          = '';
        if (
            ! is_wp_error( $response )
            && is_array( $response )
        ) {
            if($response['response']['code'] === 200){
                echo '<div class="notice notice-success is-dismissible">
                        <p>'.$login_success.'</p>
                    </div>';
            }else{
                echo '<div class="notice notice-error">
                        <p>'.$login_failure.'</p>
                    </div>'; 
            }
        } else {
            $error = true;
        }
  }
}