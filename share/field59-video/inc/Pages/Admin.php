<?php
/**
 * @package Field59 Video
 */

namespace Inc\Pages;

use Inc\Api\SettingsApi;
use Inc\Base\BaseController;
use Inc\Api\CallBacks\AdminCallBacks;


  class Admin extends BaseController {
			public $settings;
			public $callbacks;
			public $subpages = array();


		public function register() {
			$this->settings = new SettingsApi();

			$this->callbacks = new AdminCallBacks();

			$this->setSubpages();

			$this->settings->addSubPages( $this->subpages )->register();
		}
		
		public function setSubpages(){
			$this->subpages = array(
				array(
					'parent_slug' => 'options-general.php', 
					'page_title' => 'Field59 Video Settings', 
					'menu_title' => 'Field59 Video', 
					'capability' => 'manage_options', 
					'menu_slug' => 'field59_video_settings', 
					'callback' => array( $this->callbacks, 'adminField59' )
				),
			);
		}
  }