<?php
/*
Plugin Name: CB Gallery
Plugin URI: 
Description: Gallery Plugin
Version: 1.0
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Make sure options are deleted on plugin deactivation
function cb_gallery_deactivation() {
	unregister_setting('general', 'cb_gallery');
	delete_option('cb_gallery');
}
register_deactivation_hook(__FILE__, 'cb_gallery_deactivation');


// Add Settings Section in Settings >> General >> Gallery Settings
function register_cb_gallery_settings() {
	register_setting('general', 'cb_gallery');

	add_settings_section('cb_gallery_settings', 'Gallery Settings', 'cb_gallery_settings_text', 'general');

	add_settings_field('cb_gallery_image', 'Gallery type image', 'cb_gallery_type_image_field', 'general', 'cb_gallery_settings');
	add_settings_field('cb_gallery_video', 'Gallery type video', 'cb_gallery_type_video_field', 'general', 'cb_gallery_settings');
}

// Description for setting section
function cb_gallery_settings_text() {
	echo 'Select types of attachments enabled.';
}

// Form Checkbox output for settings
function cb_gallery_type_image_field() {
	$options = get_option('cb_gallery');
	echo '<input type="checkbox" name="cb_gallery[image]" value="1" '.((bool)$options['image'] ? 'checked="checked"' : '').' />';
}

// Form Checkbox output for settings
function cb_gallery_type_video_field() {
	$options = get_option('cb_gallery');
	echo '<input type="checkbox" name="cb_gallery[video]" value="1" '.((bool)$options['video'] ? 'checked="checked"' : '').' />';
}

// Register only admin specific actions
if ( is_admin() ){
	add_action('admin_init', 'register_cb_gallery_settings');
}

// Show notice if there is a dependency required
function cb_gallery_dependency(){
    echo '<div class="error"><p>CB Gallery is depend on plugin <a target="_blank" href="http://www.advancedcustomfields.com/">Advanced Custom Fields 3.5+</a></p></div>';
}

/**
 * Register field groups
 * The register_field_group function accepts 1 array which holds the relevant data to register a field group
 * You may edit the array as you see fit. However, this may result in errors if the array is not compatible with ACF
 * This code must run every time the functions.php file is read
 */
if(function_exists("register_field_group")) {

	$options = get_option('cb_gallery', array('image' => 1, 'video' => 0));
	
	$fields = array();
	
	if((int)$options['image']) {
		$fields[] = array (
			'key' => 'field_images',
			'label' => 'Images',
			'name' => 'images',
			'type' => 'repeater',
			'order_no' => 10,
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => array (
				'status' => 0,
				'rules' => $rules,
				'allorany' => 'all',
			),
			'sub_fields' => array (
				'image' => array (
					'label' => 'Image',
					'name' => 'image',
					'type' => 'image',
					'instructions' => '',
					'column_width' => '',
					'save_format' => 'object',
					'preview_size' => 'thumbnail',
					'order_no' => 0,
					'key' => 'field_image',
				),
			),
			'row_min' => 0,
			'row_limit' => '',
			'layout' => 'table',
			'button_label' => 'Add Image',
		);
	}

	if((int)$options['video']) {
		$fields[] = array (
			'key' => 'field_external_videos',
			'label' => 'External Videos',
			'name' => 'external_videos',
			'type' => 'repeater',
			'order_no' => 0,
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => array (
				'status' => 0,
				'rules' => array (
					array (
						'field' => 'null',
						'operator' => '==',
						'value' => '',
					),
				),
				'allorany' => 'all',
			),
			'sub_fields' => array (
				'video' => array (
					'label' => 'Video',
					'name' => 'video',
					'type' => 'text',
					'instructions' => '',
					'column_width' => '',
					'default_value' => '',
					'formatting' => 'none',
					'order_no' => 0,
					'key' => 'field_video',
				),
				'title' => array (
					'label' => 'Title',
					'name' => 'title',
					'type' => 'text',
					'instructions' => '',
					'column_width' => '',
					'default_value' => '',
					'formatting' => 'html',
					'order_no' => 1,
					'key' => 'field_title',
				),
			),
			'row_min' => 0,
			'row_limit' => '',
			'layout' => 'table',
			'button_label' => 'Add Video',
		);
	}

	register_field_group(array (
		'id' => '50e3bc9e0c849',
		'title' => 'Gallery',
		'fields' => $fields,
		'location' => array (
			'rules' => array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
					'order_no' => 0,
				)
			),
			'allorany' => 'all',
		),
		'options' => array(
			'position' => 'normal',
			'layout' => 'box',
			'hide_on_screen' => array(
			),
		),
		'menu_order' => 0,
	));
} else {
	add_action('admin_notices', 'cb_gallery_dependency');
}

?>