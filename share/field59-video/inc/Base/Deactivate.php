<?php
/**
 * @package Field59 Video
 */

namespace Inc\Base;

class Deactivate
{
  function deactivate(){
    flush_rewrite_rules();
  }

}