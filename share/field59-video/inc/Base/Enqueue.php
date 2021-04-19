<?php
/**
 * @package Field59 Video
 */
namespace Inc\Base;

use \Inc\Base\BaseController;
/**
*
*/
class Enqueue extends BaseController
{
    public function register(){
      add_action('admin_enqueue_scripts', array( $this, 'enqueue') );
    }
  /**
   * Enqueues script and styles and defined media library iframe callback.
   */
    function enqueue(){
      wp_enqueue_script( 'field59-search-videos', $this->plugin_url. 'scripts/media-upload-search.js');
      wp_enqueue_script( 'field59-inline-embed-video', $this->plugin_url. 'scripts/inline-embed-video.js' );
      wp_enqueue_style( 'field59-video-admin-media', $this->plugin_url.'styles/admin-media.css');
      // Localize the script to pass PHP vars
      wp_localize_script(
        'field59-inline-embed-video', 'f59_var',
        array(
          'post_id' => intval( $_GET['post_id'] ),
        )
      );
    }
    
}