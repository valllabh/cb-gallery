<?php

require 'cb_callable.php';

class CB_Gallery {
	private $dir;
	private $assets_dir;
	private $assets_url;
	private $token;
	private $file;

	/**
	 * Constructor function.
	 * 
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->version = '3.0';
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->views_dir = trailingslashit( $this->dir ) . 'views';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'cb_gallery';

		$this->addHooks();
	}

	/**
	 * Default options
	 * 
	 * @access private
	 * @return array
	 */
	private function getDefaultOptions(){
		return array(
			'post_type' => array(
				'page'
			),
			'applicable_post_types' => array(

			)
		);
	}

	/**
	 * Get Options
	 * 
	 * @access private
	 * @return array
	 */
	private function getOptions(){
		return get_option($this->token, $this->getDefaultOptions());
	}

	/**
	 * Set Options
	 * 
	 * @access private
	 * @return array
	 */
	private function setOptions($options){
		return update_option($this->token, $options);
	}

	/**
	 * Convert item options to string
	 * 
	 * @access private
	 * @return string
	 */
	private function itemsOptionsToString($options){
		return implode(', ', $options);
	}

	/**
	 * Register various hooks
	 * 
	 * @access private
	 * @return void
	 */
	private function addHooks(){

		//Plugin Activation
		register_activation_hook( $this->file, array(&$this, 'hookActivation') );

		//Plugin Deactivation
		register_deactivation_hook( $this->file, array(&$this, 'hookDeactivation') );

		//WP Init
		add_action( 'init', array(&$this, 'hookInit'), 0 );
		add_action( 'switch_blog', array(&$this, 'hookSwitchBlog'), 0 );

		// The Post
		add_action( 'the_post', array(&$this, 'hookThePost') );

		if ( is_admin() ) {

			add_action( 'admin_menu', array(&$this, 'hookAdminMenu'), 20 );
			add_action( 'admin_print_styles', array(&$this, 'hookAdminPrintStyles'), 10 );

			add_action( 'admin_footer', array(&$this, 'hookAdminFooter') );

			add_action( 'admin_notices', array(&$this, 'hookAdminNotices'));

			// Add Metaboxes to Post Types
			add_action( 'add_meta_boxes', array(&$this, 'hookMetaBoxes') );

			// Post Save
			add_action( 'save_post', array(&$this, 'hookSavePost'), 1, 2 );

			

			// Attachment Fields
			add_filter( 'attachment_fields_to_edit', array(&$this, 'filterAttachmentFieldsEdit'), 10, 2 );
			add_filter( 'attachment_fields_to_save', array(&$this, 'filterAttachmentFieldsSave'), 10, 2 );

			// Taxonomy Hooks
			add_action( 'gallery_types_edit_form', array(&$this, 'hookGalleryTypeFieldsEdit'), 10, 2 );
			add_action( 'gallery_types_add_form_fields', array(&$this, 'hookGalleryTypeFieldsAdd'), 10, 1 );
			add_action( 'created_term', array(&$this, 'hookGalleryTypeFieldsSave'), 10, 3 );
			add_action( 'edit_term', array(&$this, 'hookGalleryTypeFieldsSave'), 10, 3 );

		}
	}

	/**
	 * Gallery Types Fields: Edit
	 * 
	 * @access public
	 * @return void
	 */
	public function hookGalleryTypeFieldsEdit($tag, $taxonomy){
		$options = $this->getOptions();
		$options['all_post_types'] = get_post_types(array(), 'objects');
		extract($options);

		require $this->dir.'/views/gallery-types-options-edit.php';
	}

	/**
	 * Gallery Types Fields: Add
	 * 
	 * @access public
	 * @return void
	 */
	public function hookGalleryTypeFieldsAdd($taxonomy){
		$options = $this->getOptions();
		$options['all_post_types'] = get_post_types(array(), 'objects');
		extract($options);

		require $this->dir.'/views/gallery-types-options-add.php';
	}

	/**
	 * Gallery Types Fields: Save
	 * 
	 * @access public
	 * @return void
	 */
	public function hookGalleryTypeFieldsSave($term_id, $tt_id, $taxonomy){
		$options = $this->getOptions();
		if(isset($_POST['applicable_post_types'])){
			$applicable_post_types = array_map('esc_attr', $_POST['applicable_post_types']);
			$options['applicable_post_types'][$term_id] = $applicable_post_types;
		} elseif(isset($_POST['applicable_post_types_sent'])) {
			$options['applicable_post_types'][$term_id] = array();
		}
		$this->setOptions($options);
	}

	/**
	 * Get Nth attachment to the post
	 * 
	 * @access public
	 * @return $post
	 */
	public function getNthAttachment($gallery_type, $index = 0, $post_id = NULL){
		global $post;

		$post_id = $post_id ? $post_id : (isset($post->ID) ? $post->ID : NULL);

		$prev_post = $post;

		$attachment = $this->getAttachments(
			$gallery_type,
			array(
				'posts_per_page' => 1,
				'offset' => $index
			),
			$post_id
		);
		$return_post = NULL;
		while ($attachment->have_posts()) {
			$attachment->the_post();
			$return_post = $post;
		}

		$post = $prev_post;
		return $return_post;
	}

