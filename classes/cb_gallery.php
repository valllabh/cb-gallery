<?php

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
		$this->version = '4.3';
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->views_dir = trailingslashit( $this->dir ) . 'views';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'cb_gallery';

		$this->taxonomy_metadata = new Taxonomy_Metadata( $this->file );

		$this->addHooks();
	}

	/**
	 * Default options
	 *
	 * @access private
	 * @return array
	 */
	private function getDefaultOptions() {
		return array(
			'applicable_post_types' => array(),
			'applicable_taxonomies' => array(),
			'applicable_user_roles' => array()
		 );
	}

	/**
	 * Get Options
	 *
	 * @access private
	 * @return array
	 */
	private function getOptions() {
		return get_option( $this->token, $this->getDefaultOptions() );
	}

	/**
	 * Set Options
	 *
	 * @access private
	 * @return array
	 */
	private function setOptions( $options ) {
		return update_option( $this->token, $options );
	}

	/**
	 * Convert item options to string
	 *
	 * @access private
	 * @return string
	 */
	private function itemsOptionsToString( $options ) {
		return implode( ', ', $options );
	}

	public function getGalleryMetaFieldKey() {
		return $this->token.'_galleries';
	}

	/**
	 * Get Nth attachment to the post
	 *
	 * @access public
	 * @return $post
	 */
	public function getNthAttachment( $gallery_type, $object_type = 'post', $index = 0, $object_id = NULL ) {
		global $post;

		$object_id = $object_id ? array( $object_id ) : NULL;

		$prev_post = $post;

		$attachment = $this->getAttachments(
			$gallery_type,
			$object_type,
			array(
				'posts_per_page' => 1,
				'offset' => $index
			 ),
			$object_id
		 );
		$return_post = NULL;
		while ( $attachment->have_posts() ) {
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
	public function getAttachments( $gallery_type, $object_type = 'post', $args = array(), $object_ids = array() ) {
		global $post;

		$options = $this->getOptions();
		if( is_string( $gallery_type ) ) {
			$gallery_type = get_term_by( 'slug', $gallery_type, 'cb_gallery_types' );
		}

		if( !isset( $gallery_type->term_id ) ){
			return new WP_Query();
		}

		if( !is_array( $object_ids ) ) {
			$object_ids = array( $object_ids );
		}

		$quried_object = get_queried_object();
		$attachments_raw = array();

		switch ( $object_type ) {
			case 'post':
				$object_ids = !empty( $object_ids ) ? $object_ids : ( isset( $post->ID ) ? array( $post->ID ) : array() );
				foreach ( $object_ids as $object_id ) {
					$gallery_meta = get_post_meta( $object_id, $this->getGalleryMetaFieldKey(), true );
					$attachments = ( is_array( $gallery_meta ) && isset( $gallery_meta['a'] ) ) ? $gallery_meta['a'] : array();
					$attachments_raw = array_merge_recursive_numbered( $attachments_raw, $attachments );
				}
				break;
			case 'tag':
			case 'term':
			case 'category':
			case 'taxonomy':
				$object_ids = !empty( $object_ids ) ? $object_ids : ( isset( $quried_object->term_id ) ? array( $quried_object->term_id ) : array() );
				foreach ( $object_ids as $object_id ) {
					$gallery_meta = get_term_meta( $object_id, $this->getGalleryMetaFieldKey(), true );
					$attachments = ( is_array( $gallery_meta ) && isset( $gallery_meta['a'] ) ) ? $gallery_meta['a'] : array();
					$attachments_raw = array_merge_recursive_numbered( $attachments_raw, $attachments );
				}
				break;
			case 'user':
				if( empty( $object_ids ) ){
					$current_user = wp_get_current_user();
					$object_ids = array( $current_user->ID );
				}
				foreach ( $object_ids as $object_id ) {
					$gallery_meta = get_user_meta( $object_id, $this->getGalleryMetaFieldKey(), true );
					$attachments = ( is_array( $gallery_meta ) && isset( $gallery_meta['a'] ) ) ? $gallery_meta['a'] : array();
					$attachments_raw = array_merge_recursive_numbered( $attachments_raw, $attachments );
				}
				break;
		}

		$post__in = isset( $attachments_raw[ $gallery_type->term_id ] ) ? $attachments_raw[ $gallery_type->term_id ] : array( 0 );

		$args = wp_parse_args( $args, array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_status' => array( 'publish', 'inherit' ),
			'orderby' => 'post__in',
			'order' => 'ASC',
			'post__in' => $post__in
		 ) );

		$attachments = new WP_Query( $args );
		return $attachments;
	}

	/**
	 * Meta Box: Gallery
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function metaBoxGallery( $post, $args ) {
		wp_enqueue_script( $this->token.'-admin-gallery' );

		extract( $args['args'] );
		include $this->views_dir.'/metabox.php';
	}

	/**
	 * Adds Gallery Meta Box
	 *
	 *
	 * @access public
	 * @return void
	 */
	public	function addGalleryMetaBox( $gallery_type, $_post = NULL ) {
		global $post;


		if( $_post ) {
			$_post = $post;
		}

		$gallery_meta = get_post_meta( $_post->ID, $this->getGalleryMetaFieldKey(), true );
		$attachments_raw = ( is_array( $gallery_meta ) && isset( $gallery_meta['a'] ) ) ? $gallery_meta['a'] : array();

		$post__in = isset( $attachments_raw[ $gallery_type->term_id ] ) ? $attachments_raw[ $gallery_type->term_id ] : array();

		$attachments = array();
		if( ! empty( $post__in ) ) {
			$attachments = get_posts( array(
				'post_type' => 'attachment',
				'posts_per_page' => -1,
				'orderby' => 'post__in',
				'order' => 'ASC',
				'post__in' => $post__in
			 ) );
		}

		add_meta_box( $this->token.'_'.$gallery_type->slug.'_'.$gallery_type->term_id,
			$gallery_type->name,
			array( &$this, 'metaBoxGallery' ),
			$_post->type,
			'normal',
			'high',
			array(
				'id' => $gallery_type->term_id,
				'token' => $this->token,
				'gallery_meta' => $gallery_meta,
				'attachments' => $attachments,
				'gallery_type' => $gallery_type
			 )
		 );

	}

	/**
	 * Register various hooks
	 *
	 * @access private
	 * @return void
	 */
	private function addHooks() {

		//Plugin Activation
		register_activation_hook( $this->file, array( &$this, 'hookActivation' ) );

		//Plugin Deactivation
		register_deactivation_hook( $this->file, array( &$this, 'hookDeactivation' ) );

		//WP Init
		add_action( 'init', array( &$this, 'hookInit' ), 0 );
		add_action( 'switch_blog', array( &$this, 'hookSwitchBlog' ), 0 );

		// The Post
		add_action( 'the_post', array( &$this, 'hookThePost' ) );

		if ( is_admin() ) {

			add_action( 'admin_menu', array( &$this, 'hookAdminMenu' ), 20 );
			add_action( 'admin_print_styles', array( &$this, 'hookAdminPrintStyles' ), 10 );

			add_action( 'admin_footer', array( &$this, 'hookAdminFooter' ) );

			add_action( 'admin_notices', array( &$this, 'hookAdminNotices' ) );



			// Attachment Fields
			add_filter( 'attachment_fields_to_edit', array( &$this, 'filterAttachmentFieldsEdit' ), 10, 2 );
			add_filter( 'attachment_fields_to_save', array( &$this, 'filterAttachmentFieldsSave' ), 10, 2 );


			// Gallery Type
			add_action( 'cb_gallery_types_edit_form', array( &$this, 'hookGalleryTypeFieldsEdit' ), 10, 2 );
			add_action( 'cb_gallery_types_add_form_fields', array( &$this, 'hookGalleryTypeFieldsAdd' ), 10, 1 );
			add_action( 'created_cb_gallery_types', array( &$this, 'hookGalleryTypeFieldsSave' ), 10, 3 );
			add_action( 'edited_cb_gallery_types', array( &$this, 'hookGalleryTypeFieldsSave' ), 10, 3 );




			/* Post Galleries */
			add_action( 'save_post', array( &$this, 'hookSavePost' ), 1, 2 );
			// Add Metaboxes to Post Types
			add_action( 'add_meta_boxes', array( &$this, 'hookMetaBoxes' ) );

			/* Taxonomy Galleries */
			$options = $this->getOptions();
			$applicable_taxonomies = array();
			foreach ( $options['applicable_taxonomies'] as $taxonomies ) {
				foreach ( $taxonomies as $taxonomy ) {
					$applicable_taxonomies[$taxonomy] = $taxonomy;
				}
			}

			foreach ( $applicable_taxonomies as $taxonomy ) {
				add_action( $taxonomy.'_edit_form', array( &$this, 'hookCategoryTagsGalleryEdit' ), 10, 2 );
				add_action( $taxonomy.'_add_form_fields', array( &$this, 'hookCategoryTagsGalleryAdd' ), 10, 1 );
				add_action( 'created_'.$taxonomy, array( &$this, 'hookCategoryTagsGallerySave' ), 10, 2 );
				add_action( 'edited_'.$taxonomy, array( &$this, 'hookCategoryTagsGallerySave' ), 10, 2 );
			}

			/* User Galleries */
			add_action( 'personal_options', array( &$this, 'hookUserProfile' ), 0 );

			add_action( 'personal_options_update', array( &$this, 'hookUserProfileSave' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'hookUserProfileSave' ) );

		}
	}

	/**
	 * Hook: personal_options_update|edit_user_profile_update
	 *
	 * @access public
	 * @return void
	 */
	public function hookUserProfileSave( $user_id ) {
		if( empty( $user_id ) ) return;

		$options = $this->getOptions();

		$data = isset( $_POST[$this->token] ) ? $_POST[$this->token] : array();
		if( empty( $data ) ) return;

		$gallery_meta = get_user_meta( $user_id, $this->getGalleryMetaFieldKey(), true );
		$gallery_meta['a'] = isset( $data['a'] ) ? $data['a'] : array();

		update_user_meta( $user_id, $this->getGalleryMetaFieldKey(), $gallery_meta );
	}

	/**
	 * Hook: personal_options
	 *
	 * @access public
	 * @return void
	 */
	public function hookUserProfile( $user ) {
		wp_enqueue_script( $this->token.'-admin-gallery' );

		$options = $this->getOptions();

		$gallery_meta = get_user_meta( $user->ID, $this->getGalleryMetaFieldKey(), true );
		$attachments_raw = ( is_array( $gallery_meta ) && isset( $gallery_meta['a'] ) ) ? $gallery_meta['a'] : array();

		foreach ( $options['applicable_user_roles'] as $gallery_type => $roles ) {
			if( count( array_intersect( $user->roles, $roles ) ) ) {
				$gallery_type = get_term_by( 'id', $gallery_type, 'cb_gallery_types' );

				if( ! isset( $gallery_type->term_id ) ) { continue; }

				$attachments = array();

				$post__in = isset( $attachments_raw[ $gallery_type->term_id ] ) ? $attachments_raw[ $gallery_type->term_id ] : array( 0 );

				if( !empty( $post__in ) ) {
					$attachments = get_posts( array(
						'post_type' => 'attachment',
						'posts_per_page' => -1,
						'orderby' => 'post__in',
						'order' => 'ASC',
						'post__in' => $post__in
					 ) );
				}

				$token = $this->token;

				$args = array(
					'id' => $gallery_type->term_id,
					'gallery_type' => $gallery_type,
					'token' => $this->token,
					'attachments' => $attachments
				 );
				extract( $args );

				require $this->views_dir.'/taxonomy-options-edit.php';
			}
		}
	}

	/**
	 * Hook: created_{taxonomy} + edited_{taxonomy}
	 *
	 * @access public
	 * @return void
	 */
	public function hookCategoryTagsGallerySave( $term_id, $tt_id ) {

		if( empty( $term_id ) ) return;

		$options = $this->getOptions();

		$data = isset( $_POST[$this->token] ) ? $_POST[$this->token] : array();
		if( empty( $data ) ) return;

		$gallery_meta = get_term_meta( $object_id, $this->getGalleryMetaFieldKey(), true );
		$gallery_meta['a'] = isset( $data['a'] ) ? $data['a'] : array();

		update_term_meta( $term_id, $this->getGalleryMetaFieldKey(), $gallery_meta );
	}

	/**
	 * Hook: {taxonomy}_add_form_fields
	 *
	 * @access public
	 * @return void
	 */
	public function hookCategoryTagsGalleryAdd( $taxonomy ) {
		wp_enqueue_script( $this->token.'-admin-gallery' );

		$options = $this->getOptions();

		foreach ( $options['applicable_taxonomies'] as $gallery_type => $applicable_taxonomies ) {
			if( in_array( $taxonomy, $applicable_taxonomies ) ) {

				$args = array(
					'id' => 'new',
					'token' => $this->token,
					'attachments' => array(),
					'gallery_type' => get_term_by( 'id', $gallery_type, 'cb_gallery_types' )
				 );

				extract( $args );

				if( isset( $gallery_type->term_id ) ) {
					require $this->views_dir.'/taxonomy-options-add.php';
				}
			}
		}
	}

	/**
	 * Hook: {taxonomy}_edit_form
	 *
	 * @access public
	 * @return void
	 */
	public function hookCategoryTagsGalleryEdit( $term ) {
		wp_enqueue_script( $this->token.'-admin-gallery' );

		$options = $this->getOptions();
		$taxonomy = $term->taxonomy;

		$gallery_meta = get_term_meta( $term->term_id, $this->getGalleryMetaFieldKey(), true );
		$attachments_raw = ( is_array( $gallery_meta ) && isset( $gallery_meta['a'] ) ) ? $gallery_meta['a'] : array();

		foreach ( $options['applicable_taxonomies'] as $gallery_type => $applicable_taxonomies ) {
			if( in_array( $taxonomy, $applicable_taxonomies ) ) {

				$gallery_type = get_term_by( 'id', $gallery_type, 'cb_gallery_types' );

				if( ! isset( $gallery_type->term_id ) ) { continue; }

				$attachments = array();

				$post__in = isset( $attachments_raw[ $gallery_type->term_id ] ) ? $attachments_raw[ $gallery_type->term_id ] : array( 0 );

				if( !empty( $post__in ) ) {
					$attachments = get_posts( array(
						'post_type' => 'attachment',
						'posts_per_page' => -1,
						'orderby' => 'post__in',
						'order' => 'ASC',
						'post__in' => $post__in
					 ) );
				}

				$token = $this->token;

				$args = array(
					'id' => $gallery_type->term_id,
					'gallery_type' => $gallery_type,
					'token' => $this->token,
					'attachments' => $attachments
				 );
				extract( $args );

				require $this->views_dir.'/taxonomy-options-edit.php';

			}
		}
	}

	/**
	 * Hook: cb_gallery_types_edit_form
	 * Gallery Types Fields: Edit
	 *
	 * @access public
	 * @return void
	 */
	public function hookGalleryTypeFieldsEdit( $tag, $taxonomy ) {
		global $wp_roles;

		$options = $this->getOptions();
		$options['all_post_types'] = get_post_types( array(), 'objects' );
		$options['all_taxonomies'] = get_taxonomies( array( 'public' => true ), 'objects' );
		$options['all_user_roles'] = $wp_roles->get_names();

		extract( $options );

		foreach ( $all_taxonomies as &$all_taxonomy ) {
			$all_taxonomy->post_types = array();
			foreach ( $all_taxonomy->object_type as $key => $value ) {
				if( isset( $all_post_types[ $value ] ) ) {
					$all_taxonomy->post_types[] = $all_post_types[ $value ]->label;
				}
			}
		}

		require $this->dir.'/views/gallery-types-options-edit.php';
	}

	/**
	 * Hook: cb_gallery_types_add_form_fields
	 * Gallery Types Fields: Add
	 *
	 * @access public
	 * @return void
	 */
	public function hookGalleryTypeFieldsAdd( $taxonomy ) {
		global $wp_roles;

		$options = $this->getOptions();
		$options['all_post_types'] = get_post_types( array(), 'objects' );
		$options['all_taxonomies'] = get_taxonomies( array( 'public' => true ), 'objects' );
		$options['all_user_roles'] = $wp_roles->get_names();

		extract( $options );

		foreach ( $all_taxonomies as &$all_taxonomy ) {
			$all_taxonomy->post_types = array();
			foreach ( $all_taxonomy->object_type as $key => $value ) {
				if( isset( $all_post_types[ $value ] ) ) {
					$all_taxonomy->post_types[] = $all_post_types[ $value ]->label;
				}
			}
		}

		require $this->dir.'/views/gallery-types-options-add.php';
	}

	/**
	 * Hook: created_term
	 * Gallery Types Fields: Save
	 *
	 * @access public
	 * @return void
	 */
	public function hookGalleryTypeFieldsSave( $term_id, $tt_id, $taxonomy = NULL ) {
		$options = $this->getOptions();

		$gallery_types = get_terms( 'cb_gallery_types', array(
			'hide_empty' => false,
			'fields' => 'ids'
		 ) );

		// Post
		if( isset( $_POST['applicable_post_types'] ) ) {
			$applicable_post_types = array_map( 'esc_attr', $_POST['applicable_post_types'] );
			$options['applicable_post_types'][$term_id] = $applicable_post_types;
		} elseif( isset( $_POST['applicable_post_types_sent'] ) ) {
			$options['applicable_post_types'][$term_id] = array();
		}

		// Taxonomy
		if( isset( $_POST['applicable_taxonomies'] ) ) {
			$applicable_taxonomies = array_map( 'esc_attr', $_POST['applicable_taxonomies'] );
			$options['applicable_taxonomies'][$term_id] = $applicable_taxonomies;
		} elseif( isset( $_POST['applicable_taxonomies_sent'] ) ) {
			$options['applicable_taxonomies'][$term_id] = array();
		}

		// User
		if( isset( $_POST['applicable_user_roles'] ) ) {
			$applicable_user_roles = array_map( 'esc_attr', $_POST['applicable_user_roles'] );
			$options['applicable_user_roles'][$term_id] = $applicable_user_roles;
		} elseif( isset( $_POST['applicable_user_roles_sent'] ) ) {
			$options['applicable_user_roles'][$term_id] = array();
		}

		foreach ( array( 'applicable_post_types', 'applicable_taxonomies', 'applicable_user_roles' ) as $option ) {
			foreach ( $options[ $option ] as $term_id => $value ) {
				if( ! in_array( $term_id, $gallery_types ) || empty( $value ) ) {
					unset( $options[ $option ][ $term_id ] );
				}
			}
		}

		$this->setOptions( $options );
	}

	/**
	 * Hook: the_post
	 *
	 * @access public
	 * @return WP_Query
	 */
	public function hookThePost( $post ) {
		global $_wp_additional_image_sizes;
		$sizes = $_wp_additional_image_sizes;

		$sizes['large'] = array();
		$sizes['full'] = array();
		$sizes['thumbnail'] = array();

		if( $post->post_type == 'attachment' ) {
			$prefix = 'cb_gallery_meta_';
			$post->cb_gallery = ( object ) array(
				'link' => get_post_meta( $post->ID, $prefix.'link' ),
				'embed_code' => get_post_meta( $post->ID, $prefix.'embed_code' )
			 );
			$post->cb_gallery->size = new stdClass();
			foreach ( $sizes as $raw_key => $value ) {
				$key = preg_replace( '[-]', '_', $raw_key );
				$img = wp_get_attachment_image_src( $post->ID, $raw_key );
				$post->cb_gallery->size->{$key} = ( object ) array(
					'src' => $img[0],
					'width' => $img[1],
					'height' => $img[2]
				 );
			}
		}

		return $post;
	}

	/**
	 * Hook: switch_blog
	 *
	 * @access public
	 * @return void
	 */
	public function hookSwitchBlog() {
	}

	/**
	 * Hook: init
	 *
	 * @access public
	 * @return void
	 */
	public function hookInit() {
		register_taxonomy(
			'cb_gallery_types',
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
				'rewrite' => array( 'slug' => 'gallery' ),
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
	public function filterAttachmentFieldsSave( $_post, $attachments ) {
		$prefix = 'cb_gallery_meta_';

		$fields = array(
			$prefix.'link',
			$prefix.'embed_code'
		 );

		$post_id = $_post['post_ID'];

		$var_name = $prefix.'link';
		if( isset( $attachments[$var_name] ) ) {
			$value = $attachments[$var_name];
			update_post_meta( $post_id, $var_name, $value );
		}

		$var_name = $prefix.'embed_code';
		if( isset( $attachments[$var_name] ) ) {
			$value = $attachments[$var_name];
			update_post_meta( $post_id, $var_name, $value );
		}

		return $_post;
	}

	/**
	 * Filter: attachment_fields_to_edit
	 *
	 * @access public
	 * @return void
	 */
	public function filterAttachmentFieldsEdit( $form_fields, $post ) {
		$field_values = array();
		$prefix = 'cb_gallery_meta_';

		// Link Field
		$field_name = $prefix.'link';
		$field_values[$field_name]['raw'] = get_post_meta( $post->ID, $field_name, true );
		$field_values[$field_name]['escaped'] = esc_attr( $field_values[$field_name]['raw'] );
		$form_fields[$field_name] = array(
			'value' => $field_values[$field_name]['raw'],
			'label' => __( 'Link' ),
			'input' => 'html',
			'html' => '<input type="text" class="widefat" name="attachments['.$post->ID.']['.$field_name.']" id="attachments-'.$post->ID.'-'.$field_name.'" value="'.$field_values[$field_name]['escaped'].'"/>',
			'helps' => 'Use absolute link/URL.<br/>Example: <code>http://your-awesome-link/</code>'
		 );

		// Embed Code
		$field_name = $prefix.'embed_code';
		$field_values[$field_name]['raw'] = get_post_meta( $post->ID, $field_name, true );
		$field_values[$field_name]['escaped'] = ( $field_values[$field_name]['raw'] );
		$form_fields[$field_name] = array(
			'value' => $field_values[$field_name]['raw'],
			'label' => __( 'Embed Code' ),
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
	public function hookAdminFooter() {
		include $this->views_dir.'/gallery-attachment-template.php';
	}

	/**
	 * Hook: save_post
	 *
	 * @access public
	 * @return void
	 */
	public function hookSavePost( $post_id, $post = NULL ) {

		if( empty( $post_id ) || empty( $post ) ) return;
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if( is_int( wp_is_post_revision( $post ) ) ) return;
		if( is_int( wp_is_post_autosave( $post ) ) ) return;
		if( !current_user_can( 'edit_post', $post_id ) ) return;

		//$options = $this->getOptions();
		//if( !in_array( $post->post_type, $options['post_type'] ) ) return;

		$data = isset( $_POST[$this->token] ) ? $_POST[$this->token] : array();
		if( empty( $data ) ) return;

		$gallery_meta = get_post_meta( $post_id, $this->getGalleryMetaFieldKey(), true );

		$gallery_meta['a'] = isset( $data['a'] ) ? $data['a'] : array();

		remove_action( 'save_post', array( &$this, 'hookSavePost' ) );

		update_post_meta( $post_id, $this->getGalleryMetaFieldKey(), $gallery_meta );

		add_action( 'save_post', array( &$this, 'hookSavePost' ) );
	}

	/**
	 * Hook: add_meta_boxes
	 *
	 * @access public
	 * @return void
	 */
	public function hookMetaBoxes() {
		global $post;

		$gallery_types = get_terms( 'cb_gallery_types', array(
			'hide_empty' => false,
		 ) );

		$options = $this->getOptions();

		foreach ( $gallery_types as $gallery_type ) {
			if( !( isset( $options['applicable_post_types'][$gallery_type->term_id] ) && in_array( $post->post_type, $options['applicable_post_types'][$gallery_type->term_id] ) ) ) {
				continue;
			}
			$this->addGalleryMetaBox( $gallery_type, $post );
		}

	}

	/**
	 * Hook: admin_print_styles
	 *
	 * @access public
	 * @return void
	 */
	public function hookAdminPrintStyles() {
		wp_register_style( $this->token.'-admin', $this->assets_url.'css/admin.css', array(), $this->version );
		wp_enqueue_style( $this->token.'-admin' );
		wp_enqueue_media();
		wp_register_script( $this->token.'-admin-gallery', $this->assets_url.'js/gallery.js', array( 'jquery', 'jquery-ui-sortable' ) );
	}

	/**
	 * Hook: admin_menu
	 *
	 * @access public
	 * @return void
	 */
	public function hookAdminMenu() {
	}

	/**
	 * Hook: register_activation_hook
	 *
	 * @access public
	 * @return void
	 */
	public function hookActivation() {
		$this->setOptions( $this->getOptions() );
	}

	/**
	 * Hook: register_deactivation_hook
	 *
	 * @access public
	 * @return void
	 */
	public function hookDeactivation() {
		delete_option( $this->token );
	}

	/**
	 * Hook: admin_notices
	 *
	 * @access public
	 * @return void
	 */
	public function hookAdminNotices() {
	}

}

?>