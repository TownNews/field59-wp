<?php
/**
 * @package Field59 Video
 */
namespace Inc\Pages;

use Inc\Api\SettingsApi;
use Inc\Base\Enqueue;
use Inc\Base\BaseController;
use Inc\Api\AuthenticationApi;
use Inc\Pages\Field59ListTable;
use Inc\Api\CallBacks\AdminCallBacks;

class AdminVideoPublishing extends BaseController{

  public  function register(){
      add_filter( 'media_upload_tabs', array( $this, 'add_media_tab' ), 20 );
			add_action( 'media_upload_field59', array(  $this, 'insert_media_video_iframe' ) );
			add_action( 'wp_ajax_field59-import-thumbnail', array(  $this, 'ajax_import_thumbnail' ) );
			add_action( 'wp_ajax_field59-search-videos', array(  $this, 'ajax_search_videos' ) );

      	// Additional features through supported hooks.
			add_filter( 'field59_video_image_source_approved', array( $this, 'whitelist_image_domains' ), 10 );
			add_action( 'field59_video_after_thumb_upload', array( $this, 'apply_eml_category' ), 10, 3 );
			add_action( 'field59_video_after_thumb_dup_selected', array( $this, 'apply_eml_category' ), 10, 3 );
			add_action( 'field59_video_media_table_before', array( $this, 'page_subtitle' ), 1 );
			add_action( 'field59_video_media_table_before', array( $this, 'filter_form_start' ), 10 );
			add_action( 'field59_video_media_table_before', array( $this, 'search_box' ), 20 );
			add_action( 'field59_video_media_table_before', array( $this, 'display_pagination' ), 30, 1 );
			add_action( 'field59_video_media_table_after', array( $this, 'filter_form_end' ), 10 );
			add_action( 'field59_video_media_table_after', array( $this, 'display_pagination' ), 30, 1 );
			add_action( 'field59_video_media_table_after', array( $this, 'inline_embed_js_template' ), 50 );

			// Filters to support approximating total & total pages because neither is passed from the API and must be calculated/infered.
			add_filter( 'field59_pagination_total', array( $this, 'approximate_total_count' ), 10, 3 );
			add_filter( 'field59_pagination_total_pages', array( $this, 'approximate_total_pages' ), 10, 3 );

			add_action( 'save_post', array( $this, 'video_embed_post_save' ), 11, 3 );
  }  

  public static function add_media_tab( $tabs ) {
    $tabs['field59'] = __( 'Field59 Video', 'field59-video' );

    return $tabs;
  }


  public static function insert_media_video_iframe() {
    // Body Content.
    $GLOBALS['body_id'] = 'field59-video-media-library';
    wp_iframe( array( $this, 'field59_draw_media_video_page' ) );
  }

