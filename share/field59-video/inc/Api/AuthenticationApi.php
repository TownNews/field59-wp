<?php
/**
 * @package Field59 Video
 */
namespace Inc\Api;

class AuthenticationApi 
{

    public static function make_field59_api_call( $url, $method = 'GET', $params = array(), $body = '', $adtl_headers = array() ) {
        global $wp_version;

        $f59_user = get_option( 'field59_username', 'option' );
        $f59_pass = get_option( 'field59_password', 'option' );
        $auth = base64_encode( $f59_user .':'. $f59_pass );
        $headers = array(
            'headers' =>array(
                 'Authorization' => "Basic $auth"
                ),
            'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
            'sslverify' => false 
        );
        // Merge additional headers if applicible.
        if ( ! empty( $adtl_headers ) ) {
            $headers = array_unique( array_merge( $headers, $adtl_headers ) );
        }
       
       /* $request = array(
            'headers' => $headers,
            'method'  => $method,
            'body'    => $body,
        );*/
        
        if ( 'GET' === $method && ! empty( $params ) && is_array( $params ) ) {
            $url = add_query_arg( $params, $url );
        } elseif (
            empty( $response['body'] )
            && ! empty( $params )
        ) {
           // $request['body'] = wp_json_encode( $params );
        }
        $response = wp_remote_request( $url, $headers );

        return $response;
    }
    /*public function register(){
        add_action('admin_notices', array($this,'make_field59_api_call')); 
    }
    
    public function make_field59_api_call(){
        $f59_user = get_option( 'field59_username', 'option' );
        $f59_pass = get_option( 'field59_password', 'option' );

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
        //$xml  = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        $login_success = 'Authentication success!!';
        $login_failure = 'Email/Username & Password combination are incorrect. <a href="admin.php?page=field59_video_settings">Click Here</a> ';

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
    } */ 
}