	/**
	 * Get attachments to the post
	 * 
	 * @access public
	 * @return WP_Query
	 */
	public function getAttachments($gallery_type, $args = array(), $post_id = NULL){
		global $post;

		$options = $this->getOptions();
		$gallery_type = get_term_by('slug', $gallery_type, 'gallery_types');
		$post_id = $post_id ? $post_id : (isset($post->ID) ? $post->ID : NULL);

		if(!$post_id) return new WP_Query();

		if( !( isset($options['applicable_post_types'][$gallery_type->term_id]) && in_array( $post->post_type, $options['applicable_post_types'][$gallery_type->term_id] ) ) ){
			return new WP_Query();
		}

		$post_galleries_token = $this->token.'_galleries';

		$attachments_raw = get_post_meta($post->ID, $post_galleries_token, true);

		$post__in = isset( $attachments_raw[ $gallery_type->term_id ] ) ? $attachments_raw[ $gallery_type->term_id ] : array();

		$args = wp_parse_args($args, array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'inherit'),
			'orderby' => 'post__in',
			'order' => 'ASC',
			'post__in' => $post__in
		));

		$attachments = new WP_Query($args);
		return $attachments;
	}

	/**
	 * Meta Box: Gallery
	 * 
	 *
	 * @access public
	 * @return void
	 */
	public function metaBoxGallery($post, $args){
		wp_enqueue_script($this->token.'-admin-gallery');

		extract($args['args']);
		include $this->views_dir.'/metabox.php';
	}

	/**
	 * Hook: the_post
	 * 
	 * @access public
	 * @return WP_Query
	 */
	public function hookThePost($post){

		if($post->post_type == 'attachment'){
			$prefix = 'cb_gallery_meta_';
			$post->cb_gallery = (object)array(
				'link' => get_post_meta($post->ID, $prefix.'link'),
				'embed_code' => get_post_meta($post->ID, $prefix.'embed_code'),
				'raw_size' => array(
					'thumb' => wp_get_attachment_image_src($post->ID, 'thumbnail'),
					'large' => wp_get_attachment_image_src($post->ID, 'large'),
					'full' => wp_get_attachment_image_src($post->ID, 'full'),
				)
			);
			foreach ($post->cb_gallery->raw_size as $key => $value) {
				$post->cb_gallery->size->{$key} = $value[0];
			}
		}

		//return $post;
	}

	/**
	 * Hook: switch_blog
	 * 
	 * @access public
	 * @return void
	 */
	public function hookSwitchBlog(){
	}

	/**
	 * Hook: init
	 *
	 * @access public
	 * @return void
	 */
	public function hookInit(){
		register_taxonomy(
			'gallery_types',
			'attachment',
			array(
				'labels' => array(
					'name'                => 'Gallery Types',
					'singular_name'       => 'Gallery Type',
					'search_items'        => 'Search gallery types',
					'all_items'           => 'All Gallery Types',
					'parent_item'         => 'Parent Gallery Type',
					'parent_item_colon'   => 'Parent Gallery Type:',
					'edit_item'           => 'Edit Gallery Type',
					'update_item'         => 'Update Gallery Type',
					'add_new_item'        => 'Add New Gallery Type',
					'new_item_name'       => 'New Gallery Type',
					'menu_name'           => 'Gallery Type',
					'not_found'           => 'No gallery types found',
				),
				'rewrite' => array('slug' => 'gallery'),
				'hierarchical' => true,
				'public' => false,
				'show_ui' => true,
				'show_admin_column' => true,
			)
		);

	}

	/**
	 * Filter: attachment_fields_to_save
	 *
	 * @access public
	 * @return void
	 */
	public function filterAttachmentFieldsSave($_post, $attachments){
		$prefix = 'cb_gallery_meta_';

		$fields = array(
			$prefix.'link',
			$prefix.'embed_code'
		);

		$post_id = $_post['post_ID'];

		$var_name = $prefix.'link';
		if(isset($attachments[$var_name])){
			$value = $attachments[$var_name];
			update_post_meta($post_id, $var_name, $value);
		}

		$var_name = $prefix.'embed_code';
		if(isset($attachments[$var_name])){
			$value = $attachments[$var_name];
			update_post_meta($post_id, $var_name, $value);
		}

		return $_post;
	}

	/**
	 * Filter: attachment_fields_to_edit
	 *
	 * @access public
	 * @return void
	 */
	public function filterAttachmentFieldsEdit($form_fields, $post){
		$field_values = array();
		$prefix = 'cb_gallery_meta_';

		// Link Field
		$field_name = $prefix.'link';
		$field_values[$field_name]['raw'] = get_post_meta($post->ID, $field_name, true);
		$field_values[$field_name]['escaped'] = esc_attr($field_values[$field_name]['raw']);
		$form_fields[$field_name] = array(
			'value' => $field_values[$field_name]['raw'],
			'label' => __('Link'),
			'input' => 'html',
			'html' => '<input type="text" class="widefat" name="attachments['.$post->ID.']['.$field_name.']" id="attachments-'.$post->ID.'-'.$field_name.'" value="'.$field_values[$field_name]['escaped'].'"/>',
			'helps' => 'Use absolute link/URL.<br/>Example: <code>http://your-awesome-link/</code>'
		);

		// Embed Code
		$field_name = $prefix.'embed_code';
		$field_values[$field_name]['raw'] = get_post_meta($post->ID, $field_name, true);
		$field_values[$field_name]['escaped'] = ($field_values[$field_name]['raw']);
		$form_fields[$field_name] = array(
			'value' => $field_values[$field_name]['raw'],
			'label' => __('Embed Code'),
			'input' => 'html',
			'html' => '<textarea class="widefat" name="attachments['.$post->ID.']['.$field_name.']" id="attachments-'.$post->ID.'-'.$field_name.'">'.$field_values[$field_name]['escaped'].'</textarea>',
		);

		return $form_fields;
	}

	/**
	 * Hook: admin_footer
	 *
	 * @access public
	 * @return void
	 */
	public function hookAdminFooter(){
		include $this->views_dir.'/gallery-attachment-template.php';
	}

	/**
	 * Hook: save_post
	 *
	 * @access public
	 * @return void
	 */
	public function hookSavePost($post_id, $post = NULL){

		if(empty($post_id) || empty($post)) return;
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if(is_int(wp_is_post_revision($post))) return;
		if(is_int(wp_is_post_autosave($post))) return;
		if(!current_user_can('edit_post', $post_id)) return;

		$options = $this->getOptions();
		if(!in_array($post->post_type, $options['post_type'])) return;

		$data = isset($_POST[$this->token]) ? $_POST[$this->token] : array();
		if(empty($data)) return;

		$attachments = isset($data['a']) ? $data['a'] : array();

		remove_action('save_post', array(&$this, 'hookSavePost'));

		$post_galleries_token = $this->token.'_galleries';

		update_post_meta( $post_id, $post_galleries_token, $attachments );

		add_action('save_post', array(&$this, 'hookSavePost'));
	}

	/**
	 * Hook: add_meta_boxes
	 *
	 * @access public
	 * @return void
	 */
	public function hookMetaBoxes() {
		global $post;

		$post_galleries_token = $this->token.'_galleries';

		$options = $this->getOptions();
		$attachments_raw = get_post_meta($post->ID, $post_galleries_token, true);

		$gallery_types = get_terms('gallery_types', array(
			'hide_empty' => false,
		));


		foreach ($gallery_types as $gallery_type) {

			if( !( isset($options['applicable_post_types'][$gallery_type->term_id]) && in_array( $post->post_type, $options['applicable_post_types'][$gallery_type->term_id] ) ) ){
				continue;
			}

			$post__in = isset( $attachments_raw[ $gallery_type->term_id ] ) ? $attachments_raw[ $gallery_type->term_id ] : array();

			$attachments = array();
			if( ! empty($post__in) ){
				$attachments = get_posts(array(
					'post_type' => 'attachment',
					'posts_per_page' => -1,
					'orderby' => 'post__in',
					'order' => 'ASC',
					'post__in' => $post__in
				));
			}

			add_meta_box($this->token.'_'.$gallery_type->slug,
				$gallery_type->name,
				array(&$this, 'metaBoxGallery'),
				$post->type,
				'normal',
				'high',
				array(
					'id' => $gallery_type->term_id,
					'token' => $this->token,
					'post_type' => $post->type,
					'attachments' => $attachments,
					'gallery_type' => $gallery_type
				)
			);

		}
	}

	/**
	 * Hook: admin_print_styles
	 * 
	 * @access public
	 * @return void
	 */
	public function hookAdminPrintStyles(){
		wp_register_style($this->token.'-admin', $this->assets_url.'css/admin.css', array(), $this->version);
		wp_enqueue_style($this->token.'-admin');

		wp_register_script($this->token.'-admin-gallery', $this->assets_url.'js/gallery.js');
	}

	/**
	 * Hook: admin_menu
	 * 
	 * @access public
	 * @return void
	 */
	public function hookAdminMenu(){
	}

	/**
	 * Hook: register_activation_hook
	 * 
	 * @access public
	 * @return void
	 */
	public function hookActivation(){
		$this->setOptions($this->getOptions());
	}

	/**
	 * Hook: register_deactivation_hook
	 * 
	 * @access public
	 * @return void
	 */
	public function hookDeactivation(){
		delete_option($this->token);
	}

	/**
	 * Hook: admin_notices
	 * 
	 * @access public
	 * @return void
	 */
	public function hookAdminNotices(){
	}

}

?>