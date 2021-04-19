<?php
/**
 * Plugin Name:     Field59 Video Integration (Rebuild)
 * Description:     Add Field59 videos to your posts and passes WordPress categories back to Field59 as tags for videos embedded into posts.
 * Author URI:      https://www.townnews.com
 * Version:         2021.02.01
 * Text Domain:     field59-video
 * Domain Path:     /languages
 *
 * @package         Field59 Video
 */

if ( ! defined( 'ABSPATH' ) ) {
	die('Hey, you can\'t access this file, human!');
}

// Require once the Composer Autoload
if( file_exists(dirname(__FILE__). '/vendor/autoload.php')){
  require_once dirname(__FILE__). '/vendor/autoload.php';
}

/**
 * The code that runs during plugin activation
 */
function activate_field59_video_plugin() {
	Inc\Base\Activate::activate();
}
register_activation_hook( __FILE__, 'activate_field59_video_plugin' );

/**
 * The code that displays custom admin notice
 */
function activation_notice(){
	Inc\Pages\AdminNotices::field59_admin_activation_notice_hook();
}
register_activation_hook( __FILE__, 'activation_notice' );

/**
 * The code that runs during plugin deactivation
 */

function deactivate_field59_video_plugin() {
	Inc\Base\Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_field59_video_plugin' );

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( 'Inc\\Init' ) ) {
	Inc\Init::register_services();
}