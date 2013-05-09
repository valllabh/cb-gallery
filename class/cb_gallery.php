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
		$this->version = '2.0';
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
		register_activation_hook($this->file, array(&$this, 'hookActivation'));

		//Plugin Deactivation
		register_deactivation_hook($this->file, array(&$this, 'hookDeactivation'));

		//WP Init
		add_action('init', array(&$this, 'hookInit'), 0);
		add_action('switch_blog', array(&$this, 'hookSwitchBlog'), 0);

		if ( is_admin() ) {

			add_action('admin_menu', array(&$this, 'hookAdminMenu'), 20);
			add_action('admin_print_styles', array(&$this, 'hookAdminPrintStyles'), 10);

			add_action('admin_footer', array(&$this, 'hookAdminFooter'));

			add_action('admin_notices', array(&$this, 'hookAdminNotices'));

			// Add Metaboxes to Post Types
			add_action('add_meta_boxes', array(&$this, 'hookMetaBoxes'));

			// Post Save
			add_action('save_post', array(&$this, 'hookSavePost'), 1, 2);

			// Attachment Fields
			add_filter('attachment_fields_to_edit', array(&$this, 'filterAttachmentFieldsEdit'), 10, 2);
			add_filter('attachment_fields_to_save', array(&$this, 'filterAttachmentFieldsSave'), 10, 2);

			// Taxonomy Hooks
			add_action('gallery_types_edit_form', array(&$this, 'hookGalleryTypeFieldsEdit'), 10, 2);
			add_action('gallery_types_add_form_fields', array(&$this, 'hookGalleryTypeFieldsAdd'), 10, 1);
			add_action('created_term', array(&$this, 'hookGalleryTypeFieldsSave'), 10,3);
			add_action('edit_term', array(&$this, 'hookGalleryTypeFieldsSave'), 10,3);

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
	 * Incremental array search
	 * 
	 * @access public
	 * @return array
	 */
	public function arraySearch($needle, $haystack, $strict = false){
		static $result = array();
		if(isset($result[$needle]) && isset($result[$needle]['resluts'])){
			if(isset($result[$needle]['resluts'][$result[$needle]['next_key']])){
				return $result[$needle]['resluts'][$result[$needle]['next_key']++];
			}
		}
		foreach ($haystack as $key => $value) {
			if($needle == $value){
				$result[$needle]['resluts'][] = $key;
			}
		}
		if(isset($result[$needle]) && isset($result[$needle]['resluts']) && isset($result[$needle]['resluts'][0])){
			$result[$needle]['next_key'] = 1;
			return intval($result[$needle]['resluts'][0]);
		} else {
			return false;
		}
	}

	/**
	 * Orders Attachments with custom ordering field
	 * 
	 * @access public
	 * @return array
	 */
	public function orderAttachments($a, $order_var){
		$attachments = $a;

		usort($attachments, array(new CB_Callable(array(&$this, 'orderAttachmentsLogic'), array($order_var)), 'call'));

		return $attachments;
	}

	public function orderAttachmentsLogic($a, $b, $order_var) {
		$a->$order_var = get_post_meta($a->ID, $order_var, true);
		$b->$order_var = get_post_meta($b->ID, $order_var, true);

		if ($a->$order_var == $b->$order_var) {
			return 0;
		}
		return ($a->$order_var < $b->$order_var) ? -1 : 1;
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

		$attachment = $this->getAttachments($gallery_type,
			array(
				'posts_per_page' => 1,
				'offset' => $index
			),
			$post_id
		);
		$return_post = NULL;
		while ($attachment->have_posts()) {
			$attachment->the_post();
			$this->cb_gallery_attachment();
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

		$gallery_type = get_term_by('slug', $gallery_type, 'gallery_types');

		$post_id = $post_id ? $post_id : (isset($post->ID) ? $post->ID : NULL);
		if(!$post_id) return array();

		$args = wp_parse_args($args, array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_status' => 'inherit',
			'post_parent' => $post_id,
			'meta_key' => 'cb_gallery_'.$gallery_type->term_id.'_order',
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
			'tax_query' => array(
				array(
					'taxonomy' => 'gallery_types',
					'terms' => $gallery_type->term_id,
				)
			)
		));

		$attachments = new WP_Query($args);
		return $attachments;
	}

	/**
	 * Sets up the attachment for the current post
	 * 
	 * @access public
	 * @return WP_Query
	 */
	public function cb_gallery_attachment(){
		global $post;
		if($post->post_type == 'attachment'){
			$prefix = 'cb_gallery_meta_';
			$post->cb_gallery = (object)array(
				'link' => get_post_meta($post->ID, $prefix.'link'),
				'embed_code' => get_post_meta($post->ID, $prefix.'embed_code'),
				'thumb_raw' => wp_get_attachment_image_src($post->ID, 'thumbnail'),
				'large_raw' => wp_get_attachment_image_src($post->ID, 'large'),
			);
			$post->cb_gallery->thumb = $post->cb_gallery->thumb_raw[0];
			$post->cb_gallery->large = $post->cb_gallery->large_raw[0];
		}
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

		$attachments_raw = isset($data['a']) ? $data['a'] : array();
		$attachment_terms_raw = isset($data['at']) ? $data['at'] : array();
		$attachment_terms = get_terms('gallery_types', array('hide_empty' => false));
		$attachments = get_posts(array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_parent' => $post->ID,
		));

		remove_action('save_post', array(&$this, 'hookSavePost'));
		foreach ($attachments as $attachment) {
			$terms_raw = isset($attachment_terms_raw[$attachment->ID]) ? $attachment_terms_raw[$attachment->ID] : array();
			$terms_raw = array_map('intval', $terms_raw);
			foreach ($attachment_terms as $term) {
				$meta_key = $this->token.'_'.$term->term_id.'_order';
				$order = $this->arraySearch($attachment->ID, $attachments_raw);

				if(in_array($term->term_id, $terms_raw) && $order !== false){
					update_post_meta($attachment->ID, $meta_key, $order);
				} else {
					delete_post_meta($attachment->ID, $meta_key);
				}
			}
			wp_set_object_terms($attachment->ID, $terms_raw, 'gallery_types', false);
		}

		add_action('save_post', array(&$this, 'hookSavePost'));
	}

	/**
	 * Hook: add_meta_boxes
	 *
	 * @access public
	 * @return void
	 */
	public function hookMetaBoxes(){
		global $post;
		$options = $this->getOptions();
		$gallery_types = get_terms('gallery_types', array('hide_empty' => false));
		$terms = array();
		$attachments = array();
		foreach ($gallery_types as $term) {
			$terms[] = $term->term_id;
		}

		$attachments_raw = get_posts(array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_parent' => $post->ID,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_status' => 'inherit',
			'tax_query' => array(
				array(
					'taxonomy' => 'gallery_types',
					'terms' => $terms,
				)
			)
		));

		foreach ($attachments_raw as &$a) {
			$a->terms = wp_get_post_terms($a->ID, 'gallery_types');
			foreach ($a->terms as $t) {
				$attachments[$t->term_id][] = $a;
			}
		}

		foreach ($attachments as $term => &$a) {
			$order_var = $this->token.'_'.$term.'_order';
			$a = $this->orderAttachments($a, $order_var);
		}

		foreach ($gallery_types as $gallery_type) {
			if(!isset($options['applicable_post_types'][$gallery_type->term_id])){
				continue;
			}
			foreach ($options['applicable_post_types'][$gallery_type->term_id] as $key => $post_type) {
				add_meta_box($this->token.'_'.$gallery_type->slug,
					$gallery_type->name,
					array(&$this, 'metaBoxGallery'),
					$post_type,
					'normal',
					'high',
					array(
						'id' => $key,
						'token' => $this->token,
						'post_type' => $post_type,
						'attachments' => isset($attachments[$gallery_type->term_id]) ? $attachments[$gallery_type->term_id] : array(),
						'gallery_type' => $gallery_type
					)
				);
			}
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