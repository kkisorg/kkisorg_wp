<?php

if (!defined('ABSPATH')) die('No direct access.');

/** 
 * Class to handle ajax endpoints, specifically used by vue components
 * If possible, keep logic here to a minimum.
 */
class MetaSlider_Api {
	
	/**
	 * Theme instance
	 * 
	 * @var object
	 * @see get_instance()
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Setup 
	 */
	public function setup() {
		$this->slideshows = new MetaSlider_Slideshows();
		$this->themes = MetaSlider_Themes::get_instance();
	}

	/**
	 * Used to access the instance
	 */
	public static function get_instance() {
		if (null === self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Register routes for admin ajax. Even if not used these can still be available.
	 */
	public function register_admin_ajax_hooks() {

		// Slideshows
		add_action('wp_ajax_ms_get_slideshows', array(self::$instance, 'get_slideshows'));
		add_action('wp_ajax_ms_list_slideshows', array(self::$instance, 'list_slideshows'));
		add_action('wp_ajax_ms_get_single_slideshow', array(self::$instance, 'get_single_slideshow'));
		add_action('wp_ajax_ms_get_preview', array(self::$instance, 'get_preview'));
		add_action('wp_ajax_ms_delete_slideshow', array(self::$instance, 'delete_slideshow'));
		add_action('wp_ajax_ms_duplicate_slideshow', array(self::$instance, 'duplicate_slideshow'));
		add_action('wp_ajax_ms_save_slideshow', array(self::$instance, 'save_slideshow'));
		add_action('wp_ajax_ms_search_slideshows', array(self::$instance, 'search_slideshows'));

		// Themes
		add_action('wp_ajax_ms_get_all_free_themes', array(self::$instance, 'get_all_free_themes'));
		add_action('wp_ajax_ms_get_custom_themes', array(self::$instance, 'get_custom_themes'));
		add_action('wp_ajax_ms_set_theme', array(self::$instance, 'set_theme'));

		// Slides
		add_action('wp_ajax_ms_import_images', array(self::$instance, 'import_images'));

		// Settings
		add_action('wp_ajax_ms_update_all_settings', array(self::$instance, 'save_all_settings'));
		add_action('wp_ajax_ms_update_single_setting', array(self::$instance, 'save_single_setting'));
		add_action('wp_ajax_ms_update_global_setting', array(self::$instance, 'save_global_setting'));
		
		// Other
		add_action('wp_ajax_set_tour_status', array(self::$instance, 'set_tour_status'));
	}

	/**
	 * Helper function to verify access
	 * 
	 * @return boolean
	 */
	public function can_access() {

		$capability = apply_filters('metaslider_capability', 'edit_others_posts');

		// Check for the nonce on the server (used by WP REST)
		if (isset($_SERVER['HTTP_X_WP_NONCE']) && wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'wp_rest')) {
			return current_user_can($capability);
		}

		// This is for when not using Axios (example: callout.php)
		if (isset($_REQUEST['METASLIDER_NONCE']) && wp_verify_nonce($_REQUEST['METASLIDER_NONCE'], 'metaslider_request')) {
			return current_user_can($capability);
		}

		return false;
	}

	/**
	 * Helper function to return an access denied response
	 */
	public function deny_access() {
		wp_send_json_error(array(
			'message' => __('You do not have access to this resource.', 'ml-slider')
		), 401);
	}

	/**
	 * Helper function to get data from the request 
	 * (supports rest & admin-ajax)
	 * Does not handle any validation
	 * 
	 * @param object $request 	 The request
	 * @param array  $parameters The wanted parameters
	 * @return array
	 */
	public function get_request_data($request, $parameters) {
		$results = array();
		foreach ($parameters as $param) {
			if (method_exists($request, 'get_param')) {
				$results[$param] = $request->get_param($param);
			} else {
				$results[$param] = isset($_REQUEST[$param]) ? stripslashes_deep($_REQUEST[$param]) : null;
			}
		}

		return $results;
	}

	/**
	 * Returns all slideshows
	 *
	 * @param object|null $request The request
	 */
    public function get_slideshows($request = null) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('page', 'count'));
		$page = isset($data['page']) ? intval($data['page']) : 1;
		$count = isset($data['count']) ? intval($data['count']) : 25;

