<?php
/*
 * Plugin Name: Rayos RSS Ingestion
 * Plugin URI: https://TownNews.com
 * Description: RSS Ingestion Temporary
 * Version: 2019.12.05
 * Author: TownNews.com
 * Author URI: https://TownNews.com
 * */

// Prevent direct file access.
if (!defined('ABSPATH')) {
    die('Direct access not allowed!');
}

if (! class_exists('RayosRSSIngest')) {
    class RayosRSSIngest
    {
        // Cron name and meta field key names
        const CRON_NAME = 'rayos_rss_ingest';
        const RSS_GUID_META_NAME = 'rayos_rss_ingest_guid';
        const RSS_AUTHOR_META_NAME = 'rss_ingest_author';

        /**
         * Constructor
         * Establish options page and settings fields within ACF
         * Establish cron job
         * Establish helper functions
         * Fetch and ingest articles
         *
         * @return void
         */
        public function __construct()
        {

            /* Add ACF fields */
            add_filter('init', array($this, 'rayos_rss_acf_include'));

            /* Cron feed processing */
            add_filter('cron_schedules', array($this, 'filter_cron_schedules'));
            add_action('init', array($this, 'cron_activation'));
            add_action(self::CRON_NAME, array($this, 'rayos_rss_ingestion_main'));
            add_action('admin_menu', array($this, 'rayos_ingest_settings'));
            //add_action('admin_menu', array($this, 'rayos_ingest_settings_fields'));
            register_deactivation_hook(__FILE__, array($this, 'cron_deactivation'));

            /* Manually Activate for testing */
            //add_action('wp', array($this, 'rayos_rss_ingestion_main'));


            // Cron runs outside of wp-admin and needs these includes for media_sideload_image()
            include_once(ABSPATH."/wp-admin/includes/media.php");
            include_once(ABSPATH."/wp-admin/includes/file.php");
            include_once(ABSPATH."/wp-admin/includes/image.php");


            // Filter author for feed author
            add_filter('gtx_author_data', array($this, 'filter_rss_ingest_author_replace'), 20, 2);
        }

        public function rayos_rss_acf_include()
        {
            $load_json=json_decode(file_get_contents(plugin_dir_path(__FILE__).'/rayos_rss_ingest_acf.json'), true);
            foreach ($load_json as $json) {
                acf_add_local_field_group($json);
            }
        }

        /**
         * Adds the fiveminute option to the schedule for the plugin's wp cron job
         *
         * @param array $param
         * @return array
         */
        public function filter_cron_schedules($param)
        {
            $param['fivemin'] = array(
                'interval' => 300,
                'display'  => 'Every 5 minutes'
            );
            return $param;
        }

        /**
         * Activate and schedule the next cron event if one is not already scheduled
         *
         * @return void
         */
        public function cron_activation()
        {
            if (!wp_next_scheduled(self::CRON_NAME)) {
                wp_schedule_event(time(), 'fivemin', self::CRON_NAME);
            }
        }

        /**
         * Removes the cron job associated with this plugin
         *
         * @return void
         */
        public function cron_deactivation()
        {
            $timestamp = wp_next_scheduled(self::CRON_NAME);
            wp_unschedule_event($timestamp, self::CRON_NAME);
        }
        
        public function rayos_ingest_settings()
        {
            // Fallback will add page to WP Admin > Settings.
            if (defined('GTX_INTERNAL_CONFIG_PAGE_SLUG')) {
                $parent_menu_slug = GTX_INTERNAL_CONFIG_PAGE_SLUG;
            } else {
                $parent_menu_slug = 'options-general.php';
            }

            // Fallback will use capability manage_options.
            if (defined('GTX_INTERNAL_CONFIG_CAPABILITY')) {
                $capability = GTX_INTERNAL_CONFIG_CAPABILITY;
            } else {
                $capability = 'manage_options';
            }
            // Add options page slugged "rayos_rss_ingestion"
            if (function_exists('acf_add_options_page')) {
                acf_add_options_page(
                    array(
                        'page_title' => 'RSS Ingest Settings',
                        'menu_title' => 'RSS Ingest Settings',
                        'menu_slug' => 'rayos_rss_ingestion',
                        'capability' => $capability,
                        'redirect' => false,
                        'parent_slug' => $parent_menu_slug,
                    )
                );
            }
        }

        /**
         * Prints a message to the debug file that can easily be called by any subclass.
         *
         * @param mixed $message      an object, array, string, number, or other data to write to the debug log
         * @param bool  $die whether or not the The function should exit after writing to the log
         *
         */
        protected function log($message, $die = false)
        {
            error_log(print_r($message, true));
            if ($die) {
                exit;
            }
        }

        /**
         * Meta Check - check if meta field exists in other articles
         * Added post_status to also check pending/draft/future (scheduled) posts
         *
         * @return bool
         */
        public function meta_check($meta_name, $meta_value)
        {
            $post_match = get_posts([
                'post_type' => 'ingested_article',
                'meta_key'   => $meta_name,
                'meta_value' => $meta_value,
                'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future'),
            ]);
            
            if (!empty($post_match)) {
                return $post_match[0]->ID;
            } else {
                return false;
            }
        }

        /**
         * filter_rss_ingest_author_replace
         * Checks if the RSS_AUTHOR_META_NAME custom field exists (rss import post) and subsititues the author w/ the byline from the feed
         *
         * @return
         */
        public function filter_rss_ingest_author_replace()
        {
            global $post;
            $post_id = $post->ID;
            
            $rss_author_name = get_post_meta($post_id, self::RSS_AUTHOR_META_NAME, true);

            if(empty($rss_author_name)){

                $rss_author_name = get_the_author_meta( 'display_name', $post_id->post_author );
            }
            

            //RSS_AUTHOR_META_NAME
            if ($rss_author_name) {
                ob_start(); ?>
                    <div class="entry-meta entry-author">
                        <span><?php echo $rss_author_name; ?></span> 
                        
                    </div>
                <?php
                return ob_get_clean();
            }
        }

        /**
         * insert_feed_item
         *
         * Creates or updates published post from item object.
         *
         * @param mixed $item - iterated feed object from rss_object
         * @param mixed $wp_author - the ACF field value for the WP author
         * @param mixed $meta_name - the name of the rss_ingestion guid meta field self::RSS_GUID_META_NAME
         * @param mixed $guid - the guid value from the item object
         * @param mixed $category - the ACF field value of selected category
         * @param mixed $existing_post_id - the post ID (if update)
         * @param mixed $dc_creator - the author of the ingested article for author attribution
         * @param mixed $img_url - URL of the preferred article/featured image
         * @param mixed $description - the "body" of the given post. 
         * @param mixed $feed - individual feed array consisting of ACF options/values for specific feed
         * 
         * @return void
         */
        public function insert_feed_item($item, $wp_author, $meta_name, $guid, $category, $existing_post_id, $dc_creator, $img_url, $description, $feed){        

            if(empty($description)){
                continue;
            }
            
            if (!$existing_post_id) {

                //Create post
                $new_post_id = wp_insert_post(array(
                    'post_type' => 'ingested_article',
                    'post_title' => $item->title->__toString(),
                    'post_content' => $description,
                    'post_status' => 'publish',
                    'post_author' => $wp_author,
                    'post_date'     => date("Y-m-d H:i:s", strtotime($item->pubDate)),
                    'post_category' =>  array($category),
                    'meta_input'    =>  array(
                        self::RSS_AUTHOR_META_NAME  => $dc_creator,
                        self::RSS_GUID_META_NAME    => $guid,
                    )
                ));

                // Grab and attatch image as featured image                
                $featured_image_id = media_sideload_image( $img_url, $new_post_id, $item->title->__toString(), 'id' );
                update_post_meta( $new_post_id, '_thumbnail_id', $featured_image_id );
                update_post_meta( $featured_image_id, '_wp_attachment_image_alt', $item->title->__toString() );

                // Copyright Meta Data
                if($feed['source'] == 'cnn'){
                    update_post_meta( $new_post_id, 'article_footer_copyright', 'cnn' );
                }
                
                
                return $new_post_id;

            } elseif ($existing_post_id) {

                $existing_guid = get_post_meta($existing_post_id, $meta_name, true);
                if ($existing_guid !== $guid) {
                    // Update post
                    $post_update = wp_update_post(array(
                        'ID' => $existing_post_id,
                        'post_type' => 'ingested_article',
                        'post_title' => $item->title->__toString(),
                        'post_content' => $description,
                        'post_status' => 'publish',
                        'post_author' => $wp_author,
                        'post_date'     => date("Y-m-d H:i:s", strtotime($item->pubDate)),
                        'post_category' =>  array($category),
                        'meta_input'    =>  array(
                            self::RSS_AUTHOR_META_NAME  => $dc_creator,
                            self::RSS_GUID_META_NAME    => $guid,
                        )
                    ));

                    // Grab and attatch image as featured image                
                    $featured_image_id = media_sideload_image( $img_url, $existing_post_id, $item->title->__toString(), 'id' );
                    update_post_meta( $existing_post_id, '_thumbnail_id' );
                    update_post_meta( $featured_image_id, '_wp_attachment_image_alt', $item->title->__toString() );

                    // Copyright Meta Data
                    if($feed['source'] == 'cnn'){
                        update_post_meta( $existing_post_id, 'article_footer_copyright', 'cnn' );
                    }
                }
                
                return $existing_post_id;
            }
        }

        public function parse_dcc_feed($rss_object, $feed){

            // Grab namespace from feed, need this to get byline
            $namespaces = $rss_object->getNamespaces(true);

            // Loop through each item of the feet
            foreach ($rss_object->channel->item as $item) {

                // Grab namespace and return string of item byline
                $dc_meta = $item->children($namespaces['dc']);
                // If no dc_fields, 
                if($dc_meta){
                    $dc_creator = $dc_meta->creator[0]->__toString();
                }else{
                    $dc_creator = NULL;
                }
                
                // Check if guid is present, if not, skip it.
                if(!empty($item->guid)){
                    // Remove attribute param from guid
                    unset($item->guid[@attibutes]);

                    // Get guid valud as string (its a url), explode with slashes, and return the last path (the 36 char GUID)
                    // from this -> http://news.lee.net/tncms/asset/editorial/7ec6e382-1b84-11ea-9de6-bbb6a8e41831
                    // to this -> 7ec6e382-1b84-11ea-9de6-bbb6a8e41831
                    $guid =  array_pop(explode('/', $item->guid->__toString()));
                }else{
                    continue;
                }
                

                // Check guid length, if less than 10 skip this one. Prevents dupes without proper guid
                // This is a soft gate against a invalid/malformed guid to prevent duplicate posts.
                // Typically the GUID's are 36 characters, but this is an ARBITRARY protection from malformed/invalid values
                // === THE NUMBER 10 IS ARBITRARY ===
                if (strlen($guid) < 10) {
                    self::log('Invalid GUID - '.$guid);
                    continue 1;
                }
                
                // $post_id will be false or a post_id string
                $post_id = self::meta_check(self::RSS_GUID_META_NAME, $guid);

                // Image comes in w/ size query parameter, explode and isolate original URL
                if($item->enclosure['url'][0]){
                    $img_url_arr = explode("?", $item->enclosure['url'][0]);
                    $img_url = $img_url_arr[0]; 
                }
                

                $description = $item->description->__toString();

                // Pass the info and insert/update the post. Return the ID of the post if needed in the future.
                $feed_item_id = self::insert_feed_item($item, $feed['wp_user'], self::RSS_GUID_META_NAME, $guid, $feed['cat_id'], $post_id, $dc_creator, $img_url, $description, $feed);

                // Unset the post_id
                unset($post_id);

            }
        }

        public function parse_cnn_feed($rss_object, $feed){

            // Grab namespace from feed, need this to get byline
            $namespaces = $rss_object->getNamespaces(true);

            // Loop through each item of the feet
            foreach ($rss_object->channel->item as $item) {

                //EMBARGOS - get the embargo and check if it's one of the 2 conditions to allow to pass
                // if embargo is none/NONE or if it's not set at all (closed tag)
                // check for this by checking if string length is 5 or more
                if(!empty($item->children($namespaces['cnn-video']))){
                    $cnn_content_video = $item->children($namespaces['cnn-video']);
                }
                if(!empty($item->children($namespaces['cnn-article']))){
                    $cnn_content_article = $item->children($namespaces['cnn-article']);
                }
                // HAVE to get positive embargo. If its none/NONE thats fine, but it has to exist. 
                // If not, don't trust it.
                if(!empty($cnn_content_video->embargoes)){
                    $embargoes = $cnn_content_video->embargoes;
                    if(strlen($embargoes->__toString())  >= 5){
                        continue;
                    }
                }
                
                
                

                // if $cnn-article->byline exists, use it, otherwise null it out
                if($cnn_content_article->byline){
                    $dc_creator = $cnn_content_article->byline->__toString();
                }else{
                    $dc_creator = NULL;
                }
                
                // Remove attribute param from guid
                //unset($item->guid[@attibutes]);

                // CNN provides a GUID, just gotta pop it off a path if article guid doesnt exist
                if($cnn_content_article->guid){
                    $guid = $cnn_content_article->guid->__toString();
                }else if($item->guid->__toString()){
                    $guid =  array_pop(explode('/', $item->guid->__toString()));
                }else{
                    // no guid available
                    continue;
                }
                

                // Check guid length, if less than 10 skip this one. Prevents dupes without proper guid
                // This is a soft gate against a invalid/malformed guid to prevent duplicate posts.
                // Typically the GUID's are 36 characters, but this is an ARBITRARY protection from malformed/invalid values
                // === THE NUMBER 10 IS ARBITRARY ===
                if (strlen($guid) < 10) {
                    self::log('Invalid GUID - '.$guid);
                    continue;
                }

                // $post_id will be false or a post_id string
                $post_id = self::meta_check(self::RSS_GUID_META_NAME, $guid);

                // Grab thumbnail image from cnn-video:thumbnailImage
                if (!empty($cnn_content_video->thumbnailImage->__toString())) {
                    $img_url = $cnn_content_video->thumbnailImage->__toString();
                }

                //re-define item title if video or article. Video takes precidence
                if ($cnn_content_article->headline) {
                    $item->title = $cnn_content_article->headline;
                }elseif($cnn_content_video->title){
                    $item->title = $cnn_content_video->title;
                }

                // building the body we need to first check if there is a description, a video, and then an article in that order.
                // use the $cnn_content_video->description, $cnn_content_video->singleembed, and $cnn-article->body if it exists
                $description = "";
                if($cnn_content_video->singleembed){
                    $description .= $cnn_content_video->singleembed->__toString();
                }
                if($cnn_content_video->description){
                    $description .= $cnn_content_video->description->__toString();
                }
                if($cnn_content_article->body){
                    $description .= $cnn_content_article->body->__toString();
                }
               //var_dump($description);


                // Pass the info and insert/update the post. Return the ID of the post if needed in the future.
                $feed_item_id = self::insert_feed_item($item, $feed['wp_user'], self::RSS_GUID_META_NAME, $guid, $feed['cat_id'], $post_id, $dc_creator, $img_url, $description, $feed);



                // Unset the post_id
                unset($post_id);

            }
        }


        /**
         * rayos_rss_ingestion_main
         *
         * This is where the magic happens. Gather stored data, fetch feeds, loop items, and insert articles.
         * Takes nothing, returns nothing.
         *
         * @return void
         */
        public function rayos_rss_ingestion_main()
        {
            
            
            // Get fields from settings
            $rayos_rss_ingest_1_url = get_field('rayos_rss_ingestion_1_url', 'option');
            $rayos_rss_ingest_1_id = get_field('rayos_rss_ingestion_category_1_id', 'option');
            $rayos_rss_ingest_1_source = get_field('rayos_rss_ingestion_feed_1_source', 'option');

            $rayos_rss_ingest_2_id = get_field('rayos_rss_ingestion_category_2_id', 'option');
            $rayos_rss_ingest_2_url = get_field('rayos_rss_ingestion_2_url', 'option');
            $rayos_rss_ingest_2_source = get_field('rayos_rss_ingestion_feed_2_source', 'option');

            $rayos_rss_ingest_wp_user = get_field('rayos_rss_ingestion_wp_user', 'option');

            $rayos_rss_ingestion_1_image_option = get_field('rayos_rss_ingestion_1_image_option', 'option');
            $rayos_rss_ingestion_2_image_option = get_field('rayos_rss_ingestion_2_image_option', 'option');


            $feeds = array(
                        array(
                                'url' => $rayos_rss_ingest_1_url,
                                'cat_id' => $rayos_rss_ingest_1_id,
                                'source' => $rayos_rss_ingest_1_source,
                                'image_option' => $rayos_rss_ingestion_1_image_option,
                                'wp_user' => $rayos_rss_ingest_wp_user,
                        ),
                        array(
                                'url' => $rayos_rss_ingest_2_url,
                                'cat_id' => $rayos_rss_ingest_2_id,
                                'source' => $rayos_rss_ingest_2_source,
                                'image_option' => $rayos_rss_ingestion_2_image_option,
                                'wp_user' => $rayos_rss_ingest_wp_user,
                        )
                    );

            // Loop the URL's listed, will work with 1, 2, or more in the future. But will need a different solution for gathering URL's and cat id's.
            foreach ($feeds as $feed) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL            => $feed['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => 'UTF-8',
                    CURLOPT_SSL_VERIFYPEER => FALSE,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ));

                $rss_file = curl_exec($curl);

                if (curl_errno($curl)) {
                    $error_msg = curl_error($curl);
                }

                curl_close($curl);

                if (isset($error_msg)) {
                    self::log($error_msg);
                    break 1;
                }

                // Attempt to parse the feed, if error, log it and skip the loop
                try {
                    $rss_object = new SimpleXMLElement($rss_file, LIBXML_NOERROR);
                } catch (Exception $e) {
                    self::log($e->getMessage());
                    break 1;
                }

                if($feed['source'] == 'dcc'){
                    self::parse_dcc_feed($rss_object, $feed);
                }elseif($feed['source'] == 'cnn'){
                    self::parse_cnn_feed($rss_object, $feed);
                }else{
                    return false;
                }
            

            }
        }
    }

    $rayos_rss_ingest = new RayosRSSIngest();
}
