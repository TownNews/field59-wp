(function($){

	var media = wp.media,
		shortcode_string = 'field59_video';

	if ( media ) {
		wp.mce          = wp.mce || {};
		wp.mce.field59_video = {
			shortcode_data: {},
			template: media.template( 'editor-field59-video' ),
			getContent: function() {
					var options             = this.shortcode.attrs.named;
					options['innercontent'] = this.shortcode.content;
					return this.template( options );
			},
			View: {
				// before WP 4.2:
				template: media.template( 'editor-field59-video' ),
				postID: $( '#post_ID' ).val(),
				initialize: function( options ) {
					this.shortcode                 = options.shortcode;
					wp.mce.gtxvideo.shortcode_data = this.shortcode;
				},
				getHtml: function() {
					var options             = this.shortcode.attrs.named;
					options['innercontent'] = this.shortcode.content;
					return this.template( options );
				}
			},
			edit: function( data, update ) {
				var shortcode_data     = wp.shortcode.next( shortcode_string, data );
				var values             = shortcode_data.shortcode.attrs.named;
				values['innercontent'] = shortcode_data.shortcode.content;

				wp.mce.field59_video.popupwindow(tinyMCE.activeEditor, values);
			},
			/**
			 * Triggers media pop-up and activates the Field59 Video tab.
			 *
			 * @link https://wordpress.stackexchange.com/a/265536/14668
			 */
			popupwindow: function (editor, values) {

				wp.media.editor.open();

				var $search_tab = $(".media-menu-item").filter(function () {
						return $( this ).text().indexOf( "Field59 Video" ) >= 0;
				}).first();
				$search_tab.trigger( 'click' );
			}
		};

		// Register the visualization.
		if (typeof wp.mce.views != 'undefined') {
			wp.mce.views.register( shortcode_string, wp.mce.field59_video );
		}
	}
}(jQuery));
