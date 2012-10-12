<?php
/*
Plugin Name: CB Gallery
Plugin URI: 
Description: Gallery Plugin
Version: 1.0
Author: Vallabh Joshi
Author URI: http://vallabhjoshi.com
License: 
*/

define('CB_GALLERY_PATH', __DIR__);


/*
*	Define Post Type
*/

function cb_gallery_create_post_type() {
	register_post_type('cb_gallery',
		array(
			'label' => 'Galleries',
			'description' => '',
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'capability_type' => 'post',
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => 'gallery',
				'with_front' => false
			),
			'query_var' => true,
			'exclude_from_search' => false,
			'supports' => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'author',
				'page-attributes'
			),
			'labels' => array (
				'name' => 'Galleries',
				'singular_name' => 'Gallery',
				'menu_name' => 'Gallery',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Gallery',
				'edit' => 'Edit',
				'edit_item' => 'Edit Gallery',
				'new_item' => 'New Gallery',
				'view' => 'View Gallery',
				'view_item' => 'View Gallery',
				'search_items' => 'Search Galleries',
				'not_found' => 'No Galleries Found',
				'not_found_in_trash' => 'No Galleries found in Trash',
				'parent' => 'Parent Gallery'
			)
		)
	);
}
add_action( 'init', 'cb_gallery_create_post_type');





/*
*	Plugin Settings
*/

// Add Settings Menu
function cb_gallery_settings_menu() {

	add_submenu_page( 
		'edit.php?post_type=cb_gallery',
		'Gallery Settings',
		'Settings',
		'manage_options',
		'settings',
		'cb_gallery_settings'
	);

}
//add_action('admin_menu', 'cb_gallery_settings_menu');

// Settings page
function cb_gallery_settings() {
	register_setting('cb_gallery_settings', 'new_option_name');
	register_setting('cb_gallery_settings', 'some_other_option');
	register_setting('cb_gallery_settings', 'option_etc');

	require CB_GALLERY_PATH.'/settings.php';
	
}

?>