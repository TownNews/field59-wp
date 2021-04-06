<?php
/**
 * Plugin Name:     Field59 Video Integration
 * Description:     Add Field59 videos to your posts and passes Rayos categories back to Field59 as tags for videos embedded into articles.
 * Author URI:      https://www.townnews.com
 * Version:         2020.07.17
 * Text Domain:     field59-video
 * Domain Path:     /languages
 *
 * @package         Field59_Video
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'FIELD59_VIDEO_VERSION', '2020.07.17' );

require_once __DIR__ . '/class-field59-video-admin.php';
require_once __DIR__ . '/class-field59-video-tagging.php';
require_once __DIR__ . '/class-field59-shortcodes.php';
require_once __DIR__ . '/class-field59-list-table.php';
