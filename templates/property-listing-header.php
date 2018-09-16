<?php
global $wp;
$currentUrl = home_url( $wp->request );
$icon_set = esc_attr(get_option('rypecore_icon_set', 'fa'));

//Get all current filters from URL
$currentFilters = array();
foreach($_GET as $key=>$value) { if(!empty($value)) { $currentFilters[$key] = $value; } }
if(!array_key_exists("advancedSearch",$currentFilters)) { $currentFilters = null; }
?>

<div class="property-listing-header">
	<span class="property-count left">
		<?php echo esc_attr($property_listing_query->found_posts); ?> <?php esc_html_e('properties found', 'rypecore'); ?>
		<?php if(!empty($currentFilters)) { echo '<a href="'.get_the_permalink().'" class="button small outline clear-property-filters">'.rypecore_get_icon($icon_set, 'times', 'cross', 'cross').esc_html__('Clear Filters', 'rypecore').'</a>'; } ?>
	</span>
	<form action="<?php echo $currentUrl; ?>" method="get" class="right">
		<select name="sort_by" onchange="this.form.submit();">
			<option value="date_desc" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'date_desc') { echo 'selected'; } ?>><?php esc_html_e('New to Old', 'rypecore'); ?></option>
			<option value="date_asc" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'date_asc') { echo 'selected'; } ?>><?php esc_html_e('Old to New', 'rypecore'); ?></option>
			<option value="price_desc" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'price_desc') { echo 'selected'; } ?>><?php esc_html_e('Price (High to Low)', 'rypecore'); ?></option>
			<option value="price_asc" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'price_asc') { echo 'selected'; } ?>><?php esc_html_e('Price (Low to High)', 'rypecore'); ?></option>
		</select>
		<?php
		foreach($_GET as $name => $value) {
			if($name != 'sort_by') {
				$name = htmlspecialchars($name);
				$value = htmlspecialchars($value);
				echo '<input type="hidden" name="'. $name .'" value="'. $value .'">';
			}
		}
		?>
	</form>
	<div class="property-layout-toggle right">
		<?php
		$query_string = '';
		foreach($_GET as $name => $value) {
			if($name != 'property_layout' && $name != 'custom_layout') {
				$name = htmlspecialchars($name);
				$value = htmlspecialchars($value);
				$query_string .= '&'.$name.'='.$value;
			}
		}
		?>
		<a href="<?php echo $currentUrl; ?><?php if(isset($custom_layout)) { echo '?custom_layout=grid'; } else { echo '?property_layout=grid'; } ?><?php echo wp_kses_post($query_string); ?>" class="property-layout-toggle-item <?php if($property_layout == 'grid') { echo 'active'; } ?>"><i class="fa fa-th-large"></i></a>
		<a href="<?php echo $currentUrl; ?><?php if(isset($custom_layout)) { echo '?custom_layout=row'; } else { echo '?property_layout=row'; } ?><?php echo wp_kses_post($query_string); ?>" class="property-layout-toggle-item <?php if($property_layout == 'row') { echo 'active'; } ?>"><i class="fa fa-bars"></i></a>
	</div>
	<div class="clear"></div>
</div>