/* global inlineEditL10n, ajaxurl, typenow */
/**
 * This file contains the functions needed for the inline editing of videos from the media window.
 */

window.wp = window.wp || {};

/**
 * Manages the settings windows for embedding Field59 videos.
 *
 * @namespace
 *
 * @access public
 *
 * @type {Object}
 *
 * @property {string} type The type of inline editor.
 * @property {string} what The prefix before the post id.
 */
var inlineEmbedVideo;
( function( $, wp ) {

	inlineEmbedVideo = {

		/**
		 * @summary Initializes the inline video embeder.
		 *
		 * Binds event handlers to the escape key to close the inline embed
		 * and to the save and close buttons. Changes DOM to be ready for
		 * embed settings.
		 *
		 * @memberof inlineEmbedVideo
		 *
		 * @returns {void}
		 */
		init : function(){
			var t = this, emRow = $( '#inline-embed' );

			t.type = 'field59-video';
			// Video id prefix.
			t.what = '#field59-';

			/**
		 * @summary Bind escape key to revert the changes and close the embed row.
		 *
		 * @returns {boolean} The result of revert.
		 */
			emRow.keyup(
				function(e){
					// Revert changes if escape key is pressed.
					if ( e.which === 27 ) {
						return inlineEmbedVideo.revert();
					}
				}
			);

			/**
		 * @summary Revert changes and close the embed if the cancel button is clicked.
		 *
		 * @returns {boolean} The result of revert.
		 */
			$( '.cancel', emRow ).click(
				function() {
					return inlineEmbedVideo.revert();
				}
			);

			/**
		 * @summary Embed the video in the post save(named: embed) button is clicked.
		 *
		 * @returns {boolean} The result of embed.
		 */
			$( '.save', emRow ).click(
				function() {
					return inlineEmbedVideo.embed( this );
				}
			);

			/**
		 * @summary If enter is pressed, and the target is not the cancel button, embed the video.
		 *
		 * @returns {boolean} The result of embed.
		 */
			$( 'td', emRow ).keydown(
				function(e){
					if ( e.which === 13 && ! $( e.target ).hasClass( 'cancel' ) ) {
						return inlineEmbedVideo.embed( this );
					}
				}
			);

			/**
		 * @summary Bind click event to the .embedinline link which opens the quick editor.
		 */
			$( '#the-list' ).on(
				'click', 'a.embedinline', function( e ) {
					e.preventDefault();
					inlineEmbedVideo.edit( this );
				}
			);
		},

		/**
		 * @summary Toggles the embed window.
		 *
		 * Hides the window when it's active and shows the window when inactive.
		 *
		 * @memberof inlineEmbedVideo
		 *
		 * @param {Object} el Element within a post table row.
		 */
		toggle : function(el){
			var t = this;
			$( t.what + t.getId( el ) ).css( 'display' ) === 'none' ? t.revert() : t.edit( el );
		},

		/**
		 * @summary Creates a quick edit window for the post that has been clicked.
		 *
		 * @memberof inlineEmbedVideo
		 *
		 * @param {number|Object} id The id of the clicked post or an element within a post
		 *                           table row.
		 * @returns {boolean} Always returns false at the end of execution.
		 */
		edit : function(id) {
			var t = this, fields, editRow, rowData;
			t.revert();

			if ( typeof(id) === 'object' ) {
				id = t.getId( id );
			}

			fields = [ 'account', 'key', 'thumb', 'vtitle', 'image', 'stream' ];

			// Add the new edit row with an extra blank row underneath to maintain zebra striping.
			editRow = $( '#inline-embed' ).clone( true );
			$( 'td', editRow ).attr( 'colspan', $( 'th:visible, td:visible', '.widefat:first thead' ).length );

			$( t.what + id ).addClass( 'inline-embed-open' ).after( editRow ).after( '<tr class="hidden"></tr>' );

			// Populate fields in the quick edit window.
			rowData = $( '#inline_' + id );

			for ( f = 0; f < fields.length; f++ ) {
				val = $( '.' + fields[f], rowData );

			/**
			 * @summary Replaces the image for a Twemoji(Twitter emoji) with it's alternate text.
			 *
			 * @returns Alternate text from the image.
			 */
				val.find( 'img' ).replaceWith( function() { return this.alt; } );
				val = val.text();

				$( ':input[name="' + fields[f] + '"]', editRow ).val( val );
			}

			$( editRow ).attr( 'id', 'edit-' + id ).addClass( 'inline-editor' ).show();
			$( '.ptitle', editRow ).focus();

			return false;
		},

		/**
		 * @summary Embeds the video in the post using the configurations chosen.
		 *
		 * @param   {int}     id The id for the video that is being embedded.
		 * @returns {boolean}    false, so the form does not submit when pressing
		 *                       Enter on a focused field.
		 */
		embed : function(id) {
			var t = this, editRow, account, key, thumb, vtitle, shortcode, ajax_params;

			if ( typeof(id) === 'object' ) {
				id = this.getId( id );
			}

			editRow = $( '#edit-' + id );

			var $errorNotice = $( '.inline-embed-finish .notice-error', editRow ),
			$error           = $errorNotice.find( '.error' );

			$( 'table.widefat .spinner' ).addClass( 'is-active' );

			account  = editRow.find( ':input[name="account"]' ).val();
			key      = editRow.find( ':input[name="key"]' ).val();
			thumb    = editRow.find( ':input[name="thumb"]' ).val();
			vtitle   = editRow.find( ':input[name="vtitle"]' ).val();
			isStream = editRow.find( ':input[name="stream"]' ).val();

			// Preliminary error handling - absolute requirements.
			if ( ! account || ! key) {
				$errorNotice.removeClass( 'hidden' );
				$error.html( 'Missing account or key for selected video. Video could not be embedded.' );
				wp.a11y.speak( $error.text() );
				$( 'table.widefat .spinner' ).removeClass( 'is-active' );
				return false;
			}

			// Collect params to pass to thumbnail import AJAX request.
			ajax_params = {
				action: 'field59-import-thumbnail',
				parent_post_id: f59_var.post_id,
			};
			fields      = editRow.find( ':input' ).serialize();
			ajax_params = fields + '&' + $.param( ajax_params );

			// Make ajax request.
			$.get(
				ajaxurl, ajax_params,
				function (response) {

					var $errorNotice = $( '#edit-' + id + ' .inline-embed-finish .notice-error' ),
						$error = $errorNotice.find('.error');

					$( 'table.widefat .spinner' ).removeClass( 'is-active' );

					if ( ! response.success ) {
						$errorNotice.removeClass( 'hidden' );
						$error.html( 'There was an error uploading the video image to the CMS. ' + response.data.message + ' (' + response.data.code + ')' );
						wp.a11y.speak( $error.text() );
						return false;
					}

					// Build shortcode.
					shortcode = '[field59_video account="' + account + '" key="' + key + '" vtitle="' + vtitle + '" thumb="' + thumb + '"';

					// Add optional attribute/values.
					if ( $( 'input[name="noads"]', editRow ).prop( 'checked' ) ) {
						shortcode += ' overrideadnet="none"';
					}
					if ( $( 'input[name="autoplay"]', editRow ).prop( 'checked' ) ) {
						shortcode += ' autoplay="true"';
					}
					if (isStream === 'true') {
						shortcode += ' stream="true"';
					}
					shortcode += ']';

					// Function ships shortcode & closes media window.
					if (typeof window.parent.send_to_editor === "function") {

						// If there's no featured image, set this one.
						if ( window.parent.wp.media.featuredImage.get() <= 0 ) {
							window.parent.wp.media.featuredImage.set(response.result.attachment_id);
						}
						window.parent.send_to_editor( shortcode );
					} else {
						// Handy when debugging iframe directly.
						$errorNotice.removeClass( 'hidden' );
						$error.html( 'There was a problem writing this embeded video to the article.<br><code>' + shortcode + '</code>' );
						wp.a11y.speak( $error.text() );
						$( 'table.widefat .spinner' ).removeClass( 'is-active' );
						return false;
					}
					t.revert();
					return false;
				},
				'json'
			);

			// Prevent submitting the form when pressing Enter on a focused field.
			return false;
		},

		/**
		 * @summary Hides and empties the video settings windows.
		 *
		 * @memberof    inlineEmbedVideo
		 *
		 * @returns {boolean} Always returns false.
		 */
		revert : function(){
			var $tableWideFat = $( '.widefat' ),
			id                = $( '.inline-editor', $tableWideFat ).attr( 'id' );

			if ( id ) {
				$( '.spinner', $tableWideFat ).removeClass( 'is-active' );
				$( '.ac_results' ).hide();

				// Remove both the inline-editor and its hidden tr siblings.
				$( '#' + id ).siblings( 'tr.hidden' ).addBack().remove();
				id = id.substr( id.lastIndexOf( '-' ) + 1 );

				// Show the post row and move focus back to the Quick Edit link.
				$( this.what + id ).removeClass( 'inline-embed-open' ).find( '.embedinline' ).focus();
			}

			return false;
		},

		/**
		 * @summary Gets the id for the video that you want to embed from the row
		 * in the video list table.
		 *
		 * @memberof    inlineEmbedVideo
		 *
		 * @param   {Object} o DOM row object to get the id for.
		 * @returns {string}   The post id extracted from the table row in the object.
		 */
		getId : function(o) {
			var id = $( o ).closest( 'tr' ).attr( 'id' ),
			parts  = id.split( '-' );
			return parts[parts.length - 1];
		}
	};

	$( document ).ready( function () { inlineEmbedVideo.init(); } );

})( jQuery, window.wp );
