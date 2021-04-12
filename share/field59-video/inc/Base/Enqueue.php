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

    function enqueue(){
     
    }
}