		$slideshows = $this->slideshows->get($count, $page);

		$slideshows = array_map(array($this, 'get_slide_data'), $slideshows);

		if (is_wp_error($slideshows)) {
			wp_send_json_error(array(
				'message' => $slideshows->get_error_message()
			), 400);
		}
		
		wp_send_json_success($slideshows, 200);
	}

	/**
	 * Returns all slideshows
	 *
	 * @param object|null $request The request
	 */
    public function search_slideshows($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('term', 'count'));

		$slideshows = $this->slideshows->search((string) $data['term'], (string) $data['count']);

		$slideshows = array_map(array($this, 'get_slide_data'), $slideshows);

		if (is_wp_error($slideshows)) {
			wp_send_json_error(array(
				'message' => $slideshows->get_error_message()
			), 400);
		}
		
		wp_send_json_success($slideshows, 200);
	}

	/**
	 * Returns a list of all slideshows
	 *
	 * @param object|null $request The request
	 */
    public function list_slideshows($request) {
		if (!$this->can_access()) $this->deny_access();

		$slideshows = $this->slideshows->get_all_slideshows();

		if (is_wp_error($slideshows)) {
			wp_send_json_error(array(
				'message' => $slideshows->get_error_message()
			), 400);
		}

		wp_send_json_success($slideshows, 200);
	}

	/**
	 * Returns a single slideshow
	 *
	 * @param object $request The request
	 */
    public function get_single_slideshow($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('id'));

		$slideshow = $this->slideshows->get_single($data['id']);

		$slideshow = array_map(array($this, 'get_slide_data'), $slideshow);

		if (is_wp_error($slideshow)) {
			wp_send_json_error(array(
				'message' => $slideshow->get_error_message()
			), 400);
		}
		
		wp_send_json_success($slideshow, 200);
    }

	/**
	 * Maps some slide data to the slideshow
	 * 
	 * @param array $slideshow - a slideshow
	 * 
	 * @return array
	 */
    private function get_slide_data($slideshow) {
		
		if (isset($slideshow['slides'])) {
			foreach ($slideshow['slides'] as $order => $slide_id) {

				$thumbnail_id = 'attachment' === get_post_type($slide_id) ? $slide_id : get_post_thumbnail_id($slide_id);
				$thumbnail_data = wp_get_attachment_image_src($thumbnail_id);

				unset($slideshow['slides'][$order]);
				if (isset($thumbnail_data['0'])) {
					$slideshow['slides'][$order] = array(
						'id' => $slide_id,
						'thumbnail' => $thumbnail_data['0'],
						'order' => $order
					);
				}
			}
			$slideshow['slides'] = array_values($slideshow['slides']);
		}
	
		return $slideshow;
	}

	/**
	 * Returns all custom themes
	 */
    public function get_custom_themes() {
		if (!$this->can_access()) $this->deny_access();
		
		$themes = $this->themes->get_custom_themes();

		if (is_wp_error($themes)) {
			wp_send_json_error(array(
				'message' => $themes->get_error_message()
			), 400);
		}

		wp_send_json_success($themes, 200);
	}

	/**
	 * Returns all themes
	 */
    public function get_all_free_themes() {
		if (!$this->can_access()) $this->deny_access();
		
		$themes = $this->themes->get_all_free_themes();

		if (is_wp_error($themes)) {
			wp_send_json_error(array(
				'message' => $themes->get_error_message()
			), 400);
		}

		wp_send_json_success($themes, 200);
	}
	
	/**
	 * Sets a specific theme
	 * 
	 * @param object $request The request
	 */
    public function set_theme($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'theme'));
		
		$response = $this->themes->set(absint($data['slideshow_id']), (array) $data['theme']);
		
		if (!$response) {
			wp_send_json_error(array(
				'message' => 'There was an issue while attempting to save the theme. Please refresh and try again.'
			), 400);
		}

		// If we made it this far, return the theme data
		wp_send_json_success((array) $data['theme'], 200);
    }
	
	/**
	 * Returns the preview HTML
	 * 
	 * @param object $request The request
	 */
    public function get_preview($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'theme_id'));

		// The theme id can be either a string or null, exit if it's something else
		if (!is_null($data['theme_id']) && !is_string($data['theme_id'])) {
			wp_send_json_error(array(
				'message' => __('The request format was not valid.', 'ml-slider')
			), 415);
		}

		// If the slideshow was deleted
		$slideshow = get_post($data['slideshow_id']);
		if ('publish' !== $slideshow->post_status) {
			wp_send_json_error(array(
				'message' => __('This slideshow is no longer available.', 'ml-slider')
			), 410);
		}

		$html = $this->slideshows->preview(
			absint($data['slideshow_id']), $data['theme_id']
		);

		if (is_wp_error($html)) {
			wp_send_json_error(array(
				'message' => $html->get_error_message()
			), 400);
		}

		wp_send_json_success($html, 200);
	}
	
	/**
	 * Duplicate a slideshow
	 * 
	 * @param object $request The request
	 */
    public function duplicate_slideshow($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id'));

		$new_slideshow = $this->slideshows->duplicate(absint($data['slideshow_id']));
		
		if (is_wp_error($new_slideshow)) {
			wp_send_json_error(array(
				'message' => $new_slideshow->get_error_message()
			), 400);
		}

		wp_send_json_success($new_slideshow, 200);
	}
	/**
	 * Delete a slideshow
	 * 
	 * @param object $request The request
	 */
    public function delete_slideshow($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'slider_id'));

		// Backwards compatability for slider_id param
		$slideshow_id = is_null($data['slideshow_id']) ? $data['slider_id'] : $data['slideshow_id'];

		// If the slideshow was deleted
		$slideshow = get_post($slideshow_id);
		if ('publish' !== $slideshow->post_status) {
			wp_send_json_error(array(
				'message' => __('This slideshow is no longer available.', 'ml-slider')
			), 410);
		}

		// Confirm it's one of ours
		if ('ml-slider' !== get_post_type($slideshow_id)) {
			wp_send_json_error(array(
				'message' => __('This was not a slideshow, so we cannot delete it.', 'ml-slider')
			), 409);
		}

		$next_slideshow = $this->slideshows->delete(absint($slideshow_id));
		
		if (is_wp_error($next_slideshow)) {
			wp_send_json_error(array(
				'message' => 'There was an issue while attempting delete the slideshow. Please refresh and try again.'
			), 400);
		}

		wp_send_json_success($next_slideshow, 200);
	}
	
	/**
	 * Save a slideshow
	 * 
	 * @param object $request The request
	 */
    public function save_slideshow($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'attachment', 'count'));

		// If we are missing the count, then it's likely the payload was truncated
		if (!$data['count'] && version_compare(phpversion(), '5.3.9', '>=')) {
			
			// If the input vars count is close to the max allowed, assume that the data was truncated and inform the user to increase the value
			$current_vars = count($_POST, COUNT_RECURSIVE); 
			if (($current_vars + 50) > ini_get('max_input_vars')) { // phpcs:ignore PHPCompatibility.IniDirectives.NewIniDirectives.max_input_varsFound
				wp_send_json_error(array(
					'current_input_vars' => $current_vars
				), 400);
			}
		}

		try {
			if (isset($data['attachment'])) {
				foreach ($data['attachment'] as $slide_id => $fields) {
					do_action("metaslider_save_{$fields['type']}_slide", $slide_id, $data['slideshow_id'], $fields);
				}
			}
		} catch (Exception $e) {
			wp_send_json_error(array('message' => $e->getMessage()), 400);
		}

		wp_send_json_success('OK', 200);
	}

	/**
	 * Update tour status
	 * 
	 * @param object $request The request
	 */
    public function set_tour_status($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('current_step'));

		// This wont provide a useful return
		update_option('metaslider_tour_cancelled_on', $data['current_step']);

		wp_send_json_success('OK', 200);
	}
	
	/**
	 * Update a single seting specific to a slideshow
	 * 
	 * @param object $request The request
	 */
    public function save_single_setting($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'setting_key', 'setting_value'));

		// Confirm it's one of ours
		if ('ml-slider' !== get_post_type($data['slideshow_id'])) {
			wp_send_json_error(array(
				'message' => __('This was not a slideshow, so we cannot update the setting.', 'ml-slider')
			), 409);
		}

		// If it's the title, process 
		if ('title' === $data['setting_key']) {
			wp_update_post(array(
				'ID' => absint($data['slideshow_id']),
				'post_title'  => (string) $data['setting_value']
			));
			return wp_send_json_success('OK', 200);
		}

		// This wont provide a useful return
		update_post_meta($data['slideshow_id'], $data['setting_key'], $data['setting_value']);

		wp_send_json_success('OK', 200);
	}

	/**
	 * Update all settings on a slideshow
	 * 
	 * @param object $request The request
	 */
    public function save_all_settings($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'settings'));

		$result = $this->slideshows->save(
			absint($data['slideshow_id']), (array) $data['settings']
		);
		
		if (is_wp_error($result)) {
			wp_send_json_error(array(
				'message' => 'There was an issue while attempting delete the slideshow. Please refresh and try again.'
			), 400);
		}

		wp_send_json_success($result, 200);
	}
	
	/**
	 * Update a global user setting
	 * 
	 * @param object $request The request
	 */
    public function save_global_setting($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('setting_key', 'setting_value'));

		// Ensure the key is prefixed
		$key = $data['setting_key'];
		$key = (0 === strpos($key, 'metaslider_')) ? $key : 'metaslider_' . $key;

		// This will not provide a useful return (reminder, key is prefixed)
		update_user_option(get_current_user_id(), $key, $data['setting_value']);

		wp_send_json_success('OK', 200);
	}
	
	/**
	 * Import theme images
	 * 
	 * @param object $request The request
	 */
    public function import_images($request) {
		if (!$this->can_access()) $this->deny_access();

		$data = $this->get_request_data($request, array('slideshow_id', 'theme_id', 'slide_id', 'image_data'));

		// Create a slideshow if one doesn't exist
        if (is_null($data['slideshow_id']) || !absint($data['slideshow_id'])) {
            $data['slideshow_id'] = $this->slideshows->create();

            if (is_wp_error($data['slideshow_id'])) {
                wp_send_json_error(array(
                    'message' => $data['slideshow_id']->get_error_message()
                ), 400);
            }
		}

		// If there are files here, then we need to prepare them
		// Dont use get_file_params() as it's WP4.4
		$images = isset($_FILES['files']) ? $this->process_uploads($_FILES['files'], $data['image_data']) : array();

		// $images should be an array of image data at this point
		// Capture the slide markup that is typically echoed from legacy code
		ob_start();

		$image_ids = MetaSlider_Image::instance()->import($images, $data['theme_id']);
		if (is_wp_error($image_ids)) {
            wp_send_json_error(array(
                'message' => $image_ids->get_error_message()
            ), 400);
        }
		
		$errors = array();
		$method = is_null($data['slide_id']) ? 'create_slide' : 'update';
		foreach ($image_ids as $image_id) {
			$slide = new MetaSlider_Slide(absint($data['slideshow_id']), $data['slide_id']);
			$slide->add_image($image_id)->$method();
			if (is_wp_error($slide->error)) array_push($errors, $slide->error);
		}

		// Disregard the output. It's not needed for imports
		ob_end_clean();

        // Send back the first error, if any
        if (isset($errors[0])) {
            wp_send_json_error(array(
                'message' => $errors[0]->get_error_message()
            ), 400);
        }

		wp_send_json_success(wp_get_attachment_thumb_url($slide->slide_data['id']), 200);
	}


	/**
	 * Verify uploads are useful and return an array with metadata
	 * For now only handles images.
	 * 
	 * @param array $files An array of the images
	 * @param array $data  Data for the image, keys should match
	 *
	 * @return array An array with image data
	 */
	public function process_uploads($files, $data = null) {
		$images = array();
		foreach($files['tmp_name'] as $index => $tmp_name) {

			// If there was an error, skip this file
			// TODO: consider reporting an error back to the user, but skipping might be best
			if (!empty($files['error'][$index])) continue;

			// If the name is empty or isn't an uploaded file, skip it
			if (empty($tmp_name) || !is_uploaded_file($tmp_name)) continue;

			// For now there's no reason to import anything but images
			if (!strstr(mime_content_type($tmp_name), "image/")) continue;
				
			// Ignore images too large for the server (According to WP)
			// The server probably handles this already
			// TODO: possibly provide user feedback, but skipping moves forward
			$max_upload_size = wp_max_upload_size();
			if (!$max_upload_size) $max_upload_size = 0;
			$file_size = $files['size'][$index];
			if ($file_size > $max_upload_size) continue;

			// Tests were passed, so move forward with this image
			$filename = $files['name'][$index];
			$images[$filename] = array(
				'source' => (string) $tmp_name,
				'caption' => isset($data[$filename]['caption']) ? (string) $data[$filename]['caption'] : '',
				'title' => isset($data[$filename]['title']) ? (string) $data[$filename]['title'] : '',
				'description' => isset($data[$filename]['description']) ? (string) $data[$filename]['description'] : '',
				'alt' => isset($data[$filename]['alt']) ? (string) $data[$filename]['alt'] : ''
			);
		}
		return $images;
	}
}

