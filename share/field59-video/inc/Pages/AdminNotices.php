<?php
/**
 * @package Field59 Video
 */
namespace Inc\Pages;

use Inc\Api\AuthenticationApi;

class AdminNotices {

    public function register(){
        add_action('admin_notices', array($this,'field59_authentication_notice'));
        add_action( 'admin_notices', array($this,'field59_admin_activation_notice' ));    
    }
 
    public static function field59_admin_activation_notice_hook() {
        /* Create transient data */
        set_transient( 'field59_activation_notice', true, 5 );
    }
   
    function field59_admin_activation_notice(){
    
        /* Check transient, if available display notice */
        if( get_transient( 'field59_activation_notice' ) ){
            echo'
                <div class="updated notice is-dismissible">
                    <p>Thank you for using the F59 Video Integration plugin. <a href="https://manager.field59.com/setup"><strong>Click Here</strong></a> to login into your F59 account to get started. Don\'t have an account? <a href="https://www.field59.com/field59-faq"><strong>Click Here</strong></a> for more information.</p>
                </div>';
            /* Delete transient, only display this notice once. */
            delete_transient( 'field59_activation_notice' );
        }
    }  
    
    public function field59_authentication_notice(){
            global $pagenow;
            
            $login_success = 'Authentication success!!';
            $login_failure = 'Email/Username & Password combination are incorrect. <a href="admin.php?page=field59_video_settings">Click Here</a> ';

            $response = AuthenticationApi::make_field59_api_call( 'https://api.field59.com/v2/user', 'GET');

            $error = false;
            if($pagenow == 'options-general.php' ){
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

}