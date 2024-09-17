<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class QOTD_OF_THE_DAY_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'quote_widget';
    }

    public function get_title() {
        return __( 'Quote of the Day', 'plugin-name' );
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function _register_controls() {
        // Add controls here if needed
    }

    protected function render() {
        echo do_shortcode('[quote_of_the_day]');
        
    }

    protected function _content_template() {}
}

function register_quote_widget() {
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor_Quote_Widget() );
}