if (class_exists('WP_REST_Controller')) :
	/**
	 * Class to handle REST route api endpoints.
	 */
	class MetaSlider_REST_Controller extends WP_REST_Controller {

		/**
		 * Namespace and version for the API
		 * 
		 * @var string
		 */
		protected $namespace = 'metaslider/v1';

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action('rest_api_init', array($this, 'register_routes'));
			$this->api = MetaSlider_Api::get_instance();
			$this->api->setup();
		}

		/**
		 * Register routes
		 */
		public function register_routes() {

			register_rest_route($this->namespace, '/slideshow/all', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'get_slideshows')
			)));
			register_rest_route($this->namespace, '/slideshow/list', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'list_slideshows')
			)));
			register_rest_route($this->namespace, '/slideshow/single', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'get_single_slideshow')
			)));
			register_rest_route($this->namespace, '/slideshow/preview', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'get_preview')
			)));
			register_rest_route($this->namespace, '/slideshow/save', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'save_slideshow')
			)));
			register_rest_route($this->namespace, '/slideshow/delete', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'delete_slideshow')
			)));
			register_rest_route($this->namespace, '/slideshow/duplicate', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'duplicate_slideshow')
			)));
			register_rest_route($this->namespace, '/slideshow/search', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'search_slideshows')
			)));
			
			register_rest_route($this->namespace, '/themes/all', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'get_all_free_themes')
			)));
			register_rest_route($this->namespace, '/themes/custom', array(array(
				'methods' => 'GET',
				'callback' => array($this->api, 'get_custom_themes')
			)));
			register_rest_route($this->namespace, '/themes/set', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'set_theme')
			)));
			
			register_rest_route($this->namespace, '/import/images', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'import_images')
			)));
			
			register_rest_route($this->namespace, '/tour/status', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'set_tour_status')
			)));

			register_rest_route($this->namespace, '/settings/save', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'save_all_settings')
			)));

			register_rest_route($this->namespace, '/settings/save-single', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'save_single_setting')
			)));

			register_rest_route($this->namespace, '/settings/save-global', array(array(
				'methods' => 'POST',
				'callback' => array($this->api, 'save_global_setting')
			)));
		}
	}
endif;
