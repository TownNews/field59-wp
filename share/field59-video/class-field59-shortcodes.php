<?php
/**
 * Class to manage Field59 Video intergration shortcode functionality.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'Field59_Shortcodes' ) ) :
	/**
	 * Field59 Video shortcode functionality.
	 */
	class Field59_Shortcodes {

		/**
		 * Handles initializing this class and returning the singleton instance after it's been cached.
		 *
		 * @return null|Field59_Shortcodes
		 */
		public static function get_instance() {
			// Store instance locally to avoid private static replication.
			static $instance = null;

			if ( null === $instance ) {
				$instance = new self();
				self::init_hooks();
			}

			return $instance;
		}

		/**
		 * Registers hooks that initialize this plugin.
		 *
		 * @return void
		 */
		public static function init_hooks() {
			add_shortcode( 'field59_video', array( __CLASS__, 'field59_video_shortcode' ) );
		}

		/**
		 * Handles outputs for [field59_video] shortcodes.
		 *
		 * @param array $shortcode_attributes List of shortcode attributes.
		 * @return string
		 */
		public static function field59_video_shortcode( $shortcode_attributes ) {
			$atts = shortcode_atts(
				array(
					'type' => 'video',
					'key' => '',
					'account' => '',
					'autoplay' => false,
					'targetdiv' => '',
					'player' => '',
					'overrideadnet' => '',
					'vtitle' => '', // Param used by JS for visualization.
					'thumbnail' => '', // Param used by JS for visualization.
				), $shortcode_attributes
			);

			if ( empty( $atts['key'] ) || empty( $atts['account'] ) ) {
				return sprintf(
					'<!-- %s -->',
					esc_html__( 'Field59 video embed missing required parameters.', 'field59-video' )
				);
			}

			$query_params = array();

			if ( ! empty( $atts['autoplay'] ) ) {
				$query_params['autoplay'] = 'true';
			}
			if ( ! empty( $atts['player'] ) ) {
				$query_params['player'] = $atts['player'];
			}
			if ( ! empty( $atts['targetdiv'] ) ) {
				$query_params['targetdiv'] = $atts['targetdiv'];
			}
			if ( ! empty( $atts['overrideadnet'] ) ) {
				// Parameter is case sensitive.
				$query_params['overrideAdNet'] = $atts['overrideadnet'];
			}

			$params = trim( http_build_query( $query_params, '', ';' ) );

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				$html = sprintf(
					'<div data-video-source="field59" data-field59-key="%s" data-field59-account="%s" data-field59-title="%s" data-field59-autoplay="%s" ></div>',
					esc_attr( $atts['key'] ),
					esc_attr( $atts['account'] ),
					esc_attr( $atts['vtitle'] ),
					( $atts['autoplay'] ) ? 'true' : 'false'
				);
			} elseif ( is_feed() || (function_exists('is_amp_endpoint') && is_amp_endpoint()) ) {
				// When outputting to RSS/ATOM feed, or running over AMP, render Field59 video as iFrame.
				$html = sprintf(
					'<iframe src="//player.field59.com/v4/vp/%s/%s%s.html?full=y" frameborder="0" allowfullscreen></iframe>',
					esc_attr( $atts['account'] ),
					esc_attr( $atts['key'] ),
					( ! empty( $params ) ) ? '/' . $params : ''
				);
				$html = '<div class="field59-video">' . $html . '</div>';
				if (strtolower($_GET['M3U8link']) === 'true') {
					$m3u8_link = sprintf(
						'<a href="//redirect.field59.com/video/%s.m3u8" download="%s">M3U8 Download</a>',
						esc_attr( $atts['key'] ),
						esc_attr( $atts['vtitle'] )
					);
					$html .= $m3u8_link;
				}
			} else {
				// Otherwise render video as <script> tags.
				$stream_param = $shortcode_attributes['stream'] === 'true' ? 'live' : 'vp';
				$html = sprintf(
					'<script src="https://player.field59.com/v4/%s/%s/%s%s"></script>',
					esc_attr( $stream_param ),
					esc_attr( $atts['account'] ),
					esc_attr( $atts['key'] ),
					( ! empty( $params ) ) ? '/' . $params : ''
				);
			}

			/**
			 * Allow for modification of the HTML output of the Field59 display.
			 *
			 * @param string $html The shortcode output.
			 * @param array  $atts The attributes used to define the display of this shortcode.
			 */
			$html = apply_filters( 'field59_shortcode_render', $html, $atts );

			return $html;
		}
	}

	// Initialize class & kick off the admin hooks.
	Field59_Shortcodes::get_instance();
endif;
