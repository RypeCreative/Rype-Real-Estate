<?php
// Exit if accessed directly
if (!defined( 'ABSPATH')) { exit; }

/**
 *	PropertyShift_Properties class
 *
 */
class PropertyShift_Properties {

	/************************************************************************/
	// Initialize
	/************************************************************************/

	public function __construct() {
		// Load admin object & settings
		$this->admin_obj = new PropertyShift_Admin();
        $this->global_settings = $this->admin_obj->load_settings();;
	}

	/**
	 *	Init
	 */
	public function init() {
		add_action('init', array( $this, 'rewrite_rules' ));
		add_action( 'ns_basics_page_settings_init_filter', array( $this, 'add_page_settings' ));
		$this->add_image_sizes();
		add_action( 'init', array( $this, 'add_custom_post_type' ));
		add_action( 'init', array( $this, 'property_type_init' ));
		add_action( 'init', array( $this, 'property_status_init' ));
		add_action( 'init', array( $this, 'property_location_init' ));
		add_action( 'init', array( $this, 'property_amenities_init' ));
		add_filter( 'manage_edit-ps-property_columns', array( $this, 'add_properties_columns' ));
		add_action( 'manage_ps-property_posts_custom_column', array( $this, 'manage_properties_columns' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box'));
		add_action( 'save_post', array( $this, 'save_meta_box'));
		add_filter( 'ns_basics_page_settings_post_types', array( $this, 'add_page_settings_meta_box'), 10, 3 );
		add_action( 'widgets_init', array( $this, 'properties_sidebar_init'));

		//add property type tax fields
		add_action('property_type_edit_form_fields', array( $this, 'add_tax_fields'), 10, 2);
		add_action('edited_property_type', array( $this, 'save_tax_fields'), 10, 2);
		add_action('property_type_add_form_fields', array( $this, 'add_tax_fields'), 10, 2 );  
		add_action('created_property_type', array( $this, 'save_tax_fields'), 10, 2);

		//add property status tax fields
		add_action('property_status_edit_form_fields', array( $this, 'add_tax_fields'), 10, 2);
		add_action('edited_property_status', array( $this, 'save_tax_fields'), 10, 2);
		add_action('property_status_add_form_fields', array( $this, 'add_tax_fields'), 10, 2 );  
		add_action('created_property_status', array( $this, 'save_tax_fields'), 10, 2);
		add_action( 'property_status_edit_form_fields', array( $this, 'add_tax_price_range_field'), 10, 2);
		add_action('property_status_add_form_fields', array( $this, 'add_tax_price_range_field'), 10, 2 );

		//add property location tax fields
		add_action('property_location_edit_form_fields', array( $this, 'add_tax_fields'), 10, 2);
		add_action('edited_property_location', array( $this, 'save_tax_fields'), 10, 2);
		add_action('property_location_add_form_fields', array( $this, 'add_tax_fields'), 10, 2 );  
		add_action('created_property_location', array( $this, 'save_tax_fields'), 10, 2);

		//front-end template hooks
		add_filter( 'ns_core_after_top_bar_member_menu', array( $this, 'add_topbar_links'));
		add_action('propertyshift_property_actions', array($this, 'add_property_share'));
		add_action('propertyshift_property_actions', array($this, 'add_property_favoriting'));
	}

	/**
	 *	Add Image Sizes
	 */
	public function add_image_sizes() {
		add_image_size( 'property-thumbnail', 800, 600, array( 'center', 'center' ) );
	}

	/**
	 *	Rewrite Rules
	 */
	public function rewrite_rules() {
		add_rewrite_rule('^properties/page/([0-9]+)','index.php?pagename=properties&paged=$matches[1]', 'top');
	}

	/************************************************************************/
	// Properties Custom Post Type
	/************************************************************************/

	/**
	 *	Add custom post type
	 */
	public function add_custom_post_type() {
		$properties_slug = $this->global_settings['ps_property_detail_slug'];
	    register_post_type( 'ps-property',
	        array(
	            'labels' => array(
	                'name' => __( 'Properties', 'propertyshift' ),
	                'singular_name' => __( 'Property', 'propertyshift' ),
	                'add_new_item' => __( 'Add New Property', 'propertyshift' ),
	                'search_items' => __( 'Search Properties', 'propertyshift' ),
	                'edit_item' => __( 'Edit Property', 'propertyshift' ),
	            ),
	        'public' => true,
	        'capability_type' => 'ps-property',
	        'capabilities' => array(
			    'edit_post'          => 'edit_ps-property',
			    'read_post'          => 'read_ps-property',
			    'read_posts'         => 'read_ps-propertys',
			    'delete_post'        => 'delete_ps-property',
			    'delete_posts'       => 'delete_ps-propertys',
			    'edit_posts'         => 'edit_ps-propertys',
			    'edit_others_posts'  => 'edit_others_ps-propertys',
			    'publish_posts'      => 'publish_ps-propertys',
			    'read_private_posts' => 'read_private_ps-propertys',
			    'create_posts'       => 'create_ps-propertys',
			  ),
	        'show_in_menu' => true,
	        'menu_position' => 26,
	        'menu_icon' => 'dashicons-admin-home',
	        'has_archive' => false,
	        'supports' => array('title', 'editor', 'revisions', 'thumbnail', 'page_attributes'),
	        'rewrite' => array('slug' => $properties_slug),
	        )
	    );
	}

	/**
	 *	Register meta box
	 */
	public function register_meta_box() {
		add_meta_box( 'property-details-meta-box', 'Property Details', array($this, 'output_meta_box'), 'ps-property', 'normal', 'high' );
	}

	/**
	 *	Load property settings
	 *
	 * @param int $post_id
	 */
	public function load_property_settings($post_id, $return_defaults = false) {

		global $post;

		//populate agent select
		$agent_obj = new PropertyShift_Agents();
		$agent_select_options = $agent_obj->get_agents();

        // settings
		$property_settings_init = array(
			'id' => array(
				'group' => 'general',
				'title' => esc_html__('Property Code', 'propertyshift'),
				'description' => esc_html__('An optional string to used to identify properties', 'propertyshift'),
				'name' => 'ps_property_code',
				'type' => 'text',
				'value' => $post_id,
				'order' => 0,
			),
			'featured' => array(
				'group' => 'general',
				'title' => esc_html__('Featured Property', 'propertyshift'),
				'name' => 'ps_property_featured',
				'type' => 'checkbox',
				'value' => 'false',
				'order' => 1,
			),
			'street_address' => array(
				'group' => 'general',
				'title' => esc_html__('Street Address', 'propertyshift'),
				'name' => 'ps_property_address',
				'description' => esc_html__('Provide the address for the property', 'propertyshift'),
				'type' => 'text',
				'order' => 2,
			),
			'price' => array(
				'group' => 'general',
				'title' => esc_html__('Price', 'propertyshift'),
				'name' => 'ps_property_price',
				'description' => esc_html__('Use only numbers. Do not include commas or dollar sign (ex.- 250000)', 'propertyshift'),
				'type' => 'number',
				'min' => 0,
				'order' => 3,
			),
			'price_postfix' => array(
				'group' => 'general',
				'title' => esc_html__('Price Postfix', 'propertyshift'),
				'name' => 'ps_property_price_postfix',
				'description' => esc_html__('Provide the text displayed after the price (ex.- Per Month)', 'propertyshift'),
				'type' => 'text',
				'order' => 4,
			),
			'beds' => array(
				'group' => 'general',
				'title' => esc_html__('Bedrooms', 'propertyshift'),
				'name' => 'ps_property_bedrooms',
				'description' => esc_html__('Provide the number of bedrooms', 'propertyshift'),
				'type' => 'number',
				'min' => 0,
				'order' => 5,
			),
			'baths' => array(
				'group' => 'general',
				'title' => esc_html__('Bathrooms', 'propertyshift'),
				'name' => 'ps_property_bathrooms',
				'description' => esc_html__('Provide the number of bathrooms', 'propertyshift'),
				'type' => 'number',
				'min' => 0,
				'step' => 0.5,
				'order' => 6,
			),
			'garages' => array(
				'group' => 'general',
				'title' => esc_html__('Garages', 'propertyshift'),
				'name' => 'ps_property_garages',
				'description' => esc_html__('Provide the number of garages', 'propertyshift'),
				'type' => 'number',
				'min' => 0,
				'order' => 7,
			),
			'area' => array(
				'group' => 'general',
				'title' => esc_html__('Area', 'propertyshift'),
				'name' => 'ps_property_area',
				'description' => esc_html__('Provide the area. Use only numbers and decimals, do not include commas.', 'propertyshift'),
				'type' => 'number',
				'min' => 0,
				'step' => 0.01,
				'order' => 8,
			),
			'area_postfix' => array(
				'group' => 'general',
				'title' => esc_html__('Area Postfix', 'propertyshift'),
				'name' => 'ps_property_area_postfix',
				'description' => esc_html__('Provide the text to display directly after the area (ex. - Sq Ft)', 'propertyshift'),
				'type' => 'text',
				'value' => 'Sq Ft',
				'order' => 9,
			),
			'description' => array(
				'group' => 'description',
				'name' => 'ps_property_description',
				'type' => 'editor',
				'order' => 10,
				'class' => 'full-width no-padding',
				'esc' => false,
			),
			'gallery' => array(
				'group' => 'gallery',
				'name' => 'ps_additional_img',
				'type' => 'gallery',
				'serialized' => true,
				'order' => 11,
				'class' => 'full-width no-padding',
			),
			'floor_plans' => array(
				'group' => 'floor_plans',
				'name' => 'ps_property_floor_plans',
				'type' => 'floor_plans',
				'serialized' => true,
				'order' => 12,
				'class' => 'full-width no-padding',
			),
			'latitude' => array(
				'group' => 'map',
				'title' => esc_html__('Latitude', 'propertyshift'),
				'name' => 'ps_property_latitude',
				'type' => 'text',
				'order' => 13,
			),
			'longitude' => array(
				'group' => 'map',
				'title' => esc_html__('Longitude', 'propertyshift'),
				'name' => 'ps_property_longitude',
				'type' => 'text',
				'order' => 14,	
			),
			'video_url' => array(
				'group' => 'video',
				'title' => esc_html__('Video URL', 'propertyshift'),
				'name' => 'ps_property_video_url',
				'type' => 'text',
				'order' => 15,
			),
			'video_cover' => array(
				'group' => 'video',
				'title' => esc_html__('Video Cover Image', 'propertyshift'),
				'name' => 'ps_property_video_img',
				'type' => 'image_upload',
				'display_img' => true,
				'order' => 16,
			),
			'agent' => array(
				'group' => 'owner_info',
				'title' => esc_html__('Select an Agent', 'propertyshift'),
				'name' => 'post_author_override', //overrides the author
				'type' => 'select',
				'options' => $agent_select_options,
				'value' => $post->post_author,
				'order' => 17,
			),
			'agent_display' => array(
				'group' => 'owner_info',
				'title' => esc_html__('Display Agent Info on Listing', 'propertyshift'),
				'description' => esc_html__('If checked, the agents info will be publicly displayed on the listing', 'propertyshift'),
				'name' => 'ps_property_agent_display',
				'type' => 'checkbox',
				'value' => true,
				'order' => 18,
			),
		);
		$property_settings_init = apply_filters('propertyshift_property_settings_init_filter', $property_settings_init, $post_id);
		uasort($property_settings_init, 'ns_basics_sort_by_order');

		// Return default settings
		if($return_defaults == true) {
			
			return $property_settings_init;
		
		// Return saved settings
		} else {
			$property_settings = $this->admin_obj->get_meta_box_values($post_id, $property_settings_init);
			return $property_settings;
		}
	}

	/**
	 *	Output meta box interface
	 */
	public function output_meta_box($post) {

		$property_settings = $this->load_property_settings($post->ID); 
		wp_nonce_field( 'ps_property_details_meta_box_nonce', 'ps_property_details_meta_box_nonce' ); ?>
		
		<div class="ns-tabs meta-box-form meta-box-form-property-details">
			<ul class="ns-tabs-nav">
	            <li><a href="#general" title="<?php esc_html_e('General Info', 'propertyshift'); ?>"><i class="fa fa-home"></i> <span class="tab-text"><?php echo esc_html_e('General Info', 'propertyshift'); ?></span></a></li>
	            <li><a href="#description" title="<?php esc_html_e('Description', 'propertyshift'); ?>"><i class="fa fa-pencil-alt"></i> <span class="tab-text"><?php echo esc_html_e('Description', 'propertyshift'); ?></span></a></li>
	            <li><a href="#gallery" title="<?php esc_html_e('Gallery', 'propertyshift'); ?>"><i class="fa fa-image"></i> <span class="tab-text"><?php echo esc_html_e('Gallery', 'propertyshift'); ?></span></a></li>
	            <li><a href="#floor-plans" title="<?php esc_html_e('Floor Plans', 'propertyshift'); ?>"><i class="fa fa-th-large"></i> <span class="tab-text"><?php echo esc_html_e('Floor Plans', 'propertyshift'); ?></span></a></li>
	            <li><a href="#map" title="<?php esc_html_e('Map', 'propertyshift'); ?>" onclick="refreshMap()"><i class="fa fa-map"></i> <span class="tab-text"><?php echo esc_html_e('Map', 'propertyshift'); ?></span></a></li>
	            <li><a href="#video" title="<?php esc_html_e('Video', 'propertyshift'); ?>"><i class="fa fa-video"></i> <span class="tab-text"><?php echo esc_html_e('Video', 'propertyshift'); ?></span></a></li>
	            <li><a href="#agent" title="<?php esc_html_e('Contacts', 'propertyshift'); ?>"><i class="fa fa-user"></i> <span class="tab-text"><?php echo esc_html_e('Contacts', 'propertyshift'); ?></span></a></li>
	            <?php do_action('propertyshift_after_property_tabs'); ?>
	        </ul>

	        <div class="ns-tabs-content">
        	<div class="tab-loader"><img src="<?php echo esc_url(home_url('/')); ?>wp-admin/images/spinner.gif" alt="" /> <?php echo esc_html_e('Loading...', 'propertyshift'); ?></div>

        	<!--*************************************************-->
	        <!-- GENERAL INFO -->
	        <!--*************************************************-->
	        <div id="general" class="tab-content">
	            <h3><?php echo esc_html_e('General Info', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'general') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            } ?>
	        </div>

	        <!--*************************************************-->
	        <!-- DESCRIPTION -->
	        <!--*************************************************-->
	        <div id="description" class="tab-content">
	            <h3><?php echo esc_html_e('Description', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'description') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            } ?>
	        </div>

	        <!--*************************************************-->
	        <!-- GALLERY -->
	        <!--*************************************************-->
	        <div id="gallery" class="tab-content">
	            <h3><?php echo esc_html_e('Gallery', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'gallery') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            } ?>
	        </div>

