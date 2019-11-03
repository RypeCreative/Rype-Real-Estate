<?php global $current_user, $wp_roles; ?>    

<!-- start submit property -->
<div class="user-dashboard">
	<?php if(is_user_logged_in() && (current_user_can('ps_agent') || current_user_can('administrator'))) {

	//global settings
	$icon_set = esc_attr(get_option('ns_core_icon_set', 'fa'));
    if(function_exists('ns_core_load_theme_options')) { $icon_set = ns_core_load_theme_options('ns_core_icon_set'); }

    $admin_obj = new PropertyShift_Admin();
    $members_submit_property_fields = $admin_obj->load_settings(false, 'ps_members_submit_property_fields', false);
    if(empty($members_submit_property_fields)) { $members_submit_property_fields = array(); }
    $members_my_properties_page = $admin_obj->load_settings(false, 'ps_members_my_properties_page');
    $members_add_types = $admin_obj->load_settings(false, 'ps_members_add_types');
    $members_add_status = $admin_obj->load_settings(false, 'ps_members_add_status');
    $members_add_locations = $admin_obj->load_settings(false, 'ps_members_add_locations');
    $members_add_amenities = $admin_obj->load_settings(false, 'ps_members_add_amenities');
    $area_postfix_default = $admin_obj->load_settings(false, 'ps_default_area_postfix');

    // Load properties object
    $properties_obj = new PropertyShift_Properties();

	//intialize variables
	$errors = '';
	$success = '';

	//If editting property, get data and determine form action
	if (isset($_GET['edit_property']) && !empty($_GET['edit_property'])) {
	    $form_submit_text = esc_html__('Update Property', 'propertyshift');
	    $edit_property_id = $_GET['edit_property'];
	    $form_action = '?edit_property='.esc_attr($edit_property_id);

        $edit_property_settings = $properties_obj->load_property_settings($edit_property_id);
	    $edit_address = $edit_property_settings['street_address']['value'];
        $edit_price = $edit_property_settings['price']['value'];
        $edit_price_postfix = $edit_property_settings['price_postfix']['value'];
        $edit_bedrooms = $edit_property_settings['beds']['value'];
        $edit_bathrooms = $edit_property_settings['baths']['value'];
        $edit_garages = $edit_property_settings['garages']['value'];
        $edit_area = $edit_property_settings['area']['value'];
        $edit_area_postfix = $edit_property_settings['area_postfix']['value'];
        $edit_description = $edit_property_settings['description']['value'];
        $edit_floor_plans = $edit_property_settings['floor_plans']['value'];
        $edit_additional_images = $edit_property_settings['gallery']['value'];
        $edit_video_url = $edit_property_settings['video_url']['value'];
        $edit_video_img = $edit_property_settings['video_cover']['value'];
        $latitude = $edit_property_settings['latitude']['value'];
        $longitude = $edit_property_settings['longitude']['value'];
        $edit_agent_display = $edit_property_settings['owner_display']['value'];
        $edit_agent_select = $edit_property_settings['owner_display']['children']['agent']['value'];
        $edit_agent_custom_name = $edit_property_settings['owner_display']['children']['owner_custom_name']['value'];
        $edit_agent_custom_email = $edit_property_settings['owner_display']['children']['owner_custom_email']['value'];
        $edit_agent_custom_phone = $edit_property_settings['owner_display']['children']['owner_custom_phone']['value'];
        $edit_agent_custom_url = $edit_property_settings['owner_display']['children']['owner_custom_url']['value'];
        $edit_property_location = $properties_obj->get_tax($edit_property_id, 'property_location', true);
        $edit_property_amenities = $properties_obj->get_tax($edit_property_id, 'property_amenities', true);
        $edit_property_status = $properties_obj->get_tax($edit_property_id, 'property_status', true);
        $edit_property_type = $properties_obj->get_tax($edit_property_id, 'property_type', true);

    	//delete additional image
		if (!empty($_GET['additional_img_attachment_id'])) {
		    $image_to_delete = $_GET['additional_img_attachment_id'];
		    $image_to_delete_url = wp_get_attachment_url( $image_to_delete );
		    $new_edit_additional_images = explode(",", $edit_additional_images[0]);
		                    
		    $key = array_search($image_to_delete_url, $new_edit_additional_images);
		    if($key!==false) { unset($new_edit_additional_images[$key]); }

		    //update values
		    $strAdditionalImgs = implode(",", $new_edit_additional_images);
		    update_post_meta( $edit_property_id, 'ps_additional_img', $strAdditionalImgs );
		}
    	
	} else {
		$edit_property_id = '';
		$form_action = '';
		$form_submit_text = esc_html__('Submit Property', 'propertyshift');
	}

	//If form was submitted insert/update post
	if (!empty($_POST)) {

		if(isset($_GET['edit_property']) && !empty($_GET['edit_property'])) {
            $inserted_post = $properties_obj->insert_property_post($_GET['edit_property']);
    	} else {
            $inserted_post = $properties_obj->insert_property_post();
    	}
		$errors = $inserted_post['errors'];
		$success = $inserted_post['success'];
	} ?>

	<!-- start submit property form -->
	<div class="user-submit-property-form">

		<?php if ($success != '') { ?>
	        <div class="alert-box success"><h4><?php echo wp_kses_post($success); ?></h4><?php if (!empty($members_my_properties_page)) { echo '<a href="'.esc_url($members_my_properties_page).'">'. esc_html__('View your properties.', 'propertyshift').'</a>'; } ?></div>
	    <?php } ?>

	    <form method="post" action="<?php echo get_the_permalink().$form_action; ?>" enctype="multipart/form-data">

			<div class="submit-property-section" id="general-info">
		    	<h3><?php esc_html_e('General Info', 'propertyshift'); ?></h3>
		    	
                <div class="form-block form-block-property-title">
                    <?php if(isset($errors['title'])) { ?>
                        <div class="alert-box error"><h4><?php echo esc_attr($errors['title']); ?></h4></div>
                    <?php } ?>
                    <label><?php esc_html_e('Title*', 'propertyshift'); ?></label>
                    <input class="required border" type="text" name="title" value="<?php if(!empty($edit_property_id)) { echo get_the_title( $edit_property_id ); } else { echo esc_attr($_POST['title']); } ?>" />
                </div>

                <?php if(ns_basics_in_array_key('description', $members_submit_property_fields)) { ?>
                <div class="form-block form-block-property-description">
                    <label><?php esc_html_e('Description', 'propertyshift'); ?></label>
                    <?php 
                    $editor_id = 'propertydescription';
                    $settings = array('textarea_name' => 'description', 'editor_height' => 180, 'quicktags' => array('buttons' => ','));
                    wp_editor( $edit_description, $editor_id, $settings);
                    ?>
                </div>
                <?php } ?>

		    	<div class="row">
				<div class="col-lg-6 col-md-6">

					<div class="row form-block-property-price">
						<?php if(isset($errors['price'])) { ?>
						       <div class="col-lg-12"><div class="alert-box error"><h4><?php echo esc_attr($errors['price']); ?></h4></div></div>
						   <?php } ?>
						<div class="col-lg-6 col-md-6 form-block">
	                           <label><?php esc_html_e('Price*', 'propertyshift'); ?></label>
							<input class="required border" type="number" name="price" value="<?php if(isset($edit_price)) { echo $edit_price; } else { echo esc_attr($_POST['price']); } ?>" />
						</div>

                        <?php if(ns_basics_in_array_key('price_postfix', $members_submit_property_fields )) { ?>
						<div class="col-lg-6 col-md-6 form-block">
	                           <label><?php esc_html_e('Price Postfix', 'propertyshift'); ?></label>
							<input type="text" class="border" name="price_post" value="<?php if(isset($edit_price_postfix)) { echo $edit_price_postfix; } else { echo esc_attr($_POST['price_post']); } ?>" />
						</div>
                        <?php } ?>
					</div>

                    <?php if(ns_basics_in_array_key('beds', $members_submit_property_fields )) { ?>
					<div class="form-block form-block-property-beds">
	                    <label><?php esc_html_e('Bedrooms', 'propertyshift'); ?></label>
						<input type="number" class="border" name="beds" value="<?php if(isset($edit_bedrooms)) { echo $edit_bedrooms; } else { echo esc_attr($_POST['beds']); } ?>" />
					</div>
                    <?php } ?>

                    <?php if(ns_basics_in_array_key('baths', $members_submit_property_fields )) { ?>
					<div class="form-block form-block-property-baths">
	                    <label><?php esc_html_e('Bathrooms', 'propertyshift'); ?></label>
						<input type="number" class="border" name="baths" value="<?php if(isset($edit_bathrooms)) { echo $edit_bathrooms; } else { echo esc_attr($_POST['baths']); } ?>" />
					</div>
                    <?php } ?>

                    <?php if(ns_basics_in_array_key('garages', $members_submit_property_fields )) { ?>
					<div class="form-block form-block-property-garages">
	                    <label><?php esc_html_e('Garages', 'propertyshift'); ?></label>
						<input type="number" class="border" name="garages" value="<?php if(isset($edit_garages)) { echo $edit_garages; } else { echo esc_attr($_POST['garages']); } ?>" />
					</div>
                    <?php } ?>

					<div class="row form-block-property-area">
                        <?php if(ns_basics_in_array_key('area', $members_submit_property_fields )) { ?>
						<div class="col-lg-6 col-md-6 form-block">
                            <label><?php esc_html_e('Area', 'propertyshift'); ?></label>
							<input type="number" class="border" name="area" value="<?php if(isset($edit_area)) { echo $edit_area; } else { echo esc_attr($_POST['area']); } ?>" />
						</div>
                        <?php } ?>

                        <?php if(ns_basics_in_array_key('area_postfix', $members_submit_property_fields )) { ?>
						<div class="col-lg-6 col-md-6 form-block">
                            <label><?php esc_html_e('Area Postfix', 'propertyshift'); ?></label>
							<input type="text" class="border" name="area_post" value="<?php if(isset($edit_area_postfix)) { echo $edit_area_postfix; } else if($_POST['area_post']) { echo esc_attr($_POST['area_post']); } else { echo $area_postfix_default; } ?>" />
						</div>
                        <?php } ?>
					</div>

                    <?php if(ns_basics_in_array_key('video', $members_submit_property_fields )) { ?>
					<div class="form-block form-block-property-video-url">
                        <label><?php esc_html_e('Video URL', 'propertyshift'); ?></label>
						<input type="text" class="border" name="video_url" value="<?php if(isset($edit_video_url)) { echo $edit_video_url; } else { echo esc_url($_POST['video_url']); } ?>" />
					</div>

					<div class="form-block form-block-property-video-img">
                        <label><?php esc_html_e('Video Cover Image', 'propertyshift'); ?></label>
						<input type="text" class="border" name="video_img" value="<?php if(isset($edit_video_img)) { echo $edit_video_img; } else { echo esc_url($_POST['video_img']); } ?>" />
					</div>
                    <?php }  ?>

				</div><!-- end col -->

				<div class="col-lg-6 col-md-6">
					<div class="form-block form-block-property-address">
                        <?php if(isset($errors['address'])) { ?>
                            <div class="alert-box error"><h4><?php echo esc_attr($errors['address']); ?></h4></div>
                        <?php } ?>
                        <label><?php esc_html_e('Street Address*', 'propertyshift'); ?></label>
                        <input class="required border" type="text" name="street_address" value="<?php if(isset($edit_address)) { echo $edit_address; } else { echo esc_attr($_POST['street_address']); } ?>" />
                    </div>

                    <?php if(ns_basics_in_array_key('property_location', $members_submit_property_fields )) { ?>
                    <div class="form-block form-block-property-location border">
                        <label for="property-location"><?php esc_html_e('Property Location', 'propertyshift'); ?></label>
                        <select data-placeholder="<?php esc_html_e('Select a location...', 'propertyshift'); ?>" name="property_location[]" id="property-location" multiple>
                            <?php
                            $property_locations = get_terms('property_location', array( 'hide_empty' => false, 'parent' => 0 )); 
                            if ( !empty( $property_locations ) && !is_wp_error( $property_locations ) ) { ?>
                                <?php foreach ( $property_locations as $property_location ) { ?>
                                    <option value="<?php echo esc_attr($property_location->slug); ?>" <?php if(isset($edit_property_location) && in_array($property_location->slug, $edit_property_location)) { echo 'selected'; } else if(isset($_POST['property_location']) && in_array($property_location->slug, $_POST['property_location'])) { echo 'selected'; } ?>><?php echo esc_attr($property_location->name); ?></option>
                                    <?php 
                                        $term_children = get_term_children($property_location->term_id, 'property_location'); 
                                        if(!empty($term_children)) {
                                            echo '<optgroup>';
                                            foreach ( $term_children as $child ) {
                                                $term = get_term_by( 'id', $child, 'property_location' ); ?>
                                                <option value="<?php echo $term->slug; ?>" <?php if(isset($edit_property_location) && in_array($term->slug, $edit_property_location)) { echo 'selected'; } else if(isset($_POST['property_location']) && in_array($term->slug, $_POST['property_location'])) { echo 'selected'; } ?>><?php echo $term->name; ?></option>
                                            <?php }
                                            echo '</optgroup>';
                                        }
                                    ?>
                                <?php } ?>
                            <?php } ?>
                        </select>

                        <?php if($members_add_locations == 'true') { ?>
                        <div class="property-add-tax-form property-location-new">
                            <span class="property-location-new-toggle note"><?php esc_html_e("Don't see your location?", 'propertyshift'); ?> <a href="#"><?php esc_html_e('Add a new one.', 'propertyshift'); ?></a></span>
                            <div class="property-location-new-content show-none">
                                <input class="border" type="text" placeholder="Location name" />
                                <a href="#" class="button"><?php echo ns_core_get_icon($icon_set, 'plus', 'plus'); ?> <?php esc_html_e('Add', 'propertyshift'); ?></a>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <?php } ?>
		            </div>
                    <?php } ?>

                    <?php if(ns_basics_in_array_key('amenities', $members_submit_property_fields )) { ?>
		            <div class="form-block form-block-property-amenities border">
                        <label for="property-amenities"><?php esc_html_e('Amenities', 'propertyshift'); ?></label>
                        <select data-placeholder="<?php esc_html_e('Select an amenity...', 'propertyshift'); ?>" name="property_amenities[]" id="property-amenities" multiple>
                            <?php
                            $property_amenities = get_terms('property_amenities', array( 'hide_empty' => false, 'parent' => 0 )); 
                            if ( !empty( $property_amenities ) && !is_wp_error( $property_amenities ) ) { ?>
                                <?php foreach ( $property_amenities as $property_amenity ) { ?>
                                    <option value="<?php echo esc_attr($property_amenity->slug); ?>" <?php if(isset($edit_property_amenities) && in_array($property_amenity->slug, $edit_property_amenities)) { echo 'selected'; } else if(isset($_POST['property_amenities']) && in_array($property_amenity->slug, $_POST['property_amenities'])) { echo 'selected'; } ?>><?php echo esc_attr($property_amenity->name); ?></option>
                                    <?php 
                                        $term_children = get_term_children($property_amenity->term_id, 'property_amenities'); 
                                        if(!empty($term_children)) {
                                            echo '<optgroup>';
                                            foreach ( $term_children as $child ) {
                                                $term = get_term_by( 'id', $child, 'property_amenities' ); ?>
                                                <option value="<?php echo $term->slug; ?>" <?php if(isset($edit_property_amenities) && in_array($term->slug, $edit_property_amenities)) { echo 'selected'; } else if(isset($_POST['property_amenities']) && in_array($term->slug, $_POST['property_amenities'])) { echo 'selected'; } ?>><?php echo $term->name; ?></option>
                                            <?php }
                                            echo '</optgroup>';
                                        }
                                    ?>
                                <?php } ?>
                            <?php } ?>
                        </select>

                        <?php if($members_add_amenities == 'true') { ?>
                        <div class="property-add-tax-form property-location-new">
                            <span class="property-location-new-toggle note"><?php esc_html_e("Don't see your amenity?", 'propertyshift'); ?> <a href="#"><?php esc_html_e('Add a new one.', 'propertyshift'); ?></a></span>
                            <div class="property-location-new-content show-none">
                                <input class="border" type="text" placeholder="Location name" />
                                <a href="#" class="button"><?php echo ns_core_get_icon($icon_set, 'plus', 'plus'); ?> <?php esc_html_e('Add', 'propertyshift'); ?></a>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <?php } ?>
		            </div>
                    <?php } ?>

                    <?php if(ns_basics_in_array_key('property_type', $members_submit_property_fields )) { ?>
		            <div class="form-block form-block-property-type border">
                        <label for="property-type"><?php esc_html_e('Property Type', 'propertyshift'); ?></label>
		                <select name="property_type" id="property-type">
		                    <option value=""><?php esc_html_e('Select a property type...', 'propertyshift'); ?></option>
		                    <?php 
		                        $property_type_terms = get_terms('property_type', array('hide_empty' => false)); 
		                        foreach ( $property_type_terms as $property_type_term ) { ?>
		                            <option value="<?php echo esc_attr($property_type_term->slug); ?>" <?php if(isset($edit_property_type)) { if(in_array($property_type_term->slug, $edit_property_type)) { echo 'selected'; } } else if(isset($_POST['property_type'])) { if($_POST['property_type'] == $property_type_term->slug) { echo 'selected'; } } ?>><?php echo esc_attr($property_type_term ->name); ?></option>;
		                    <?php } ?>
		                </select>

                        <?php if($members_add_types == 'true') { ?>
                        <div class="property-add-tax-form property-type-new">
                            <span class="property-location-new-toggle note"><?php esc_html_e("Don't see your type?", 'propertyshift'); ?> <a href="#"><?php esc_html_e('Add a new one.', 'propertyshift'); ?></a></span>
                            <div class="property-location-new-content show-none">
                                <input class="border" type="text" placeholder="Type name" />
                                <a href="#" class="button"><?php echo ns_core_get_icon($icon_set, 'plus', 'plus'); ?> <?php esc_html_e('Add', 'propertyshift'); ?></a>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <?php } ?>

		            </div>
                    <?php } ?>

                    <?php if(ns_basics_in_array_key('property_status', $members_submit_property_fields )) { ?>
					<div class="form-block form-block-property-status border">
                        <label for="contract-type"><?php esc_html_e('Contract Type', 'propertyshift'); ?></label>
		                <select name="contract_type" id="contract-type">
		                    <option value=""><?php esc_html_e('Select a contract type...', 'propertyshift'); ?></option>
		                    <?php 
		                        $property_status_terms = get_terms('property_status', array('hide_empty' => false)); 
		                        foreach ( $property_status_terms as $property_status_term ) { ?>
		                            <option value="<?php echo esc_attr($property_status_term->slug); ?>" <?php if(isset($edit_property_status)) { if(in_array($property_status_term->slug, $edit_property_status)) { echo 'selected'; } } else if(isset($_POST['contract_type'])) { if($_POST['contract_type'] == $property_status_term->slug) { echo 'selected'; } } ?>><?php echo esc_attr($property_status_term ->name); ?></option>;
		                    <?php } ?>
		                </select>

                        <?php if($members_add_status == 'true') { ?>
                        <div class="property-add-tax-form property-status-new">
                            <span class="property-location-new-toggle note"><?php esc_html_e("Don't see your status?", 'propertyshift'); ?> <a href="#"><?php esc_html_e('Add a new one.', 'propertyshift'); ?></a></span>
                            <div class="property-location-new-content show-none">
                                <input class="border" type="text" placeholder="Type name" />
                                <a href="#" class="button"><?php echo ns_core_get_icon($icon_set, 'plus', 'plus'); ?> <?php esc_html_e('Add', 'propertyshift'); ?></a>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <?php } ?>
		            </div>
                    <?php } ?>

				</div><!-- end col -->
				</div><!-- end row -->
			</div><!-- end general info -->

            <?php do_action('propertyshift_after_property_submit_general', $edit_property_settings); ?>

            <?php if(ns_basics_in_array_key('floor_plans', $members_submit_property_fields )) { ?>
            <div class="submit-property-section" id="property-floor-plans">
                <div class="form-block-property-floor-plans">
                    <h3><?php esc_html_e('Floor Plans', 'propertyshift'); ?></h3>
                    <div class="form-block property-floor-plans">
                        <div class="accordion">
                            <?php 
                            if(!empty($edit_floor_plans) && !empty($edit_floor_plans[0])) { 
                                $count = 0;                     
                                foreach ($edit_floor_plans as $floor_plan) { ?>
                                    <h4 class="accordion-tab"><span class="floor-plan-title-mirror"><?php echo $floor_plan['title']; ?></span> <span class="delete-floor-plan right"><i class="fa fa-trash"></i> <?php esc_html_e('Delete', 'propertyshift'); ?></span></h4>
                                    <div class="floor-plan-item"> 
                                        <div class="floor-plan-left"> 
                                            <label><?php esc_html_e('Title:', 'propertyshift'); ?> </label> <input class="border floor-plan-title" type="text" name="ps_property_floor_plans[<?php echo $count; ?>][title]" placeholder="<?php esc_html_e('New Floor Plan', 'propertyshift'); ?>" value="<?php echo $floor_plan['title']; ?>" /><br/>
                                            <label><?php esc_html_e('Size:', 'propertyshift'); ?> </label> <input class="border" type="text" name="ps_property_floor_plans[<?php echo $count; ?>][size]" value="<?php echo $floor_plan['size']; ?>" /><br/>
                                            <label><?php esc_html_e('Rooms:', 'propertyshift'); ?> </label> <input class="border" type="number" name="ps_property_floor_plans[<?php echo $count; ?>][rooms]" value="<?php echo $floor_plan['rooms']; ?>" /><br/>
                                            <label><?php esc_html_e('Bathrooms:', 'propertyshift'); ?> </label> <input class="border" type="number" name="ps_property_floor_plans[<?php echo $count; ?>][baths]" value="<?php echo $floor_plan['baths']; ?>" /><br/>
                                        </div>
                                        <div class="floor-plan-right">
                                            <label><?php esc_html_e('Description:', 'propertyshift'); ?></label>
                                            <textarea class="border" name="ps_property_floor_plans[<?php echo $count; ?>][description]"><?php echo $floor_plan['description']; ?></textarea>
                                            <div>
                                                <label><?php esc_html_e('Image', 'propertyshift'); ?></label>
                                                <input class="border" type="text" name="ps_property_floor_plans[<?php echo $count; ?>][img]" value="<?php echo $floor_plan['img']; ?>" />
                                                <span><em><?php esc_html_e('Provide the absolute url to a hosted image.', 'propertyshift'); ?></em></span>
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                    </div> 
                                    <?php $count++; ?>
                                <?php }
                            }
                            ?>
                        </div>
                        <div class="button light small add-floor-plan"><i class="fa fa-plus"></i> <?php esc_html_e('Create New Floor Plan', 'propertyshift'); ?></div>
                    </div>
                </div>
            </div><!-- end floor plans -->
            <?php } ?>

            <?php if(ns_basics_in_array_key('featured_image', $members_submit_property_fields) || ns_basics_in_array_key('gallery_images', $members_submit_property_fields)) { ?>
			<div class="submit-property-section" id="property-images">
				<h3><?php esc_html_e('Property Images', 'propertyshift'); ?></h3>

                <?php if(ns_basics_in_array_key('featured_image', $members_submit_property_fields )) { ?>
				<div class="form-block featured-img">
					<?php if(isset($edit_property_id) && !empty($edit_property_id)) { echo get_the_post_thumbnail( $edit_property_id, 'thumbnail', array( 'class' => 'featured-img' ) ); } ?>
	                <label for="featured_img"><?php esc_html_e('Featured Image', 'propertyshift'); ?></label><br/>
	                <input id="featured_img" name="featured_img" type="file">
	            </div>
                <?php } ?>

                <?php if(ns_basics_in_array_key('gallery_images', $members_submit_property_fields )) { ?>
	            <div class="form-block">
	            	<label><?php esc_html_e('Gallery Images', 'propertyshift'); ?></label>
	            	
	            	<div class="additional-img-container">

	            		<?php 
		            	if(isset($edit_additional_images) && !empty($edit_additional_images)) {
		            		foreach ($edit_additional_images as $edit_additional_image) {
		            			if(!empty($edit_additional_image)) {
			            			$additional_img_attachment_id = propertyshift_get_attachment_id_by_url($edit_additional_image); ?>
			            			<table>
			            				<tr>
			            				<td>
			            				<div class="media-uploader-additional-img">
		                        		<img class="additional-img-preview" src="<?php echo $edit_additional_image; ?>" alt="" />
		                        		<a href="<?php echo get_the_permalink().'?edit_property='.$edit_property_id.'&additional_img_attachment_id='.$additional_img_attachment_id; ?>" class="delete-additional-img right"><i class="fa fa-trash"></i> <?php esc_html_e('Delete', 'propertyshift'); ?></a>
		                        		</div>
			            				</td>
			            				</tr>
			            			</table>
			            		<?php }
		            		}
		            	} ?>

	            		<table>
	                        <tr>
	                        <td>
	                        <div class="media-uploader-additional-img">
	                        <input type="file" class="additional_img" name="additional_img1" value="" />
	                        <span class="delete-additional-img right"><i class="fa fa-trash"></i> <?php esc_html_e('Delete', 'propertyshift'); ?></span>
	                        </div>
	                        </td>
	                        </tr>
	                    </table>
	                </div>
	                <span class="button light small add-additional-img"><i class="fa fa-plus"></i>  <?php esc_html_e('Add Image', 'propertyshift'); ?></span>
	            </div>
                <?php } ?>

			</div><!-- end property images -->
            <?php } ?>

            <?php if(ns_basics_in_array_key('map', $members_submit_property_fields )) { ?>
			<div class="submit-property-section" id="map">
            	<h3><?php esc_html_e('Map', 'propertyshift'); ?></h3>
            	<div class="left">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 form-block">
                            <input type="text" class="border" name="latitude" id="property_latitude" placeholder="<?php esc_html_e('Latitude', 'propertyshift'); ?>" value="<?php if(isset($latitude)) { echo $latitude; } else { echo esc_attr($_POST['latitude']); } ?>" />
                        </div>

                        <div class="col-lg-6 col-md-6 form-block">
                            <input type="text" class="border" name="longitude" id="property_longitude" placeholder="<?php esc_html_e('Longitude', 'propertyshift'); ?>" value="<?php if(isset($longitude)) { echo $longitude; } else { echo esc_attr($_POST['longitude']); } ?>" />
                        </div>
                    </div>
            	</div>
            	<?php
                $maps_obj = new PropertyShift_Maps();
                $maps_obj->build_single_property_map($latitude, $longitude);
                ?>
            </div>
            <?php } ?>

	        <input type="submit" class="button alt right" value="<?php echo $form_submit_text; ?>" />
	    </form>

	</div><!-- end form container -->
	
	<?php } else {
        ns_basics_template_loader('alert_not_logged_in.php', null, false);
    } ?>
</div><!-- end submit property -->