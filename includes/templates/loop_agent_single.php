<?php
    //Global settings
    $num_properties_per_page = esc_attr(get_option('ns_num_properties_per_page', 12));
    $icon_set = esc_attr(get_option('ns_core_icon_set', 'fa'));
    if(function_exists('ns_core_load_theme_options')) { $icon_set = ns_core_load_theme_options('ns_core_icon_set'); }
    $agent_detail_items_default = ns_real_estate_load_default_agent_detail_items();
    $agent_detail_items = get_option('ns_agent_detail_items', $agent_detail_items_default);

    //Get template location
    if(isset($template_args)) { $template_location = $template_args['location']; } else { $template_location = ''; }
    if($template_location == 'sidebar') { 
        $template_location_sidebar = 'true'; 
    } else { 
        $template_location_sidebar = 'false';
    }

	//Get agent details
    $agent_details_values = get_post_custom( $post->ID );
	$agent_title = isset( $agent_details_values['ns_agent_title'] ) ? esc_attr( $agent_details_values['ns_agent_title'][0] ) : '';
	$agent_email = isset( $agent_details_values['ns_agent_email'] ) ? esc_attr( $agent_details_values['ns_agent_email'][0] ) : '';
	$agent_mobile_phone = isset( $agent_details_values['ns_agent_mobile_phone'] ) ? esc_attr( $agent_details_values['ns_agent_mobile_phone'][0] ) : '';
	$agent_office_phone = isset( $agent_details_values['ns_agent_office_phone'] ) ? esc_attr( $agent_details_values['ns_agent_office_phone'][0] ) : '';
	$agent_description = isset( $agent_details_values['ns_agent_description'] ) ? $agent_details_values['ns_agent_description'][0] : '';
    $agent_fb = isset( $agent_details_values['ns_agent_fb'] ) ? esc_attr( $agent_details_values['ns_agent_fb'][0] ) : '';
	$agent_twitter = isset( $agent_details_values['ns_agent_twitter'] ) ? esc_attr( $agent_details_values['ns_agent_twitter'][0] ) : '';
	$agent_google = isset( $agent_details_values['ns_agent_google'] ) ? esc_attr( $agent_details_values['ns_agent_google'][0] ) : '';
	$agent_linkedin = isset( $agent_details_values['ns_agent_linkedin'] ) ? esc_attr( $agent_details_values['ns_agent_linkedin'][0] ) : '';
	$agent_youtube = isset( $agent_details_values['ns_agent_youtube'] ) ? esc_attr( $agent_details_values['ns_agent_youtube'][0] ) : '';
	$agent_instagram = isset( $agent_details_values['ns_agent_instagram'] ) ? esc_attr( $agent_details_values['ns_agent_instagram'][0] ) : '';
    $agent_form_source = isset( $agent_details_values['ns_agent_form_source'] ) ? esc_attr( $agent_details_values['ns_agent_form_source'][0] ) : 'default';
    $agent_form_id = isset( $agent_details_values['ns_agent_form_id'] ) ? esc_attr( $agent_details_values['ns_agent_form_id'][0] ) : '';

    //Get agent property count
    $agent_properties = ns_real_estate_get_agent_properties(get_the_id(), $num_properties_per_page);
    $agent_properties_count = $agent_properties['count'];
