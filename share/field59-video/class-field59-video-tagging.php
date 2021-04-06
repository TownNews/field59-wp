<?php
/**
 * Class to manage Field59 video auto-tagging from the Rayos article UI.
 *
 * The primary use-case of this feature is OTT focused - where a station editor can embed videos in articles with specific categories and expect that tagging to be reflected in Field59, which powers the OTT video lists.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'Field59_Video_Tagging' ) ) :
	/**
	 * Field59 Video tagging.
	 */
	class Field59_Video_Tagging extends Field59_Video_Admin {

		/**
		 * Registers hooks that initialize this plugin.
		 *
		 * @return void
		 */
		public static function init_hooks() {
			add_action( 'save_post', array( __CLASS__, 'video_article_save' ), 12, 2 );
		}

		/**
		 * Article save hook - combines existing Field59 tags with the slugs of the categories
		 * applied to the article in Rayos and then passes the combination of tags back to
		 * the Field59 API.
		 *
		 * @param int     $post_id The post ID.
		 * @param WP_Post $post    The WordPress post object.
		 * @return void
		 */
		public static function video_article_save( $post_id, $post ) {

			if ( ! has_shortcode( $post->post_content, 'field59_video' ) ) {
				return;
			}

			// Extract shortcode attributes & iterate.
			if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $shortcode ) {

					if ( 'field59_video' === $shortcode[2] ) {

						$shortcode_attrs = shortcode_parse_atts( $shortcode[3] );
						if ( ! is_array( $shortcode_attrs ) ) {
							$shortcode_attrs = array();
						}

						// Get existing tags from Fiedl59.
						$field59_tags = self::get_field59_tags( $shortcode_attrs['key'] );

						// Collect Rayos categories into a list of slugs.
						$rayos_categories = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );
						$rayos_post_tags = get_the_tags($post_id);
						$rayos_tags       = [];
						if($rayos_categories){
							foreach ( $rayos_categories as $cat ) {
								$rayos_tags[] = $cat->slug;
							}
						}
						if($rayos_post_tags){
							foreach ( $rayos_post_tags as $tag ) {
								$rayos_tags[] = $tag->slug;
							}
						}

						// Combine old/new slugs without duplicates.
						$new_tags = array_unique( array_merge( $field59_tags, $rayos_tags ) );

						// If there are no changes, do not update.
						if ( $new_tags === $field59_tags ) {
							return;
						}

						$response = self::assign_field59_tags( $shortcode_attrs['key'], $new_tags );
					}
				}
			}
		}

		/**
		 * Gets a list of tags applied to the requested Field59 video.
		 *
		 * @param string $key The Field59 video key.
		 * @return array
		 */
		protected static function get_field59_tags( $key ) {

			$taglist = [];

			$url = sprintf( 'https://api.field59.com/v2/video/%s', $key );

			$response = self::make_field59_call( $url, 'GET' );

			if (
				! is_wp_error( $response )
				&& is_array( $response )
				&& 200 === $response['response']['code']
			) {
				$body = $response['body'];
			} else {
				if ( is_wp_error( $response ) ) {
					return $response;
				}
			}

			$xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA );
			if($xml){
				if (
					property_exists( $xml, 'tags' )
					&& count( $xml->tags->tag ) > 0
				) {

					foreach ( $xml->tags->tag as $tag ) {
						$taglist[] = (string) $tag;
					}
				}
			}
			return $taglist;
		}

		/**
		 * Applies the list of tags to the Field59 video via the API.
		 *
		 * @param string $key  The Field59 video key.
		 * @param array  $tags List of all tags to apply to the video.
		 * @return array|WP_Error
		 */
		protected static function assign_field59_tags( $key, $tags ) {
			if (
				empty( $key )
				|| empty( $tags ) ) {
				return;
			}

			$tag_tags = [];

			foreach ( $tags as $tag ) {
				$tag_tags[] = sprintf( '<tag><![CDATA[%s]]></tag>', esc_attr( $tag ) );
			}

			$url = sprintf( 'https://api.field59.com/v2/video/%s', esc_attr( $key ) );

			$xml = sprintf(
				'<?xml version="1.0"?>
				<video>
					<tags>
						%s
					</tags>
				</video>',
				implode( $tag_tags )
			);

			$additional_headers = array( 'content-type' => 'application/xml' );

			$response = self::make_field59_call( $url, 'POST', array(), $xml, $additional_headers );

			return $response;
		}
	}

	// Initialize class & kick off the admin hooks.
	Field59_Video_Tagging::init_hooks();
endif;