  /**
   * Returns HTML to display a table row for the supplied video.
   *
   * @param SimpleXMLElement $video SimpleXMLElement object of Field59's API video node.
   * @return string
   */
  public static function draw_media_video_row( $video ) {

    // check if stream prop exists on response
    if ( array_key_exists( 'stream', $video ) ) {
      $url 				= (string) "https://player.field59.com/v4/live/{$video->owner}/{$video->key}";
      $stream 			= (string) 'true';
    } else {
      $url 				= (string) $video->url;
      $stream 			= (string) 'false';
      $duration           = (string) $video->duration;
      $description        = (string) $video->description;
      $thumb_small        = (string) $video->thumbSmall;
      $thumb_medium       = (string) $video->thumbMedium;
      $last_modified_date = (string) $video->lastModifiedDate;
    }

    $key                = (string) $video->key;
    $title              = (string) $video->title;
    $category           = (string) $video->category;
    $keywords           = (array) $video->tags->tag;
    $summary            = (string) $video->summary;
    $thumb              = (string) $video->thumb;
    $create_date        = (string) $video->createDate;
    $owner              = (string) $video->owner;
    $user               = (string) $video->user;
    $id                 = (string) $video->id; // User ID?

    $row_classes = array( 'level-0', 'type-field59video' );
    if ( ! empty( $category ) ) {
      $row_classes[] = 'category-' . trim( $category );
    } else {
      $row_classes[] = 'category-uncategorized';
    }

    $html = sprintf( '<tr id="field59-%s" class="%s">', $key, implode( ' ', $row_classes ) );

    // First cell, thumbnail & title.
    $html .= sprintf(
      '<td class="title column-title has-row-actions column-primary page-title" data-colname="Title">
        <a class="embedinline" href="#">
          <img src="%s" class="field59_video_thumbnail %s">
        </a>
        <strong>
          <a class="row-title embedinline" href="#">%s</a>
        </strong>',
      esc_attr( $thumb_small ),
      empty( $thumb_small ) ? 'no-thumb' : 'has-thumb',
      esc_html( $title )
    );

    // Inline embed javascript uses these.
    $html .= sprintf(
      '<div class="hidden" id="inline_%s">
        <div class="key">%s</div>
        <div class="account">%s</div>
        <div class="vtitle">%s</div>
        <div class="image">%s</div>
        <div class="thumb">%s</div>
        <div class="stream">%s</div>
      </div>',
      esc_attr( $key ),
      esc_attr( $key ),
      esc_attr( $owner ),
      esc_attr( $title ),
      esc_attr( $thumb ),
      esc_attr( $thumb_medium ),
      esc_attr( $stream )
    );

    /**
     * Video description/summary.
     *
     * Mimics behavior in Field59 Manager where if both summary & description
     * values are present, the summary displays.
     */
    $html .= sprintf(
      '<div class="video-description">
        <p>%s</p>
      </div>',
      ( ! empty( $summary ) ) ? esc_html( $summary ) : esc_html( $description )
    );

    // Row actions - avaliable on hover.
    $html .= sprintf(
      '<div class="row-actions">
        <span class="embed"><a class="embedinline" href="#" aria-label="Embed “%s”">%s</a></span>
      </div>',
      esc_html( $title ),
      esc_attr__( 'Embed', 'field59-video' )
    );

    $html .= '</td>';

    // Video length.
    $html .= sprintf( '<td class="length column-length" data-colname="Length">%s</td>', $duration );

    // Category.
    $html .= sprintf( '<td class="category column-category" data-colname="Category">%s</td>', $category );

    // Keywords.
    $html .= sprintf( '<td class="keywords column-keywords" data-colname="Keywords">%s</td>', implode( ', ', $keywords ) );

    // Created or last modified (replicated WordPress post date behavior).
    if ( $create_date === $last_modified_date ) {
      $html .= sprintf(
        '<td class="date column-date" data-colname="Date">%s<br><abbr title="%s">%s</abbr></td>',
        esc_attr__( 'Created', 'field59-video' ),
        $create_date,
        date( 'Y/m/d', strtotime( $create_date ) )
      );
    } else {
      $html .= sprintf(
        '<td class="date column-date" data-colname="Date">%s<br><abbr title="%s">%s</abbr></td>',
        esc_attr__( 'Modified', 'field59-video' ),
        $last_modified_date,
        date( 'Y/m/d', strtotime( $last_modified_date ) )
      );
    }

    $html .= '</tr>';

    return $html;
  }

