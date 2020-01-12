<?php
/*
 * Plugin Name: MetaSlider Schedule Slides
 * Plugin URI:  https://www.metaslider.com
 * Description: Schedule Slides in MetaSlider, with date and time. Also adds a "hide" checkbox for your Metaslider slides
 * Version:     1.0.4
 * Author:      Team Updraft
 * Author URI:  https://www.metaslider.com
 * License:     GPL-2.0+
 * Copyright:   2017- Simba Hosting Ltd
 *
 * Text Domain: metaslider_schedule_slides
 * Domain Path: /languages/
 */

if (!defined('ABSPATH')) die('No direct access.');

// only loads if MetaSliderPro_Schedule_Slides doesn't exist (MS Pro)
if (!class_exists('MetaSliderPro_Schedule_Slides')) {

	class Meta_Slider_Schedule_Slides {

		/**
		 * Instance object
		 *
		 * @var object
		 * @see get_instance()
		 */
		protected static $instance = NULL; 

		/**
		 * Slide types
		 *
		 * @var array
		 * @see __construct()
		 */
		private $slide_type_filters = array(); 

		/**
		 * __construct
		 */
		private function __construct() {}

		/**
		 * Plugin setup
		 */
		public function setup() {

			if (class_exists('MetaSliderPro_Schedule_Slides'))return;

			$this->slide_type_filters = apply_filters('meta_slider_schedule_slides_types', array(
				'image',
				'layer',
				'html_overlay',
				'post_feed',
				'vimeo',
				'youtube'
			));

			add_action('init', array($this, 'init'), 30);
		}

		/**
		 * Used to access the instance
		 */
		public static function get_instance() {
			if (NULL === self::$instance) self::$instance = new self();
			return self::$instance;
		}
		
		/**
		 * Schedule_slides
		 * Adds filters and actions
		 */
		public function init() {

			// if we don't find MetaSliderPlugin, exit early.
			if(!class_exists('MetaSliderPlugin')) {
				add_action('admin_notices', array($this, 'meta_slider_not_loaded'));
				return false;
			}

			// do_action("metaslider_save_{$fields['type']}_slide", $slide_id, $slider_id, $fields);
			foreach ($this->slide_type_filters as $slide_type) {
				add_filter('metaslider_'.$slide_type.'_slide_tabs', array($this, 'slide_admin_tab'), 10, 4);
				add_action('metaslider_save_'.$slide_type.'_slide', array($this, 'save_settings'), 10, 3);
				add_filter('metaslider_populate_slides_args', array($this, 'filter_query'), 10, 3);
			}

			// prints the JS + css after the slides
			add_action('metaslider_register_admin_styles', array($this, 'extra_styles'));
			load_plugin_textdomain('metaslider_schedule_slides', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		}

		/**
		 * filter_query
		 * Filters the query args. 
		 * 
		 * @param  array $args The slider arguments
		 * @param  int   $slider_id Slider ID
		 * @param  array $settings the Slideshow's settings
		 * @return array
		 */
		public function filter_query($args, $slider_id, $settings) {
			if(!is_admin()) {
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(

						// slide is not hidden
						'relation' => 'OR',
						array(
							'key' => '_meta_slider_slide_is_hidden',
							'value' => 'yes',
							'compare' => '!='
						),
						array(
							'key' => '_meta_slider_slide_is_hidden',
							'value' => '',
							'compare' => 'NOT EXISTS'
						),

					),

					// AND
					array(
						'relation' => 'OR',

						// slide is not scheduled
						array(
							'key' => '_meta_slider_slide_is_scheduled',
							'value' => '',
							'compare' => 'NOT EXISTS'
						),
						array(
							'key' => '_meta_slider_slide_is_scheduled',
							'value' => 'no'
						),

						// or 
						array(
							'relation' => 'AND',

							//Slide is scheduled
							array(
								'key' => '_meta_slider_slide_is_scheduled',
								'value' => 'yes'
							),

							// and _meta_slider_slide_scheduled_start < NOW
							array(
								'key' => '_meta_slider_slide_scheduled_start',
								'value' => current_time("Y-m-d H:i:s"),
								'type' => 'datetime',
								'compare' => '<'
							),

							// and _meta_slider_slide_scheduled_end > NOW
							array(
								'key' => '_meta_slider_slide_scheduled_end',
								'value' => current_time("Y-m-d H:i:s"),
								'type' => 'datetime',
								'compare' => '>'
							),
						),


					),

				);
			}
			return $args;
		}

		/**
		 * Adds the admin tab
		 * 
		 * @param  array  $tabs     The registered tabs
		 * @param  object $slide    The current slide
		 * @param  object $slider   The current slider
		 * @param  array  $settings The current slider's settings
		 * 
		 * @return array 
		 */
		public function slide_admin_tab($tabs, $slide, $slider, $settings) {
			$hide_slide = get_post_meta($slide->ID, '_meta_slider_slide_is_hidden', true);
			$admin_title = get_post_meta($slide->ID, '_meta_slider_slide_admin_title', true);
			ob_start();
			$this->slide_admin_tab_controls($slide);
			$content = ob_get_clean();

			$tabs['schedule'] = array(
				'title' => __('Schedule', 'metaslider_schedule_slides'),
				'content' => $content
			);

			if(isset($tabs['general'])) {
				$tabs['general']['content'] .= '<div class="row"><label><input type="checkbox" name="attachment['.$slide->ID.'][hide_slide]" '.($hide_slide == 'yes' ? 'checked="checked"':'') .'> '.__('Hide slide', 'metaslider_schedule_slides').'</label></div>';
				$tabs['general']['content'] .= '<div class="row"><label><input type="texte" name="attachment['.$slide->ID.'][admin_title]" placeholder="'.__('Slide Title (Admin only)', 'metaslider_schedule_slides').'" value="'.$admin_title.'"></label></div>';
			}

			return $tabs;
		}

		/** 
		 * Renders the Schedule tab Controls
		 * 
		 * @param object $post WP_Post object
		 */
		public function slide_admin_tab_controls($post) {
			$is_scheduled = get_post_meta($post->ID, '_meta_slider_slide_is_scheduled', true);
			$schedule_start = get_post_meta($post->ID, '_meta_slider_slide_scheduled_start', true);
			$schedule_end = get_post_meta($post->ID, '_meta_slider_slide_scheduled_end', true);
			?>
			<input type="checkbox" name="attachment[<?php echo $post->ID ?>][schedule]" <?php echo ($is_scheduled == 'yes') ? 'checked="checked"' : '' ?> > <?php _e('Schedule slide', 'metaslider_schedule_slides') ?><br>
			<div class="hide-if-notchecked">
			<?php if($schedule_start) : ?>
			<?php _e('From', 'metaslider_schedule_slides') ?> <?php $this->touch_time($post->ID,'from', true, $schedule_start); ?>
			<?php else : ?>
			<?php _e('From', 'metaslider_schedule_slides') ?> <?php $this->touch_time($post->ID,'from', false); ?>
			<?php endif; ?>
			<?php if($schedule_end) : ?>
			<?php _e('To', 'metaslider_schedule_slides') ?> <?php $this->touch_time($post->ID,'to', true, $schedule_end); ?>
			<?php else : ?>
			<?php _e('To', 'metaslider_schedule_slides') ?> <?php $this->touch_time($post->ID,'to', false); ?>
			<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Renders a line of CSS, symply to hide the date selector if not scheduled.
		 * 
		 * @param int $slider_id Current slideshow ID
		 */
		public function extra_styles($slider_id) {
			$styles = '
			input[type=checkbox]:not(:checked) ~ .hide-if-notchecked {
				display: none;
			}
			.metaslider-ui .metaslider-slides-container .slide {
				max-height: none;
			}
			';
			wp_add_inline_style('metaslider-admin-styles', $styles);
		}

		/**
		 * Saving the new settings
		 * 
		 * @param  object $slide  The current slide
		 * @param  object $slider The current slider
		 * @param  array  $fields Submited fields
		 */
		public function save_settings($slide_id, $slider_id, $fields){

			// Get the old data		
			$is_scheduled = get_post_meta($slide_id, '_meta_slider_slide_is_scheduled', true);
			$schedule_start = get_post_meta($slide_id, '_meta_slider_slide_scheduled_start', true);
			$schedule_end = get_post_meta($slide_id, '_meta_slider_slide_scheduled_end', true);
			$hide_slide = get_post_meta($slide_id, '_meta_slider_slide_is_hidden', true);

			// Update $hide_slide
			update_post_meta($slide_id, '_meta_slider_slide_is_hidden', isset($fields['hide_slide']) ? 'yes' : 'no', $hide_slide);

			// Update $is_scheduled
			update_post_meta($slide_id, '_meta_slider_slide_is_scheduled', isset($fields['schedule']) ? 'yes' : 'no' , $is_scheduled);

			if(isset($fields['schedule'])) {

				// if $is_scheduled update start / endtime
				$start_date = $fields['from']['aa'] .'-'. $fields['from']['mm'] .'-'. $fields['from']['jj'] . ' ' . $fields['from']['hh']. ':'. $fields['from']['mn'] .':'.$fields['from']['ss'];
				$end_date = $fields['to']['aa'] .'-'. $fields['to']['mm'] .'-'. $fields['to']['jj'] . ' ' . $fields['to']['hh']. ':'. $fields['to']['mn'] .':'.$fields['to']['ss'];
				update_post_meta($slide_id, '_meta_slider_slide_scheduled_start', sanitize_text_field($start_date), $schedule_start);
				update_post_meta($slide_id, '_meta_slider_slide_scheduled_end', sanitize_text_field($end_date), $schedule_end);
			}

			// get saved title
			$admin_title = get_post_meta($slide_id, '_meta_slider_slide_admin_title', true);

			// update saved title
			update_post_meta($slide_id, '_meta_slider_slide_admin_title', sanitize_text_field($fields['admin_title']), $admin_title);
		}

		/**
		 * Prints the date time fields (copied and adapted from Wordpress Core function touch_time)
		 * https://developer.wordpress.org/reference/functions/touch_time/
		 * 
		 * @param int    $slide_id   The current slide
		 * @param string $input_name Input name attribute
		 * @param bool   $edit       If editing
		 * @param string $date       Date
		 * @param bool   $multi      If field is used multiple times
		 */ 
		private function touch_time($slide_id, $input_name = '', $edit = true, $date = '0000-00-00 00:00:00', $multi = false) {
			global $wp_locale;
			$post = get_post();
			$tab_index_attribute = '';
			$time_adj = current_time('timestamp');
			$post_date = $date;

			$jj = ($edit) ? mysql2date('d', $post_date, false) : gmdate('d', $time_adj);
			$mm = ($edit) ? mysql2date('m', $post_date, false) : gmdate('m', $time_adj);
			$aa = ($edit) ? mysql2date('Y', $post_date, false) : gmdate('Y', $time_adj);
			$hh = ($edit) ? mysql2date('H', $post_date, false) : gmdate('H', $time_adj);
			$mn = ($edit) ? mysql2date('i', $post_date, false) : gmdate('i', $time_adj);
			$ss = ($edit) ? mysql2date('s', $post_date, false) : gmdate('s', $time_adj);

			$cur_jj = gmdate('d', $time_adj);
			$cur_mm = gmdate('m', $time_adj);
			$cur_aa = gmdate('Y', $time_adj);
			$cur_hh = gmdate('H', $time_adj);
			$cur_mn = gmdate('i', $time_adj);

			$month = '<label><span class="screen-reader-text">' . __('Month') . '</span><select ' . ($multi ? '' : 'name="attachment['.$slide_id.']['.$input_name.'][mm]" ') . 'id="'.$input_name.'_mm"' . $tab_index_attribute . ">\n";
			for ($i = 1; $i < 13; $i = $i +1) {
				$monthnum = zeroise($i, 2);
				$monthtext = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
				$month .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected($monthnum, $mm, false) . '>';
				/* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
				$month .= sprintf(__('%1$s-%2$s'), $monthnum, $monthtext) . "</option>\n";
			}
			$month .= '</select></label>';

			$day = '<label><span class="screen-reader-text">' . __('Day') . '</span><input type="text" ' . ($multi ? '' : 'id="'.$input_name.'_jj" ') . 'name="attachment['.$slide_id.']['.$input_name.'][jj]" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
			$year = '<label><span class="screen-reader-text">' . __('Year') . '</span><input type="text" ' . ($multi ? '' : 'id="'.$input_name.'_aa" ') . 'name="attachment['.$slide_id.']['.$input_name.'][aa]" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
			$hour = '<label><span class="screen-reader-text">' . __('Hour') . '</span><input type="text" ' . ($multi ? '' : 'id="'.$input_name.'_hh" ') . 'name="attachment['.$slide_id.']['.$input_name.'][hh]" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
			$minute = '<label><span class="screen-reader-text">' . __('Minute') . '</span><input type="text" ' . ($multi ? '' : 'id="'.$input_name.'_mn" ') . 'name="attachment['.$slide_id.']['.$input_name.'][mn]" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
			echo '<div class="timestamp-wrap">';

			/** 
			 * taken from WP, '%1$s %2$s, %3$s @ %4$s:%5$s' is part of the default domain.
			 * (used for post publish date)
			 * translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
			printf(__('%1$s %2$s, %3$s @ %4$s:%5$s'), $month, $day, $year, $hour, $minute);

			echo '</div><input type="hidden" id="'.$input_name.'_ss" name="attachment['.$slide_id.']['.$input_name.'][ss]" value="' . $ss . '" />';
		}

		/**
		 * Notice if MS is not installed / active
		 */
		public function meta_slider_not_loaded() {
			?>
			<div class="notice notice-warning ">
				<p><?php _e('MetaSlider Schedule Slides could not be loaded because the plugin <strong>MetaSlider</strong> wasn\'t found.', 'metaslider_schedule_slides') ?></p>
			</div>
			<?php
		}
	}

	add_action('plugins_loaded', array(Meta_Slider_Schedule_Slides::get_instance(), 'setup'), 30);
}
