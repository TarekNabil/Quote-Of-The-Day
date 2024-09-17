<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Elementor_Quote_Widget extends \Elementor\Widget_Base {

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
        echo '<input type="date" id="quote_date_picker">';
        echo '<button onclick="fetchQuote()">Get Quote</button>';
    }

    protected function _content_template() {}
}

function register_quote_widget() {
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor_Quote_Widget() );
}
add_action('elementor/widgets/widgets_registered', 'register_quote_widget');