  /**
   * Draws the contents of the page serviced by the iFrame in the media library.
   *
   * @return void
   */
  public static function field59_draw_media_video_page() {
    // Pagination variables.
    $paged = isset( $_GET['paged'] ) ? absint( sanitize_text_field( $_GET['paged'] ) ) : 1;

    // Force keyword searches to a single page.
    if ( ! empty( $_GET['s'] ) ) {
      $paged = 1;
    }

    // Drop this here, because search overrides don't work on dev...
    $fetch_type = ! empty( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'video';

    $pagination = array(
      'paged'        => max( 1, $paged ),
      'position'     => 'top',
    );

    // Parse argments against default structure so all expected array keys are avaliable.
    $pagination = wp_parse_args( $pagination, self::get_pagination_defaults() );

    $pagination['offset'] = ( $pagination['paged'] - 1 ) * $pagination['per_page'];

    $search_params = array(
      'type'    => $fetch_type,
      'sorting' => 'date', // API default; explicitly set.
      'limit'   => $pagination['per_page'],
      'skip'    => $pagination['offset'],
    );

    $f59_user = get_option( 'field59_username', 'option' );
    $f59_pass = get_option( 'field59_password', 'option' );

    if ( empty( $f59_user ) || empty( $f59_pass ) ) {
      echo '<div class="content"><p>The Field59 API user name and password must be set in the admin settings in order to support video integration.<p></div>';
      echo sprintf(
        '<p style="text-align:center;"><small><em>%s %s - %s</em></small></p></div>',
        esc_attr__( 'Error code', 'field59-video' ),
        '0',
        esc_attr__( 'Missing Credentials', 'field59-video' )
      );
      return;
    }

    if ( ! empty( $_GET['s'] ) ) {
      $search_params['terms']   = sanitize_text_field( $_GET['s'] );
      $search_params['sorting'] = 'relevance';
    }

    $response = AuthenticationApi::make_field59_api_call( 'https://api.field59.com/v2/search', 'GET', $search_params );

    $error         = false;
    $error_details = '';
    
    $login_unauthorized = 'Field59 email/username and password combination are incorrect. <a href="admin.php?page=field59_video_settings">Click here</a> to verify your login information.';
    $login_failure = 'Field59 email/username and password must be set in the <a href="admin.php?page=field59_video_settings">Field59 Video Settings</a> in order to support video integration.';
    if (
      ! is_wp_error( $response )
      && is_array( $response )
    ) {
      if ( 200 === $response['response']['code'] ) {
        $body = $response['body'];
      } else {
        $error = true;
        if ( 401 === $response['response']['code'] ) {
          echo 
            '<div class="content"><p>'.$login_failure. '</p></div>';
        }elseif ( 403 === $response['response']['code'] ) {
          echo 
            '<div class="content"><p>'.$login_unauthorized.'</p></div>';
        }

      }
    } else {
      $error = true;
    }
    
    if ( $error ) {
      echo sprintf(
        '<div class="content"><p>%s <a href="https://www.field59.com/api-error-responses">Click Here</a>. %s</p>',
        esc_html( 'For more information ', 'field59-video' ),
        esc_html( $error_details )
      );
      return;
    }
    
    $videos = simplexml_load_string( $body, null, LIBXML_NOCDATA );

    /**
     * Ideally the $total and $total_pages would be defined in the
     * API response. Since it is not there are functions applied to the hooks
     * below to help approximate the total and total pages.
     */
    $total       = null;
    $total_pages = null;
    $count       = count( $videos->$fetch_type );

    $pagination['count'] = $count;

    /**
     * Filters the total count of Field59 videos avaliable for the API query.
     *
     * @param int              $total      The total number of results availiable.
     * @param array            $pagination Structured list of pagination details.
     * @param SimpleXMLElement $videos     The simpleXML extract ov videos from the feed.
     */
    $pagination['total_items'] = apply_filters( 'field59_pagination_total', $total, $pagination, $videos );

    /**
     * Filters the total number of pages required to support total count of Field59 videos avaliable for the API query.
     *
     * @param int              $total      The total number of results availiable.
     * @param array            $pagination Structured list of pagination details.
     * @param SimpleXMLElement $videos     The simpleXML extract ov videos from the feed.
     */
    $pagination['total_pages'] = apply_filters( 'field59_pagination_total_pages', $total, $pagination, $videos );

    echo '<div class="content">';

    /**
     * Runs before the table of videos displays in the media window.
     *
     * An ideal way to add filter, pagination and search HTML above the results.
     *
     * @param array $pagination Structured list of pagination details.
     */
    do_action( 'field59_video_media_table_before', $pagination );

    echo sprintf(
      '<table class="wp-list-table widefat fixed striped field59-videos">
        <thead>
        <tr>
          <th scope="col" id="title" class="manage-column column-title">%s</th>
          <th scope="col" id="length" class="manage-column column-length">%s</th>
          <th scope="col" id="category" class="manage-column column-category">%s</th>
          <th scope="col" id="keywords" class="manage-column column-keywords">%s</th>
          <th scope="col" id="date" class="manage-column column-date">%s</th>
        </tr>
        <thead>
        <tbody id="the-list">',
      esc_attr__( 'Title', 'field59-video' ),
      esc_attr__( 'Length', 'field59-video' ),
      esc_attr__( 'Category', 'field59-video' ),
      esc_attr__( 'Keywords', 'field59-video' ),
      esc_attr__( 'Date', 'field59-video' )
    );
    if ( $count ) {
      foreach ( $videos->$fetch_type as $video ) {
        echo self::draw_media_video_row( $video );
      }
    } else {
      echo sprintf( '<tr><td colspan="5">%s</td></tr>', esc_html__( 'There are no videos to display.', 'field59-video' ) );
    }
    echo '</tbody></table>';

    // Update pagaination position.
    $pagination['position'] = 'bottom';

    /**
     * Runs before the table of videos displays in the media window.
     *
     * An ideal way to add filter, pagination and search HTML above the results.
     *
     * @param array $pagination List of pagination values.
     */
    do_action( 'field59_video_media_table_after', $pagination );

    echo '</div>';
  }

  /**
   * Displays optional subtitle on page.
   *
   * @return void
   */
  public static function page_subtitle() {
    $subtitle = self::get_page_subtitle();

    echo sprintf( '<span class="subtitle">%s</span>', esc_html( $subtitle ) );
  }

  /**
   * Determines the subtitle to display for the page.
   *
   * @return string
   */
  public static function get_page_subtitle() {
    $subtitle = '';
    if ( ! empty( $_GET['s'] ) ) {
      $subtitle = sprintf( '%s “%s”', __( 'Search results for', 'field59-video' ), esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) );
    }
    return $subtitle;
  }

