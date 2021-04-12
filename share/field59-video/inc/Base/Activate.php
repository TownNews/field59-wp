<?php
/**
 * @package Field59 Video
 */

namespace Inc\Base;

class Activate
{
    public static function activate(){
    
        flush_rewrite_rules();
    }
}