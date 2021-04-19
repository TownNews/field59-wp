<?php
/**
 * @package Field59 Video
 */
namespace Inc\Api;

class AuthenticationApi {

    public static function make_field59_api_call( $url, $method = 'GET', $params = array(), $adtl_headers = array() ) {
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
        if ( 'GET' === $method && ! empty( $params ) && is_array( $params ) ) {
            $url = add_query_arg( $params, $url );
        }
        $response = wp_remote_request( $url, $headers );

        return $response;
    }
}