  /**
   * Opening tag for video filter form and required fields for search/pagination/filtering.
   *
   * @return void
   */
  public static function filter_form_start() {
    echo '<form id="field59-video-filter">
    <div class="f59-header">
      <div class="f59-type">
        <button id="video" class="active">Videos</button>
        <button id="event">Events/Streams</button>
      </div>';

    echo sprintf(
      '<input type="hidden" name="tab" value="%s">',
      esc_attr( sanitize_key( $_GET['tab'] ) )
    );
    echo sprintf(
      '<input type="hidden" name="post_id" value="%s">',
      esc_attr( sanitize_key( $_GET['post_id'] ) )
    );
  }

  /**
   * Outputs HTML for the search box form fields.
   *
   * @return void
   */
  public static function search_box() {
    $search_term = ! empty( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

    echo '<div class="search-pagination-wrapper"><p class="search-box">';

    echo '<label class="screen-reader-text" for="post-search-input">Search Videos:</label>
    
    <span class="spinner"></span>';

    echo sprintf(
      '<input type="search" id="video-search-input" name="s" value="%s">',
      esc_attr( $search_term )
    );

    echo '<input type="submit" id="search-submit" class="button" value="Search Videos">
    </p>';
  }

  /**
   * Default values for arguments supported by the use of a pagination
   * array in this plugin.
   *
   * @return array $pagination Structured list of pagination details.
   */
  public static function get_pagination_defaults() {
    $pagination = array(
      'paged' => 1,
      'total_items' => 0,
      'total_pages' => 0,
      'per_page' => 25,
      'count' => 0,
      'position' => 'top',
    );
    return $pagination;
  }

  /**
   * @param int   $total_pages The number of pages availiable.
   * @param array $pagination  Structured list of pagination details.
   * @return int $total_pages
   */
  public static function approximate_total_pages( $total_pages, $pagination ) {
    // If total_pages is defined; return it instead of approximating.
    if ( $total_pages ) {
      return $total_pages;
    }

    // Parse argments against default structure so all expected array keys are avaliable.
    $pg = wp_parse_args( $pagination, self::get_pagination_defaults() );

    $total_pages = absint( ceil( (int) $pg['total_items'] / $pg['per_page'] ) );

    /**
     * If the current page is the last page and has the same count as the
     * per_page, then provide one more page incase the stored count is wrong.
     *
     * Example
     *    30 records:
     *     - pg 1 shows 1,2,3
     *     - pg 2 shows 1,2,3
     *     - pg 3 shows 1,2,3,4
     *     - pg 4 may not contain videos but if it does, the count is off it will get updated when that pagination renders.
     */
    if (
      $pg['count'] === $pg['per_page']
      && $total_pages === $pg['paged']
    ) {
      $total_pages++;
    }

    return $total_pages;
  }

  /**
   * Approximates the max total number of items because it is not yet availiable in the API.
   *
   * Logic:
   * - Store last known accurate total in options table (stored total)
   * - If the calculated total of the current page + previous pages is larger than stored total
   * - Otherwise only change the stored total if there is a clear mis-match between what is served on the last page vs. the stored value.
   *
   * @param int   $total      The total number of results availiable.
   * @param array $pagination Structured list of pagination details.
   * @return int $total
   */
  public static function approximate_total_count( $total, $pagination ) {
    // If total is defined; return it instead of approximating.
    if ( $total ) {
      return $total;
    }

    // Parse argments against default structure so all expected array keys are avaliable.
    $pg = wp_parse_args( $pagination, self::get_pagination_defaults() );

    $stored_total = absint( get_option( 'field59_video_total_approximation', 0 ) );
    $new_total    = ( $pg['paged'] - 1 ) * $pg['per_page'] + $pg['count'];

    /**
     * Updating the stored total when the current page returns 0 records
     * and the estimated count is lower than the stored value.
     *
     * Ex: Videos deleted via Field59 manager.
     */
    if ( 0 === absint( $pg['count'] ) ) {
      if ( $stored_total > $new_total ) {
        update_option( 'field59_video_total_approximation', $new_total );
        return $new_total;
      }
    }

    if (
      $new_total > $stored_total
      && $pg['count'] > 0
    ) {
      update_option( 'field59_video_total_approximation', $new_total );
      return $new_total;
    }

    if (
      $new_total < $stored_total
      && $pg['count'] < $pg['per_page']
    ) {
      update_option( 'field59_video_total_approximation', $new_total );
      return $new_total;
    }

    return $stored_total;
  }

  /**
   * Outputs HTML for pagination in Field59 media frame.
   *
   * @param array $pagination Structured list of pagination details.
   * @return void
   */
  public static function display_pagination( $pagination ) {

    // Parse argments against default structure so all expected array keys are avaliable.
    $pg = wp_parse_args( $pagination, self::get_pagination_defaults() );

    $table = Field59ListTable::get_instance();
    $table->set_pagination_args( $pg );

    echo sprintf( '<div class="tablenav %s">', esc_attr( $pg['position'] ) );
      $table->print_pagination( $pg['position'] );
    echo '</div></div></div>';
  }

  /**
   * Returns the HTML for pagination based on passed arguments.
   *
   * @param array $pagination Structured list of pagination details.
   * @return string
   */
  public static function get_display_pagination( $pagination ) {
    ob_start();
    self::display_pagination( $pagination );
    $html = ob_get_contents();
    ob_end_clean();
      /**
       * Pagination is generated using supar globals. Some of the output must
       * be modified in order to use what's passed back to the client side
       * through the admin ajax request.
       */
    $html = str_replace( '/wp-admin/admin-ajax.php', '/wp-admin/media-upload.php', $html );
    $html = str_replace( 'action=field59-search-videos', 'tab=field59', $html );

    return $html;
  }

  /**
   * Closing tag for video filter form.
   *
   * @return void
   */
  public static function filter_form_end() {
    echo '</form>';
  }

  /**
   * Generates the HTML used by javascript to support inline embed functionality.
   *
   * @return void
   */
  public static function inline_embed_js_template() {
    $html = '
    <form method="get">
      <table style="display: none">
        <tbody id="inlineembed">
          <tr class="inline-edit-row inline-embed-video" id="inline-embed" style="display: none">
            <td class="colspanchange" colspan="6">
              <fieldset class="inline-embed-col">
                <div class="inline-edit-col">
                  <strong>Optional Embed Settings</strong>
                  <div class="inline-edit-group wp-clearfix">
                    <label class="alignleft"><input name="autoplay" type="checkbox" value="open"> <span class="checkbox-title">Autoplay Video</span></label> <label class="alignleft"><input name="noads" type="checkbox" value="open"> <span class="checkbox-title">No Ads</span></label>
                  </div>
                </div>
              </fieldset>
              <fieldset style="display:none;">
                <input name="account" type="hidden" value="">
                <input name="key" type="hidden" value="">
                <input name="vtitle" type="hidden" value="">
                <input name="image" type="hidden" value="">
                <input name="thumb" type="hidden" value="">
                <input name="stream" type="hidden" value="">
              </fieldset>
              <div class="submit inline-embed-finish">
                <button class="button button-primary save alignleft" type="button">Embed</button> <span class="spinner"></span> <button class="button cancel alignright" type="button">Cancel</button><br class="clear">
                <div class="notice notice-error notice-alt inline hidden">
                  <p class="error"></p>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </form>
    ';

    echo $html;
  }

  /**
   * Adds CSS for use inside the editor.
   *
   * @return void
   */

  /**
   * Function associated with calls to the admin ajax action 'field59-import-thumbnail'.
   *
   * Imports image from external resource into WordPress's media library.
   *
   * @link https://www.isitwp.com/upload-user-submitted-files-frontend/
   * @link https://andrewho.nl/wordpress-add-uploaded-image-media-library/
   *
   * @return void
   */
  public function ajax_import_thumbnail() {

    // Get the path to the upload directory.
    $wp_upload_dir   = wp_upload_dir();
    $timeout_seconds = 5;

    // Does image already exist? - Check meta key.
    // Filter list of approved domains for import.
    $image_url      = $_REQUEST['image'];
    $video_title    = $_REQUEST['vtitle'];
    $account        = $_REQUEST['account'];
    $key            = $_REQUEST['key'];
    $parent_post_id = $_REQUEST['parent_post_id'];
    
    // return early if importing without an image
    if(!$image_url){
      $output = array(
        'post_id' => $parent_post_id,
        'video_key' => $key,
        'video_account' => $account,
        'upload' => 'existing',
      );
      wp_send_json(
        array(
          'success' => true,
          'result' => $output,
        )
      );
      wp_die();
    }

    /**
     * Filters the image source for invalidation based on URL.
     *
     * Return false on the filter to prevent further import action.
     *
     * @param string $url The image URL.
     */
    $is_approved_domain = (bool) apply_filters( 'field59_video_image_source_approved', $image_url );

    if ( ! $is_approved_domain ) {
      wp_send_json_error(
        array(
          'code' => 'f59-003',
          'message' => 'Image source was disapproved. <code>' . $image_url . '</code>',
        )
      );
      wp_die();
    }

    // Check for duplicates.
    $args  = array(
      'post_type' => 'attachment',
      'orderby'   => 'modified',
      'post_status' => array( 'publish', 'inherit' ),
      'meta_query'  => array(
        array(
          'key' => '_import_origin',
          'value' => $image_url,
          'compare' => '=',
        ),
      ),
    );
    $query = new WP_Query( $args );

    // Image is already uploaded; reuse from media libary.
    if ( count( $query->posts ) ) {

      $attachment = $query->posts[0];

      /**
       * Runs after a duplicate attachment has been detected and selected for use.
       *
       * @param int $attachment The ID of the attachment post.
       * @param string $key The key of the Field59 video associated with the image.
       * @param string $account The account of the Field59 video associated with the image.
       */
      do_action( 'field59_video_after_thumb_dup_selected', $attachment->ID, $key, $account );

      // Build response.
      $output = array(
        'attachment_id' => $attachment->ID,
        'post_id' => $parent_post_id,
        'video_key' => $key,
        'video_account' => $account,
        'upload' => 'existing',
      );
      wp_send_json(
        array(
          'success' => true,
          'result' => $output,
        )
      );
      wp_die();
    }

    // Download file to temp dir.
    $temp_file = download_url( $image_url, $timeout_seconds );

    if ( is_wp_error( $temp_file ) ) {
      wp_send_json_error(
        array(
          'code' => 'f59-001',
          'message' => 'Video image import failed.',
        )
      );
      wp_die();
    }
    // Array based on $_FILE as seen in PHP file uploads.
    $file = array(
      'name'     => basename( $image_url ),
      'type'     => 'image/png',
      'tmp_name' => $temp_file,
      'error'    => 0,
      'size'     => filesize( $temp_file ),
    );

    $overrides = array(
      // Tells WordPress to not look for the POST form
      // fields that would normally be present as
      // we downloaded the file from a remote server, so there
      // will be no form fields
      // Default is true.
      'test_form' => false,

      // Setting this to false lets WordPress allow empty files, not recommended
      // Default is true.
      'test_size' => true,
    );

    // Move the temporary file into the uploads directory.
    $results = wp_handle_sideload( $file, $overrides );
    if ( ! empty( $results['error'] ) ) {
      wp_send_json_error(
        array(
          'code' => 'f59-002',
          'message' => 'Video image import failed.',
        )
      );
      wp_die();
    }

    // $filename should be the path to a file in the upload directory.
    $filename = $results['file'];
    // URL to the file in the uploads dir.
    $local_url = $results['url'];
    // MIME type of the file.
    $filetype = $results['type'];

    // Prepare an array of post data for the attachment.
    $attachment = array(
      'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
      'post_mime_type' => $filetype,
      'post_title'     => $video_title,
      'post_content'   => '',
      'post_status'    => 'inherit',
    );

    // Creates the entry in the media library.
    $attach_id   = wp_insert_attachment( $attachment, $filename, $parent_post_id );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // Add metadata for duplicate matching and import debugging/tracability.
    update_post_meta( $attach_id, '_import_by', 'field59-video plugin v/');
    update_post_meta( $attach_id, '_import_origin', $image_url );
    update_post_meta( $attach_id, '_import_field59_account', $account );
    update_post_meta( $attach_id, '_import_field59_video_key', $key );

    /**
     * Runs after the image has been uploaded to the media library.
     *
     * @param int $attachment The ID of the attachment post.
     * @param string $key The key of the Field59 video associated with the image.
     * @param string $account The account of the Field59 video associated with the image.
     */
    do_action( 'field59_video_after_thumb_upload', $attach_id, $key, $account );

    // Build response.
    $output = array(
      'attachment_id' => $attach_id,
      'post_id' => $parent_post_id,
      'video_key' => $key,
      'video_account' => $account,
      'upload' => 'fresh',
    );
    wp_send_json(
      array(
        'success' => true,
        'result' => $output,
      )
    );
    wp_die();
  }
  /**
   * Function associated with calls to the admin ajax action 'field59-search-videos'.
   *
   * Pings Field59 API and returns table body contents and subtitle
   * to replace the displays on the page.
   *
   * @return void
   */
  public function ajax_search_videos() {
			$search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			
			$search_type = sanitize_text_field( wp_unslash( $_GET['type'] ) );
			$search_type = ! empty( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'video';
			$paged       = isset( $_GET['paged'] ) ? absint( sanitize_text_field( $_GET['paged'] ) ) : 1;

			$pagination = array(
				'paged'        => max( 1, $paged ),
			);

			// Parse argments against default structure so all expected array keys are avaliable.
			$pagination = wp_parse_args( $pagination, self::get_pagination_defaults() );

			$pagination['offset'] = ( $pagination['paged'] - 1 ) * $pagination['per_page'];

			$search_params = array(
				'sorting' => 'date', // API default; explicitly set.
				'skip'    => $pagination['offset'],
				'type'    => $search_type,
			);

			if ( ! empty( $search_term ) ) {
				$search_params['terms']   = $search_term;
				$search_params['sorting'] = 'relevance';
				$search_params['limit']   = 50;
				$search_params['skip']    = 0;
			}
			$response = AuthenticationApi::make_field59_api_call( 'https://api.field59.com/v2/search', 'GET', $search_params);
			$f59_user = get_option( 'field59_username', 'option' );
      $f59_pass = get_option( 'field59_password', 'option' );
		
			if (
				! is_wp_error( $response )
				&& is_array( $response )
			) {
				if ( 200 === $response['response']['code'] ) {
					$body = $response['body'];
				} else {
					$error = true;
					if ( 401 === $response['response']['code'] ) {
						$error_details = esc_attr__( 'Please verify username and password settings.', 'field59-video' );
					}
				}
			} else {
				$error = true;
			}

			if ( $error ) {

				if ( ! isset( $error_details ) ) {
					$error_details = $response['response']['message'];
				}

				$tbody = sprintf(
					'<tr><td colspan="6">%s: <pre>%s</pre></td></tr>',
					esc_html( 'The service responded with an errors', 'field59-video' ),
					$error_details
				);
				wp_send_json_error(
					array(
						'code'              => $response['response']['code'],
						'error_message'     => $response['response']['message'],
						'tbody'             => $tbody,
						'pagination_top'    => '',
						'pagination_bottom' => '',
					)
				);
				wp_die();
			}

			

			$videos      = simplexml_load_string( $body, null, LIBXML_NOCDATA );
			$video_count = count( $videos->$search_type );
			$total       = 0;
			$total_pages = 0;

			if ( $video_count ) {
				$tbody = '';

				foreach ( $videos->$search_type as $video ) {
					$tbody .= self::draw_media_video_row( $video );
				}
			} else {
				if ( ! empty( $search_term ) ) {
					$tbody = sprintf(
						'<tr><td colspan="5">%s “%s”</td></tr>',
						esc_html__( 'There are no videos to display for search term', 'field59-video' ),
						$search_term
					);
				} else {
					$tbody = sprintf( '<tr><td colspan="5">%s</td></tr>', esc_html__( 'There are no videos to display Sorry.', 'field59-video' ) );
				}
			}

			$subtitle = self::get_page_subtitle();

			$pagination['count'] = $video_count;

			if ( ! empty( $search_term ) ) {
				$pagination['total_items'] = $video_count;
				$pagination['total_pages'] = 1;
			} else {
				$pagination['total_items'] = apply_filters( 'field59_pagination_total', $total, $pagination, $videos );

				$pagination['total_pages'] = apply_filters( 'field59_pagination_total_pages', $total_pages, $pagination, $videos );
			}
			$pg_display_top = self::get_display_pagination( $pagination );

			$pagination['position'] = 'bottom';
			$pg_display_bot         = self::get_display_pagination( $pagination );

			// Build response.
			$output = array(
				'tbody' => $tbody,
				'subtitle' => $subtitle,
				'pagination_top' => $pg_display_top,
				'pagination_bottom' => $pg_display_bot,
			);
			wp_send_json(
				array(
					'success' => true,
					'result' => $output,
				)
			);
			wp_die();
		}

  /**
   * Creates and adds a Field59 media taxonomy to an attachment.
   *
   * Supports the most basic default use of Enhanced Media Library's attachment taxonomy.
   *
   * @param int $attachment_id The Post ID of the attachment.
   * @return void
   */
  public static function apply_eml_category( $attachment_id ) {

    // Use the default taxonomy EML sets.
    $taxonomy      = 'media_category';
    $category_name = 'Field59 Video Thumbnails';

    $taxonomy_exist = taxonomy_exists( $taxonomy );
    if ( $taxonomy_exist ) {
      $term = term_exists( $category_name, $taxonomy );
      if ( empty( $term ) ) {
        // Create term.
        $term = wp_insert_term( $category_name, $taxonomy, array( 'description' => 'Images imported from Field59 Workflow' ) );
      }

      wp_add_object_terms( $attachment_id, intval( $term['term_id'] ), $taxonomy );
    }
  }

  /**
   * Restricts what domains images will be pulled in from.
   *
   * Prefered method of disabling is by removing the hook from the filter.
   *
   * @param string $url The URL in question.
   * @return false|string False when the import should not proceed; the URL to continue.
   */
  public static function whitelist_image_domains( $url = '' ) {

    /**
     * Filters the list of domains that are whitelisted to import images from.
     *
     * @param array $hosts A list of hosts to whitelist.
     */
    $approved_domains = apply_filters(
      'field59_video_whitelist_image_domains',
      array(
        'cdn.field59.com',
        'redirect.field59.com',
      )
    );

    $host = strtolower( wp_parse_url( $url, PHP_URL_HOST ) );

    if ( ! in_array( $host, $approved_domains, 1 ) ) {
      return false;
    }

    return $url;
  }
}