?>	

	<?php if (!empty($agent_detail_items)) { 
		foreach($agent_detail_items as $value) { ?>

				<?php
                    if(isset($value['name'])) { $name = $value['name']; }
                    if(isset($value['label'])) { $label = $value['label']; }
                    if(isset($value['slug'])) { $slug = $value['slug']; }
                    if(isset($value['active']) && $value['active'] == 'true') { $active = 'true'; } else { $active = 'false'; }
                    if(isset($value['sidebar']) && $value['sidebar'] == 'true') { $sidebar = 'true'; } else { $sidebar = 'false'; }
                ?>

                <?php if($active == 'true' && ($sidebar == $template_location_sidebar)) { ?>
					
                	<?php if($slug == 'overview') { ?>
                    <!--******************************************************-->
                    <!-- OVERVIEW -->
                    <!--******************************************************-->
                	<div class="agent-single-item ns-single-item widget agent-<?php echo esc_attr($slug); ?>">

                        <a href="<?php the_permalink(); ?>" class="agent-img">
                            <?php if(isset($agent_properties_count) && $agent_properties_count > 0) { ?>
                                <div class="button alt button-icon agent-tag agent-assigned"><?php echo ns_core_get_icon($icon_set, 'home'); ?><?php echo esc_attr($agent_properties_count); ?> <?php if($agent_properties_count <= 1) { esc_html_e('Assigned Property', 'ns-real-estate'); } else { esc_html_e('Assigned Properties', 'ns-real-estate'); } ?></div>
                            <?php } ?>
                            <?php if ( has_post_thumbnail() ) {  ?>
                                <div class="img-fade"></div>
                                <?php the_post_thumbnail('full'); ?>
                            <?php } else { ?>
                                <img src="<?php echo plugins_url( '/ns-real-estate/images/agent-img-default.gif' ); ?>" alt="" />
                            <?php } ?>
                        </a>

                        <div class="agent-content">
                            <div class="agent-details">
        	                	<?php if(!empty($agent_title)) { ?><p><span><?php echo esc_attr($agent_title); ?></span><?php echo ns_core_get_icon($icon_set, 'tag'); ?><?php esc_html_e('Title', 'ns-real-estate'); ?>:</p><?php } ?>
        	                	<?php if(!empty($agent_email)) { ?><p><span><?php echo esc_attr($agent_email); ?></span><?php echo ns_core_get_icon($icon_set, 'envelope', 'envelope', 'mail'); ?><?php esc_html_e('Email', 'ns-real-estate'); ?>:</p><?php } ?>
        	                	<?php if(!empty($agent_mobile_phone)) { ?><p><span><?php echo esc_attr($agent_mobile_phone); ?></span><?php echo ns_core_get_icon($icon_set, 'phone', 'telephone'); ?><?php esc_html_e('Mobile', 'ns-real-estate'); ?>:</p><?php } ?>
        	                	<?php if(!empty($agent_office_phone)) { ?><p><span><?php echo esc_attr($agent_office_phone); ?></span><?php echo ns_core_get_icon($icon_set, 'building', 'apartment', 'briefcase'); ?><?php esc_html_e('Office', 'ns-real-estate'); ?>:</p><?php } ?>
                                <?php do_action('ns_real_estate_after_agent_details', $post->ID); ?>
                            </div>
                            <?php if(in_array('agent_detail_item_contact', $agent_detail_items)) { ?> 
                                <div class="button button-icon agent-message right"><?php echo ns_core_get_icon($icon_set, 'envelope'); ?><?php esc_html_e('Message Agent', 'ns-real-estate'); ?></div>
                            <?php } ?>
                            <?php if(!empty($agent_fb) || !empty($agent_twitter) || !empty($agent_google) || !empty($agent_linkedin) || !empty($agent_youtube) || !empty($agent_instagram)) { ?>
                            <div class="center">
                                <ul class="social-icons circle clean-list">
                                    <?php if(!empty($agent_fb)) { ?><li class="agent-footer-item"><a href="<?php echo esc_url($agent_fb); ?>" target="_blank"><i class="fab fa-facebook"></i></a></li><?php } ?>
                                    <?php if(!empty($agent_twitter)) { ?><li class="agent-footer-item"><a href="<?php echo esc_url($agent_twitter); ?>" target="_blank"><i class="fab fa-twitter"></i></a></li><?php } ?>
                                    <?php if(!empty($agent_google)) { ?><li class="agent-footer-item"><a href="<?php echo esc_url($agent_google); ?>" target="_blank"><i class="fab fa-google-plus"></i></a></li><?php } ?>
                                    <?php if(!empty($agent_linkedin)) { ?><li class="agent-footer-item"><a href="<?php echo esc_url($agent_linkedin); ?>" target="_blank"><i class="fab fa-linkedin"></i></a></li><?php } ?>
                                    <?php if(!empty($agent_youtube)) { ?><li class="agent-footer-item"><a href="<?php echo esc_url($agent_youtube); ?>" target="_blank"><i class="fab fa-youtube"></i></a></li><?php } ?>
                                    <?php if(!empty($agent_instagram)) { ?><li class="agent-footer-item"><a href="<?php echo esc_url($agent_instagram); ?>" target="_blank"><i class="fab fa-instagram"></i></a></li><?php } ?>
                                </ul>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="clear"></div>
	                </div>
                	<?php } ?>

                	<?php if($slug == 'description' && !empty($agent_description)) { ?>
                    <!--******************************************************-->
                    <!-- DESCRIPTION -->
                    <!--******************************************************-->
                		<div class="agent-single-item ns-single-item content widget agent-<?php echo esc_attr($slug); ?>">
                			<?php if(!empty($label)) { ?>
                                <div class="module-header module-header-left">
                                    <h4><?php echo esc_attr($label); ?></h4>
                                    <div class="widget-divider"><div class="bar"></div></div>
                                </div>
                            <?php } ?>
                			<?php echo $agent_description; ?>
                		</div>
                	<?php } ?>

                	<?php if($slug == 'contact') { ?>
                    <!--******************************************************-->
                    <!-- CONTACT -->
                    <!--******************************************************-->
                        <a class="anchor" name="anchor-agent-contact"></a>
                		<div class="agent-single-item ns-single-item widget agent-<?php echo esc_attr($slug); ?>">
                			<?php if(!empty($label)) { ?>
                                <div class="module-header module-header-left">
                                    <h4><?php echo esc_attr($label); ?></h4>
                                    <div class="widget-divider"><div class="bar"></div></div>
                                </div>
                            <?php } ?>
                            <?php
                                if($agent_form_source == 'contact-form-7') {
                                    $agent_form_title = get_the_title( $agent_form_id );
                                    echo do_shortcode('[contact-form-7 id="<?php echo esc_attr($agent_form_id); ?>" title="'.$agent_form_title.'"]');
                                } else { 
                                    if(function_exists('ns_real_estate_agent_contact_form')) {
                                        ns_real_estate_agent_contact_form($agent_email); 
                                    } else {
                                        esc_html_e('Please install required plugins to display the contact form.', 'ns-real-estate');
                                    }
                                } 
                            ?>
                		</div>
                	<?php } ?>

                	<?php if($slug == 'properties') { ?>
                    <!--******************************************************-->
                    <!-- AGENT PROPERTIES -->
                    <!--******************************************************-->
                        <a class="anchor" name="anchor-agent-properties"></a>
                		<div class="agent-single-item ns-single-item widget agent-<?php echo esc_attr($slug); ?>">
                		    <?php if(!empty($label)) { ?>
                                <div class="module-header module-header-left">
                                    <h4><?php echo esc_attr($label); ?></h4>
                                    <div class="widget-divider"><div class="bar"></div></div>
                                </div>
                            <?php } ?>
                	        <?php 
                                //Set template args
                                $template_args_properties = array();
                                $template_args_properties['custom_args'] = $agent_properties['args'];
                                $template_args_properties['custom_show_filter'] = false;
                                $template_args_properties['custom_layout'] = 'grid';
                                $template_args_properties['custom_pagination'] = true;
                                if($template_location_sidebar == 'true') { $template_args_properties['custom_cols'] = 1; }
                                $template_args_properties['no_post_message'] = esc_html__( 'Sorry, no properties were found.', 'ns-real-estate' );
                                
                                //Load template
                                ns_real_estate_template_loader('loop_properties.php', $template_args_properties);
                            ?>
                        </div>
                	<?php } ?>

                <?php } ?>

        <?php } //end foreach ?>
	<?php } ?>