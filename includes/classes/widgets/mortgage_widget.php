<?php
/**
 * Mortgage Calculator Widget Class
 */
class ns_real_estate_mortgage_calculator_widget extends WP_Widget {

    /** constructor */
    function __construct() {

        $widget_options = array(
          'classname'=>'mortgage-calculator',
          'description'=> esc_html__('Display a mortgage calculator.', 'ns-real-estate'),
          'panels_groups' => array('ns-real-estate')
        );
        parent::__construct('ns_real_estate_mortgage_calculator_widget', esc_html__('(Nightshift) Mortgage Calculator', 'ns-real-estate'), $widget_options);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        extract( $args );
        global $wpdb;

        $icon_set = get_option('ns_core_icon_set', 'fa');
        $title = apply_filters('widget_title', $instance['title']);

        ?>
              <?php echo wp_kses_post($before_widget); ?>
                  <?php if ( $title )
                        echo wp_kses_post($before_title . $title . $after_title); ?>

                        <div class="mortgage-calculator-container"></div>

              <?php echo wp_kses_post($after_widget); ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {  

        if (isset($instance['title'])) { $title = esc_attr($instance['title']); } else { $title = ''; }
        ?>

        <p>
           <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'ns-real-estate'); ?></label>
           <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>

        <?php
    }

} // class utopian_recent_posts
add_action('widgets_init', create_function('', 'return register_widget("ns_real_estate_mortgage_calculator_widget");'));

?>