	        <!--*************************************************-->
	        <!-- FLOOR PLANS -->
	        <!--*************************************************-->
	        <div id="floor-plans" class="tab-content">
	            <h3><?php echo esc_html_e('Floor Plans', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'floor_plans') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            } ?>
	        </div>

	        <!--*************************************************-->
	        <!-- MAP -->
	        <!--*************************************************-->
	        <div id="map" class="tab-content">
	            <h3><?php echo esc_html_e('Map', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'map') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            }
	            $maps_obj = new PropertyShift_Maps();
	            $maps_obj->build_single_property_map($property_settings['latitude']['value'], $property_settings['longitude']['value']);
	            ?>
	        </div>

	        <!--*************************************************-->
	        <!-- VIDEO -->
	        <!--*************************************************-->
	        <div id="video" class="tab-content">
	            <h3><?php echo esc_html_e('Video', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'video') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            } ?>
	        </div>

	        <!--*************************************************-->
	        <!-- CONTACT INFO -->
	        <!--*************************************************-->
	        <div id="agent" class="tab-content">
	            <h3><?php echo esc_html_e('Primary Agent', 'propertyshift'); ?></h3>
	            <?php
	            foreach($property_settings as $setting) {
	            	if($setting['group'] == 'owner_info') {
            			$this->admin_obj->build_admin_field($setting);
            		}
	            } ?>
	        </div>

	        <?php do_action('propertyshift_after_property_tab_content', $property_settings); ?>

        	</div><!-- end ns-tabs-content -->
        	<div class="clear"></div>

		</div><!-- end ns-tabs -->

	<?php }

	/**
	 * Save Meta Box
	 */
	public function save_meta_box($post_id) {
		// Bail if we're doing an auto save
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // if our nonce isn't there, or we can't verify it, bail
        if( !isset( $_POST['ps_property_details_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['ps_property_details_meta_box_nonce'], 'ps_property_details_meta_box_nonce' ) ) return;

        // if our current user can't edit this post, bail
        if( !current_user_can( 'edit_post', $post_id ) ) return;

        // allow certain attributes
        $allowed = array('a' => array('href' => array()));

        // Load property settings and save
        $property_settings = $this->load_property_settings($post_id);
        $this->admin_obj->save_meta_box($post_id, $property_settings, $allowed);
	}

	/************************************************************************/
	// Property Taxonomies
	/************************************************************************/

	/**
	 *	Register property type taxonomy
	 */
	public function property_type_init() {
		$property_type_tax_slug = $this->global_settings['ps_property_type_tax_slug'];
	    $labels = array(
	    'name'                          => __( 'Property Type', 'propertyshift' ),
	    'singular_name'                 => __( 'Property Type', 'propertyshift' ),
	    'search_items'                  => __( 'Search Property Types', 'propertyshift' ),
	    'popular_items'                 => __( 'Popular Property Types', 'propertyshift' ),
	    'all_items'                     => __( 'All Property Types', 'propertyshift' ),
	    'parent_item'                   => __( 'Parent Property Type', 'propertyshift' ),
	    'edit_item'                     => __( 'Edit Property Type', 'propertyshift' ),
	    'update_item'                   => __( 'Update Property Type', 'propertyshift' ),
	    'add_new_item'                  => __( 'Add New Property Type', 'propertyshift' ),
	    'new_item_name'                 => __( 'New Property Type', 'propertyshift' ),
	    'separate_items_with_commas'    => __( 'Separate property types with commas', 'propertyshift' ),
	    'add_or_remove_items'           => __( 'Add or remove property types', 'propertyshift' ),
	    'choose_from_most_used'         => __( 'Choose from most used property types', 'propertyshift' )
	    );
	    
	    register_taxonomy(
	        'property_type',
	        'ps-property',
	        array(
	            'label'         => __( 'Property Types', 'propertyshift' ),
	            'labels'        => $labels,
	            'hierarchical'  => true,
	            'rewrite' => array( 'slug' => $property_type_tax_slug ),
	            'capabilities' => array(
	            	'manage_terms' => 'manage_property_type',
    				'edit_terms' => 'edit_property_type',
    				'delete_terms' => 'delete_property_type',
	            	'assign_terms' => 'assign_property_type',
	            ),
	        )
	    );
	}

	/**
	 *	Register property status taxonomy
	 */
	public function property_status_init() {
		$property_status_tax_slug = $this->global_settings['ps_property_status_tax_slug'];
	    $labels = array(
	    'name'                          => __( 'Property Status', 'propertyshift' ),
	    'singular_name'                 => __( 'Property Status', 'propertyshift' ),
	    'search_items'                  => __( 'Search Property Statuses', 'propertyshift' ),
	    'popular_items'                 => __( 'Popular Property Statuses', 'propertyshift' ),
	    'all_items'                     => __( 'All Property Statuses', 'propertyshift' ),
	    'parent_item'                   => __( 'Parent Property Status', 'propertyshift' ),
	    'edit_item'                     => __( 'Edit Property Status', 'propertyshift' ),
	    'update_item'                   => __( 'Update Property Status', 'propertyshift' ),
	    'add_new_item'                  => __( 'Add New Property Status', 'propertyshift' ),
	    'new_item_name'                 => __( 'New Property Status', 'propertyshift' ),
	    'separate_items_with_commas'    => __( 'Separate property statuses with commas', 'propertyshift' ),
	    'add_or_remove_items'           => __( 'Add or remove property statuses', 'propertyshift' ),
	    'choose_from_most_used'         => __( 'Choose from most used property statuses', 'propertyshift' )
	    );
	    
	    register_taxonomy(
	        'property_status',
	        'ps-property',
	        array(
	            'label'         => __( 'Property Status', 'propertyshift' ),
	            'labels'        => $labels,
	            'hierarchical'  => true,
	            'rewrite' => array( 'slug' => $property_status_tax_slug ),
	            'capabilities' => array(
	            	'manage_terms' => 'manage_property_status',
    				'edit_terms' => 'edit_property_status',
    				'delete_terms' => 'delete_property_status',
	            	'assign_terms' => 'assign_property_status',
	            ),
	        )
	    );
	}

	/**
	 *	Register property location taxonomy
	 */
	public function property_location_init() {
		$property_location_tax_slug = $this->global_settings['ps_property_location_tax_slug'];
	    $labels = array(
	    'name'                          => __( 'Property Location', 'propertyshift' ),
	    'singular_name'                 => __( 'Property Location', 'propertyshift' ),
	    'search_items'                  => __( 'Search Property Locations', 'propertyshift' ),
	    'popular_items'                 => __( 'Popular Property Locations', 'propertyshift' ),
	    'all_items'                     => __( 'All Property Locations', 'propertyshift' ),
	    'parent_item'                   => __( 'Parent Property Location', 'propertyshift' ),
	    'edit_item'                     => __( 'Edit Property Location', 'propertyshift' ),
	    'update_item'                   => __( 'Update Property Location', 'propertyshift' ),
	    'add_new_item'                  => __( 'Add New Property Location', 'propertyshift' ),
	    'new_item_name'                 => __( 'New Property Location', 'propertyshift' ),
	    'separate_items_with_commas'    => __( 'Separate property locations with commas', 'propertyshift' ),
	    'add_or_remove_items'           => __( 'Add or remove property locations', 'propertyshift' ),
	    'choose_from_most_used'         => __( 'Choose from most used property locations', 'propertyshift' )
	    );
	    
	    register_taxonomy(
	        'property_location',
	        'ps-property',
	        array(
	            'label'         => __( 'Property Location', 'propertyshift' ),
	            'labels'        => $labels,
	            'hierarchical'  => true,
	            'rewrite' => array( 'slug' => $property_location_tax_slug ),
	            'capabilities' => array(
	            	'manage_terms' => 'manage_property_location',
    				'edit_terms' => 'edit_property_location',
    				'delete_terms' => 'delete_property_location',
	            	'assign_terms' => 'assign_property_location',
	            ),
	        )
	    );
	}

	/**
	 *	Register property amenities taxonomy
	 */
	public function property_amenities_init() {
		$property_amenities_tax_slug = $this->global_settings['ps_property_amenities_tax_slug'];
	    $labels = array(
	    'name'                          => __( 'Amenities', 'propertyshift' ),
	    'singular_name'                 => __( 'Amenity', 'propertyshift' ),
	    'search_items'                  => __( 'Search Amenities', 'propertyshift' ),
	    'popular_items'                 => __( 'Popular Amenities', 'propertyshift' ),
	    'all_items'                     => __( 'All Amenities', 'propertyshift' ),
	    'parent_item'                   => __( 'Parent Amenity', 'propertyshift' ),
	    'edit_item'                     => __( 'Edit Amenity', 'propertyshift' ),
	    'update_item'                   => __( 'Update Amenity', 'propertyshift' ),
	    'add_new_item'                  => __( 'Add New Amenity', 'propertyshift' ),
	    'new_item_name'                 => __( 'New Amenity', 'propertyshift' ),
	    'separate_items_with_commas'    => __( 'Separate amenities with commas', 'propertyshift' ),
	    'add_or_remove_items'           => __( 'Add or remove amenities', 'propertyshift' ),
	    'choose_from_most_used'         => __( 'Choose from most used amenities', 'propertyshift' )
	    );
	    
	    register_taxonomy(
	        'property_amenities',
	        'ps-property',
	        array(
	            'label'         => __( 'Amenities', 'propertyshift' ),
	            'labels'        => $labels,
	            'hierarchical'  => true,
	            'rewrite' => array( 'slug' => $property_amenities_tax_slug ),
	            'capabilities' => array(
	            	'manage_terms' => 'manage_property_amenities',
    				'edit_terms' => 'edit_property_amenities',
    				'delete_terms' => 'delete_property_amenities',
	            	'assign_terms' => 'assign_property_amenities',
	            ),
	        )
	    );
	}

	/************************************************************************/
	// Add Columns to Properties Post Type
	/************************************************************************/

	/**
	 *	Add properties columns
	 *
	 * @param array $columns
	 *
	 */
	public function add_properties_columns($columns) {
		$columns = array(
	        'cb' => '<input type="checkbox" />',
	        'title' => __( 'Property', 'propertyshift' ),
	        'thumbnail' => __('Image', 'propertyshift'),
	        'location' => __( 'Location', 'propertyshift' ),
	        'type' => __( 'Type', 'propertyshift' ),
	        'status' => __( 'Status', 'propertyshift' ),
	        'price'  => __( 'Price','propertyshift' ),
	        'agent' => __('Assigned Agent', 'propertyshift'),
	        'date' => __( 'Date', 'propertyshift' )
	    );
	    return $columns;
	}

	/**
	 *	Manage properties columns
	 *
	 * @param string $column
	 * @param int $post_id 
	 */
	public function manage_properties_columns($column, $post_id) {
		global $post;
		$property_settings = $this->load_property_settings($post_id); 

	    switch( $column ) {

	        case 'thumbnail' :
	            if(has_post_thumbnail()) { echo the_post_thumbnail('thumbnail'); } else { echo '--'; }
	            break;

	        case 'price' :
	            $price = $property_settings['price']['value'];
	            if(!empty($price)) { $price = $this->get_formatted_price($price); }
	            if(empty($price)) { echo '--'; } else { echo $price; }
	            break;

	        case 'location' :

	            //Get property location
	          	$property_location = $this->get_tax_location($post_id);
	          	$address = $property_settings['street_address']['value'];
	          	if(!empty($address)) { echo $address.'<br/>'; }
	            if(empty($property_location)) { echo '--'; } else { echo $property_location; }
	            break;

	        case 'type' :

	            //Get property type
	        	$property_type = $this->get_tax($post_id, 'property_type');
	            if(empty( $property_type)) { echo '--'; } else { echo $property_type; }
	            break;

	        case 'status' :

	            //Get property status
	        	$property_status = $this->get_tax($post_id, 'property_status');
	            if(empty($property_status)) { echo '--'; } else { echo $property_status; }
	            break;

	        case 'agent' :

	        	$agent_id = get_the_author_meta('ID');
	            if(!empty($agent_id)) { 
	            	$agent = get_userdata($agent_id); ?>
	            	<a href="<?php echo get_edit_user_link($agent_id); ?>"><?php echo $agent->display_name; ?></a>
	            <?php } else {
	            	echo '--';
	            }
	            break;

	        default :
	            break;
	    }
	}

	/************************************************************************/
	// Customize Property Taxonomies Admin Page
	/************************************************************************/

	/**
	 *	Add taxonomy fields
	 *
	 * @param string $tag
	 */
	public function add_tax_fields($tag) {
		if(is_object($tag)) { $t_id = $tag->term_id; } else { $t_id = ''; }
	    $term_meta = get_option( "taxonomy_$t_id");
	    ?>
	    <tr class="form-field">
	        <th scope="row" valign="top"><label for="cat_Image_url"><?php esc_html_e('Category Image Url', 'propertyshift'); ?></label></th>
	        <td>
	            <div class="admin-module admin-module-tax-field admin-module-tax-img no-border">
	                <input type="text" class="property-tax-img" name="term_meta[img]" id="term_meta[img]" size="3" style="width:60%;" value="<?php echo $term_meta['img'] ? $term_meta['img'] : ''; ?>">
	                <input class="button admin-button ns_upload_image_button" type="button" value="<?php esc_html_e('Upload Image', 'propertyshift'); ?>" />
	                <span class="button button-secondary remove"><?php esc_html_e('Remove', 'propertyshift'); ?></span><br/>
	                <p class="description"><?php esc_html_e('Image for Term, use full url', 'propertyshift'); ?></p>
	            </div>
	        </td>
	    </tr>
	<?php }

	/**
	 *	Add taxonomy price range field
	 *
	 * @param string $tag
	 */
	public function add_tax_price_range_field($tag) {
		if(is_object($tag)) { $t_id = $tag->term_id; } else { $t_id = ''; }
	    $term_meta = get_option( "taxonomy_$t_id");
	    ?>
	    <tr class="form-field">
	        <th scope="row" valign="top">
	            <strong><?php esc_html_e('Price Range Settings', 'propertyshift'); ?></strong>
	            <p class="admin-module-note"><?php esc_html_e('Settings here will override the defaults configured in the plugin settings.', 'propertyshift'); ?></p>
	        </th>
	        <td>
	            <div class="admin-module admin-module-tax-field tax-price-range-field no-border">
	                <label for="price_range_min"><?php esc_html_e('Minimum', 'propertyshift'); ?></label>
	                <input type="number" class="property-tax-price-range-min" name="term_meta[price_range_min]" id="term_meta[price_range_min]" size="3" value="<?php echo $term_meta['price_range_min'] ? $term_meta['price_range_min'] : ''; ?>">
	            </div>
	            <div class="admin-module admin-module-tax-field tax-price-range-field no-border">
	                <label for="price_range_max"><?php esc_html_e('Maximum', 'propertyshift'); ?></label>
	                <input type="number" class="property-tax-price-range-max" name="term_meta[price_range_max]" id="term_meta[price_range_max]" size="3" value="<?php echo $term_meta['price_range_max'] ? $term_meta['price_range_max'] : ''; ?>">
	            </div>
	            <div class="admin-module admin-module-tax-field tax-price-range-field no-border">
	                <label for="price_range_min_start"><?php esc_html_e('Minimum Start', 'propertyshift'); ?></label>
	                <input type="number" class="property-tax-price-range-min-start" name="term_meta[price_range_min_start]" id="term_meta[price_range_min_start]" size="3" value="<?php echo $term_meta['price_range_min_start'] ? $term_meta['price_range_min_start'] : ''; ?>">
	            </div>
	            <div class="admin-module admin-module-tax-field tax-price-range-field no-border">
	                <label for="price_range_max_start"><?php esc_html_e('Maximum Start', 'propertyshift'); ?></label>
	                <input type="number" class="property-tax-price-range-max-start" name="term_meta[price_range_max_start]" id="term_meta[price_range_max_start]" size="3" value="<?php echo $term_meta['price_range_max_start'] ? $term_meta['price_range_max_start'] : ''; ?>">
	            </div>
	        </td>
	    </tr>
	<?php }

	/**
	 *	Save taxonomy fields
	 *
	 * @param int $term_id
	 */
	public function save_tax_fields($term_id) {
		if ( isset( $_POST['term_meta'] ) ) {
	        $t_id = $term_id;
	        $term_meta = get_option( "taxonomy_$t_id");
	        $cat_keys = array_keys($_POST['term_meta']);
	            foreach ($cat_keys as $key){
	            if (isset($_POST['term_meta'][$key])){
	                $term_meta[$key] = $_POST['term_meta'][$key];
	            }
	        }
	        //save the option array
	        update_option( "taxonomy_$t_id", $term_meta );
	    }
	}

	/************************************************************************/
	// Property Utilities
	/************************************************************************/

	/**
	 *	Count properties
	 *
	 * @param string $type
	 * @param int $user_id 
	 */
	public function count_properties($type, $user_id = null) {
		$args_total_properties = array(
            'post_type' => 'ps-property',
            'showposts' => -1,
            'author' => $user_id,
            'post_status' => $type 
        );

        $meta_posts = get_posts( $args_total_properties );
        $meta_post_count = count( $meta_posts );
        unset( $meta_posts);
        return $meta_post_count;
	}

	/**
	 *	Get formatted price
	 *
	 * @param string $price
	 */
	public function get_formatted_price($price) {

	    $currency_symbol = $this->global_settings['ps_currency_symbol'];
	    $currency_symbol_position = $this->global_settings['ps_currency_symbol_position'];
	    $currency_thousand = $this->global_settings['ps_thousand_separator'];
	    $currency_decimal = $this->global_settings['ps_decimal_separator'];
	    $currency_decimal_num =  $this->global_settings['ps_num_decimal'];

	    if(!empty($price)) { $price = number_format($price, $currency_decimal_num, $currency_decimal, $currency_thousand); }
	    if($currency_symbol_position == 'before') { $price = $currency_symbol.$price; } else { $price = $price.$currency_symbol; }

	    return $price;
	}

	/**
	 *	Get formatted area
	 *
	 * @param string $area
	 */
	public function get_formatted_area($area) {
		
	    $decimal_num_area = $this->global_settings['ps_num_decimal_area'];
	    $decimal_area = $this->global_settings['ps_decimal_separator_area'];
	    $thousand_area =  $this->global_settings['ps_thousand_separator_area'];

    	if(!empty($area)) { $area = number_format($area, $decimal_num_area, $decimal_area, $thousand_area); }
    	return $area;
	}

	/**
	 *	Get property taxonomy
	 *
	 * @param int $post_id
	 * @param string $tax
	 * @param string $array
	 */
	public function get_tax($post_id, $tax, $array = null, $hide_empty = true) {
		$output = '';

	    if($hide_empty == false) {
	        $tax_terms =  get_terms(['taxonomy' => $tax, 'hide_empty' => false,]);
	    } else {
	        $tax_terms = get_the_terms( $post_id, $tax);
	    }

	    if($tax_terms && ! is_wp_error($tax_terms)) : 
	        
	        //populate term links
	        $term_links = array();
	        foreach ($tax_terms as $term) {
	            if($array == 'true') {
	                $term_links[] = $term->slug;
	            } else {
	                $term_links[] = '<a href="'. esc_attr(get_term_link($term->slug, $tax)) .'">'.$term->name.'</a>' ;
	            }
	        }

	        //determine output
	        if($array == 'true') { $output = $term_links;  } else { $output = join( ", ", $term_links); }
	    
	    endif;
	    return $output;
	}

	/**
	 *	Get property location
	 *
	 * @param int $post_id
	 */
	public function get_tax_location($post_id, $output = null, $array = null) {
		$property_location = '';
	    $property_location_output = '';
	    $property_location_terms = get_the_terms( $post_id, 'property_location');
	    if ( $property_location_terms && ! is_wp_error( $property_location_terms) ) : 
	        $property_location_links = array();
	        $property_location_child_links = array();
	        foreach ( $property_location_terms as $property_location_term ) {
	            if($property_location_term->parent != 0) {
	                if($array == 'true') {
	                    $property_location_child_links[] = $property_location_term->slug;
	                } else {
	                    $property_location_child_links[] = '<a href="'. esc_attr(get_term_link($property_location_term ->slug, 'property_location')) .'">'.$property_location_term ->name.'</a>' ;
	                }
	            } else {
	                if($array == 'true') {
	                    $property_location_links[] = $property_location_term->slug;
	                } else {
	                    $property_location_links[] = '<a href="'. esc_attr(get_term_link($property_location_term ->slug, 'property_location')) .'">'.$property_location_term ->name.'</a>' ;
	                }
	            }
	        }                   
	        $property_location = join( "<span>, </span>", $property_location_links );
	        $property_location_children = join( "<span>, </span>", $property_location_child_links );
	    endif;

	    if($array == 'true') {
	        if(!empty($property_location_links)) { $property_location_output = array_merge($property_location_links, $property_location_child_links); }
	    } else {
	        if($output == 'parent') {
	            $property_location_output = $property_location;
	        } else if($output == 'children') {
	            $property_location_output = $property_location_children;
	        } else {
	            $property_location_output .= $property_location_children;
	            if(!empty($property_location_children) && !empty($property_location)) { $property_location_output .= ', '; } 
	            $property_location_output .= $property_location;
	        }
	    }
	    
	    return $property_location_output; 
	}

	/**
	 *	Retrieves the full address, including location
	 *
	 * @param int $post_id
	 *
	 */
	public function get_full_address($post_id) {
	    $property_settings = $this->load_property_settings($post_id);
	    $street_address = $property_settings['street_address']['value'];
	    $property_address = '';
	    $property_location = $this->get_tax_location($post_id);
	    if(!empty($street_address)) { $property_address .= $street_address; }
	    if(!empty($street_address) && !empty($property_location)) { $property_address .= ', '; }
	    if(!empty($property_location)) { $property_address .= $property_location; }
	    return $property_address;
	}

	/**
	 *	Get property amenities
	 *
	 * @param int $post_id
	 * @param boolean $hide_empty
	 * @param boolean $array
	 */
	public function get_tax_amenities($post_id, $hide_empty = true, $array = null) {
		$property_amenities = '';
	    $property_amenities_links = array();

	    if($hide_empty == false) {
	        $property_amenities_terms =  get_terms(['taxonomy' => 'property_amenities', 'hide_empty' => false,]);
	    } else {
	        $property_amenities_terms = get_the_terms( $post_id, 'property_amenities' );
	    }

	    if ( $property_amenities_terms && ! is_wp_error( $property_amenities_terms) ) : 
	        foreach ( $property_amenities_terms as $property_amenity_term ) {
	            if($array == 'true') {
	                $property_amenities_links[] = $property_amenity_term->slug;
	            } else {
	                if(has_term($property_amenity_term->slug, 'property_amenities', $post_id)) { $icon = '<i class="fa fa-check icon"></i>'; } else { $icon = '<i class="fa fa-times icon"></i>'; }
	                $property_amenities_links[] = '<li><a href="'. esc_attr(get_term_link($property_amenity_term->slug, 'property_amenities')) .'">'.$icon.'<span>'.$property_amenity_term->name.'</span></a></li>' ;
	            }
	        } 
	    endif;

	    if($array == 'true') { 
	        $property_amenities = $property_amenities_links;
	    } else { 
	        $property_amenities = join( '', $property_amenities_links ); 
	        if(!empty($property_amenities)) { $property_amenities = '<ul class="amenities-list clean-list">'.$property_amenities.'</ul>'; }
	    }

	    return $property_amenities;
	}

	/**
	 *	Get property walkscore
	 *
	 * @param int $post_id
	 *
	 */
	public function get_walkscore($lat, $lon, $address) {
		$address = urlencode($address);
	    $url = "http://api.walkscore.com/score?format=json&address=$address";
	    $url .= "&lat=$lat&lon=$lon&wsapikey=f6c3f50b09a7ce69d6d276015e57e996";
	    $request = wp_remote_get($url);
	    $str = wp_remote_retrieve_body($request);
	    return $str;
	}


	/************************************************************************/
	// Property Page Settings Methods
	/************************************************************************/
	
	/**
	 *	Add page settings meta box
	 *
	 * @param array $post_types
	 */
	public function add_page_settings_meta_box($post_types) {
		$post_types[] = 'ps-property';
    	return $post_types;
	}

	/**
	 *	Add page settings
	 *
	 * @param array $page_settings_init
	 */
	public function add_page_settings($page_settings_init) {
		
		// Add map banner options
		$page_settings_init['banner_source']['options'][esc_html__('Map Banner', 'propertyshift')] = array(
			'value' => 'properties_map', 
			'icon' => NS_BASICS_PLUGIN_DIR.'/images/google-maps-icon.png', 
		);

		// Add filter banner options
		$page_settings_init['property_filter_override'] = array(
			'group' => 'banner',
			'title' => esc_html__('Use Custom Property Filter Settings', 'propertyshift'),
			'name' => 'ns_banner_property_filter_override',
			'description' => esc_html__('The global property filter settings can be configured in PropertyShift > Settings', 'propertyshift'),
			'value' => 'false',
			'type' => 'switch',
			'order' => 14,
			'children' => array(
				'property_filter_display' => array(
					'title' => esc_html__('Display Property Filter', 'propertyshift'),
					'name' => 'ns_banner_property_filter_display',
					'type' => 'checkbox',
					'value' => 'true',
				),
				'property_filter_id' => array(
					'title' => esc_html__('Select a Filter', 'propertyshift'),
					'name' => 'ns_banner_property_filter_id',
					'type' => 'select',
					'options' => PropertyShift_Filters::get_filter_ids(),
				),
			),
		);

		// Set default page layout
		if($_GET['post_type'] == 'ps-property') { $page_settings_init['page_layout']['value'] = 'right sidebar'; }
			
		// Set default page sidebar
		if($_GET['post_type'] == 'ps-property') { $page_settings_init['page_layout_widget_area']['value'] = 'properties_sidebar'; }

		return $page_settings_init;
	}

	/************************************************************************/
	// Property Detail Methods
	/************************************************************************/

	/**
	 *	Load property detail items
	 */
	public static function load_property_detail_items() {
		$property_detail_items_init = array(
	        0 => array(
	            'name' => esc_html__('Overview', 'propertyshift'),
	            'label' => esc_html__('Overview', 'propertyshift'),
	            'slug' => 'overview',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        1 => array(
	            'name' => esc_html__('Description', 'propertyshift'),
	            'label' => esc_html__('Description', 'propertyshift'),
	            'slug' => 'description',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        2 => array(
	            'name' => esc_html__('Gallery', 'propertyshift'),
	            'label' => esc_html__('Gallery', 'propertyshift'),
	            'slug' => 'gallery',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        3 => array(
	            'name' => esc_html__('Property Details', 'propertyshift'),
	            'label' => esc_html__('Property Details', 'propertyshift'),
	            'slug' => 'property_details',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        4 => array(
	            'name' => esc_html__('Video', 'propertyshift'),
	            'label' => esc_html__('Video', 'propertyshift'),
	            'slug' => 'video',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        5 => array(
	            'name' => esc_html__('Amenities', 'propertyshift'),
	            'label' => esc_html__('Amenities', 'propertyshift'),
	            'slug' => 'amenities',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        6 => array(
	            'name' => esc_html__('Floor Plans', 'propertyshift'),
	            'label' => esc_html__('Floor Plans', 'propertyshift'),
	            'slug' => 'floor_plans',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        7 => array(
	            'name' => esc_html__('Location', 'propertyshift'),
	            'label' => esc_html__('Location', 'propertyshift'),
	            'slug' => 'location',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        8 => array(
	            'name' => esc_html__('Walk Score', 'propertyshift'),
	            'label' => esc_html__('Walk Score', 'propertyshift'),
	            'slug' => 'walk_score',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        9 => array(
	            'name' => esc_html__('Agent Info', 'propertyshift'),
	            'label' => 'Agent Information',
	            'slug' => 'agent_info',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	        10 => array(
	            'name' => esc_html__('Related Properties', 'propertyshift'),
	            'label' => 'Related Properties',
	            'slug' => 'related',
	            'active' => 'true',
	            'sidebar' => 'false',
	        ),
	    );

		$property_detail_items_init = apply_filters( 'propertyshift_property_detail_items_init_filter', $property_detail_items_init);
	    return $property_detail_items_init;
	}
	

	/************************************************************************/
	// Property Submit Methods
	/************************************************************************/

	/**
	 *	Load front-end property submit fields
	 */
	public static function load_property_submit_fields() {
		$property_submit_fields_init = array(
			'property_title' => array('value' => esc_html__('Property Title (required)', 'propertyshift'), 'attributes' => array('disabled', 'checked')),
            'price' => array('value' => esc_html__('Price (required)', 'propertyshift'), 'attributes' => array('disabled', 'checked')),
            'price_postfix' => array('value' => esc_html__('Price Postfix', 'propertyshift')),
            'street_address' => array('value' => esc_html__('Street Address (required)', 'propertyshift'), 'attributes' => array('disabled', 'checked')),
            'description' => array('value' => esc_html__('Description', 'propertyshift')),
            'beds' => array('value' => esc_html__('Beds', 'propertyshift')),
            'baths' => array('value' => esc_html__('Baths', 'propertyshift')),
            'garages' => array('value' => esc_html__('Garages', 'propertyshift')),
            'area' => array('value' => esc_html__('Area', 'propertyshift')),
            'area_postfix' => array('value' => esc_html__('Area Postfix', 'propertyshift')),
            'video' => array('value' => esc_html__('Video', 'propertyshift')),
            'property_location' => array('value' => esc_html__('Property Location', 'propertyshift')),
            'property_type' => array('value' => esc_html__('Property Type', 'propertyshift')),
            'property_status' => array('value' => esc_html__('Property Status', 'propertyshift')),
            'amenities' => array('value' => esc_html__('Amenities', 'propertyshift')),
            'floor_plans' => array('value' => esc_html__('Floor Plans', 'propertyshift')),
            'featured_image' => array('value' => esc_html__('Featured Image', 'propertyshift')),
            'gallery_images' => array('value' => esc_html__('Gallery Images', 'propertyshift')),
            'map' => array('value' => esc_html__('Map', 'propertyshift')),
	    );
	    $property_submit_fields_init = apply_filters( 'propertyshift_property_submit_fields_init_filter', $property_submit_fields_init);
	    return $property_submit_fields_init;
	}

	/**
	 *	Process front-end property submit
	 */
	public function insert_property_post($edit_property_id = null) {
		
		$admin_obj = new PropertyShift_Admin();
		$members_submit_property_approval = $admin_obj->load_settings(false, 'ps_members_submit_property_approval');
		if($members_submit_property_approval == 'true') {$members_submit_property_approval = 'pending'; } else { $members_submit_property_approval = 'publish'; }

		$output = array();
		$errors = array();

		// require a title
		if(trim($_POST['title']) === '') {
		    $errors['title'] =  esc_html__('Please enter a title!', 'propertyshift'); 
		} else {
		    $title = trim($_POST['title']);
		}

		// require an address
		if(trim($_POST['street_address']) === '') {
		    $errors['address'] =  esc_html__('Please enter an address!', 'propertyshift'); 
		} else {
		    $street_address = trim($_POST['street_address']);
		}

		// require a price
		if(trim($_POST['price']) === '') {
		    $errors['price'] =  esc_html__('Please enter a price!', 'propertyshift'); 
		} else {
		    $price = trim($_POST['price']);
		}

		// Get property taxonomies
		if(isset($_POST['property_location'])) { $property_location = $_POST['property_location']; }
		if(isset($_POST['property_type'])) { $property_type = $_POST['property_type']; }
		if(isset($_POST['contract_type'])) { $property_status = $_POST['contract_type']; }
		if(isset($_POST['property_amenities'])) { $property_amenities = $_POST['property_amenities']; }

		// If there are no errors
		if(empty($errors)) {

			//Insert or update post
			if(!empty($edit_property_id)) {
				$post_information = array(
		            'ID' => $edit_property_id,
		            'post_title' => wp_strip_all_tags( $title ),
		            'post_type' => 'ps-property'
		        );
		        wp_update_post( $post_information );
		        $post_ID = $edit_property_id;
			} else {
				$post_information = array(
			        'post_title' => wp_strip_all_tags( $title ),
			        'post_type' => 'ps-property',
			        'post_status' => $members_submit_property_approval
			    );
			    $post_ID = wp_insert_post( $post_information );
			}

			//Set taxonomies
	    	wp_set_object_terms($post_ID, $property_location, 'property_location', false);
	    	wp_set_object_terms($post_ID, $property_type, 'property_type', false);
	    	wp_set_object_terms($post_ID, $property_status, 'property_status', false);
	    	wp_set_object_terms($post_ID, $property_amenities, 'property_amenities', false);

	    	//upload property images
		    if(!empty($_FILES)) {
		        $additional_img_urls = array();
		        foreach( $_FILES as $file ) {

		            if($_FILES['featured_img']['tmp_name']) {
		                $attachment_id_featured_img = ns_basics_upload_user_file( $_FILES['featured_img'] );
		                set_post_thumbnail( $post_ID, $attachment_id_featured_img );
		            }
		            if( is_array($file) && $file['name'] != '' ) {
		                $attachment_id = ns_basics_upload_user_file( $file );
		                array_push($additional_img_urls, wp_get_attachment_url( $attachment_id ));
		            }
		        }
		    }  
		    if(!empty($edit_property_id)) { 		    	
				$edit_property_settings = $this->load_property_settings($edit_property_id);
				$edit_additional_images = $edit_property_settings['gallery']['value'];
		    	$additional_img_urls = array_merge($edit_additional_images, $additional_img_urls); 
		    }

		    //Set Post Meta
		    $allowed = '';
		    if( isset( $_POST['street_address'] ) )
		    	update_post_meta( $post_ID, 'ps_property_address', wp_kses( $_POST['street_address'], $allowed ) );

		    if( isset( $_POST['price'] ) )
		    	update_post_meta( $post_ID, 'ps_property_price', wp_kses( $_POST['price'], $allowed ) );

		    if( isset( $_POST['price_post'] ) )
		    	update_post_meta( $post_ID, 'ps_property_price_postfix', wp_kses( $_POST['price_post'], $allowed ) );

		    if( isset( $_POST['beds'] ) )
		    	update_post_meta( $post_ID, 'ps_property_bedrooms', wp_kses( $_POST['beds'], $allowed ) );

		    if( isset( $_POST['baths'] ) )
		    	update_post_meta( $post_ID, 'ps_property_bathrooms', wp_kses( $_POST['baths'], $allowed ) );

		    if( isset( $_POST['garages'] ) )
		    	update_post_meta( $post_ID, 'ps_property_garages', wp_kses( $_POST['garages'], $allowed ) );

		    if( isset( $_POST['area'] ) )
		    	update_post_meta( $post_ID, 'ps_property_area', wp_kses( $_POST['area'], $allowed ) );

		    if( isset( $_POST['area_post'] ) )
		    	update_post_meta( $post_ID, 'ps_property_area_postfix', wp_kses( $_POST['area_post'], $allowed ) );

		    if( isset( $_POST['video_url'] ) )
		    	update_post_meta( $post_ID, 'ps_property_video_url', wp_kses( $_POST['video_url'], $allowed ) );

		    if( isset( $_POST['video_img'] ) )
		    	update_post_meta( $post_ID, 'ps_property_video_img', wp_kses( $_POST['video_img'], $allowed ) );

		    if (isset( $_POST['ps_property_floor_plans'] )) {
		        update_post_meta( $post_ID, 'ps_property_floor_plans', $_POST['ps_property_floor_plans'] );
		    }

		    if( isset( $_POST['description'] ) )
	        	update_post_meta( $post_ID, 'ps_property_description', wp_kses_post($_POST['description']) );

		    if (!empty( $additional_img_urls )) { 
		        update_post_meta( $post_ID, 'ps_additional_img', $additional_img_urls);
		    } else {
		        update_post_meta( $post_ID, 'ps_additional_img', '');
		    }

		    if( isset( $_POST['latitude'] ) )
	        	update_post_meta( $post_ID, 'ps_property_latitude', wp_kses( $_POST['latitude'], $allowed ) );

		    if( isset( $_POST['longitude'] ) )
		        update_post_meta( $post_ID, 'ps_property_longitude', wp_kses( $_POST['longitude'], $allowed ) );

		    if( isset( $_POST['agent_custom_name'] ) )
		        update_post_meta( $post_ID, 'ps_agent_custom_name', wp_kses( $_POST['agent_custom_name'], $allowed ) );

		    if( isset( $_POST['agent_custom_email'] ) )
		        update_post_meta( $post_ID, 'ps_agent_custom_email', wp_kses( $_POST['agent_custom_email'], $allowed ) );

		    if( isset( $_POST['agent_custom_phone'] ) )
		        update_post_meta( $post_ID, 'ps_agent_custom_phone', wp_kses( $_POST['agent_custom_phone'], $allowed ) );

		    if( isset( $_POST['agent_custom_url'] ) )
		        update_post_meta( $post_ID, 'ps_agent_custom_url', wp_kses( $_POST['agent_custom_url'], $allowed ) );

		    //hook in for other add-ons
	    	do_action('propertyshift_save_property_submit', $post_ID);

			if($members_submit_property_approval == 'pending') {
		        $output['success'] = esc_html__('Your property,', 'propertyshift') .' <b>'. $title .',</b> '. esc_html__('was submitted for review!', 'propertyshift');
		    } else {
		        $output['success'] = esc_html__('Your property,', 'propertyshift') .' <b>'. $title .',</b> '. esc_html__('was published!', 'propertyshift');
		    }

		} else {
			$output['success'] = '';
		}

		$output['errors'] = $errors;
		return $output;
	}

	/************************************************************************/
	// Front-end Template Hooks
	/************************************************************************/

	/**
	 *	Add topbar links
	 */
	public function add_topbar_links() {
		$icon_set = 'fa';
		if(function_exists('ns_core_load_theme_options')) { $icon_set = ns_core_load_theme_options('ns_core_icon_set'); }
		$members_my_properties_page = $this->global_settings['ps_members_my_properties_page'];
		$members_submit_property_page = $this->global_settings['ps_members_submit_property_page']; ?>
		<?php if(!empty($members_my_properties_page) && (current_user_can('ps_agent') || current_user_can('administrator'))) { ?>
			<li><a href="<?php echo $members_my_properties_page; ?>"><?php echo ns_core_get_icon($icon_set, 'home'); ?><?php esc_html_e( 'My Properties', 'propertyshift' ); ?></a></li>
		<?php } ?>
		<?php if(!empty($members_submit_property_page) && (current_user_can('ps_agent') || current_user_can('administrator'))) { ?>
			<li><a href="<?php echo $members_submit_property_page; ?>"><?php echo ns_core_get_icon($icon_set, 'plus'); ?><?php esc_html_e( 'Submit Property', 'propertyshift' ); ?></a></li>
		<?php } ?>
	<?php }

	/**
	 *	Add property sharing
	 */
	public function add_property_share() {
		$property_listing_display_share = esc_attr(get_option('ps_property_listing_display_share', 'true'));
		if(class_exists('NS_Basics_Post_Sharing') && $property_listing_display_share == 'true') {
			$post_share_obj = new NS_Basics_Post_Sharing();
			echo $post_share_obj->build_post_sharing_links();
		}
	}

	/**
	 *	Add property favoriting
	 */
	public function add_property_favoriting() {
		$property_listing_display_favorite = esc_attr(get_option('ps_property_listing_display_favorite', 'true'));
		if(class_exists('NS_Basics_Post_Likes') && $property_listing_display_favorite == 'true') {
			$post_likes_obj = new NS_Basics_Post_Likes();
			global $post;
			echo $post_likes_obj->get_post_likes_button($post->ID);
		}
	}


	/************************************************************************/
	// Register Widget Areas
	/************************************************************************/

	/**
	 *	Register properties sidebar
	 */
	public static function properties_sidebar_init() {
		register_sidebar( array(
	        'name' => esc_html__( 'Properties Sidebar', 'propertyshift' ),
	        'id' => 'properties_sidebar',
	        'before_widget' => '<div class="widget widget-sidebar widget-sidebar-properties %2$s">',
	        'after_widget' => '</div>',
	        'before_title' => '<h4>',
	        'after_title' => '</h4>',
	    ));
	}

}
?>