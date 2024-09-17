<?php
/*
Plugin Name: Quote of the Day
Description: A plugin to display the quote of the day with a calendar to pick previous dates.
Version: 1.0
Author: Tarek Nabil
*/
function qotd_enqueue_scripts() {
    wp_enqueue_script('qotd-quote-widget', plugin_dir_url(__FILE__) . 'quote-widget.js', array('jquery-ui-datepicker'), '1.0', true);    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/redmond/jquery-ui.css');
    wp_localize_script('qotd-quote-widget', 'qotd', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
function qotd_enqueue_widget_styles() {
    wp_enqueue_style('qotd-quote-widget', plugin_dir_url(__FILE__) . 'quote-widget.css');
}
add_action('wp_enqueue_scripts', 'qotd_enqueue_widget_styles');
add_action('wp_enqueue_scripts', 'qotd_enqueue_scripts');
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
// create custom post type for quotes and add custom field "quote_date". to store publication date

function qotd_create_quote_post_type() {
    register_post_type('qotd_cpt',
        array(
            'labels' => array(
                'name' => __('Quotes'),
                'singular_name' => __('Quote')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
        )
    );
}
add_action('init', 'qotd_create_quote_post_type');

function qotd_display_quote_of_the_day($atts) {
    $output = '<div class="quote-of-the-day">';
    $output .= '<div id="quote_date_picker"></div>';
    if (isset($_GET['quote_date'])) {
        $date = $_GET['quote_date'];

        $args = array(
            'post_type' => 'qotd_cpt',
            'posts_per_page' => 1,
            'date_query' => array(
                array(
                    'year'  => date('Y', strtotime($date)),
                    'month' => date('m', strtotime($date)),
                    'day'   => date('d', strtotime($date)),
                ),
            ),
        );
    
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $output .= '<div class="quote-content">';
                $output .= '<h2>' . get_the_title() . '</h2>';
                $output .= '<div>' . get_the_content() . '</div>';
                $output .= '</div>';
            }
        } else {
            $output .= '<p>No quote found for this date.</p>';
        }
        $output .= '<div id="quote_date_picker"></div>';
        return $output;

    } else {
        // get latest quote
        $args = array(
            'post_type' => 'qotd_cpt',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="quote-content">';
                $output .= '<h2>' . get_the_title() . '</h2>';
                $output .= '<div>' . get_the_content() . '</div>';
                $output .= '</div>';
            }
        } else {
            $output .= '<p>No quote found for this date.</p>';
        }

    }



    
    return $output;
}
add_shortcode('quote_of_the_day', 'qotd_display_quote_of_the_day');

// Include Elementor Widget
// Register Elementor widget
add_action('elementor/widgets/widgets_registered', 'qotd_register_elementor_widget');
function qotd_register_elementor_widget() {
    require_once plugin_dir_path(__FILE__) . 'widgets/class-elementor-quote-widget.php';
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \QOTD_OF_THE_DAY_Widget());
}

// ajax action handler
add_action('wp_ajax_qotd_get_quote_by_date', 'qotd_get_quote_by_date');
add_action('wp_ajax_nopriv_qotd_get_quote_by_date', 'qotd_get_quote_by_date');

function qotd_get_quote_by_date() {
    $date = $_POST['date'];
    $args = array(
        'post_type' => 'qotd_cpt',
        'posts_per_page' => 1,
        'date_query' => array(
            array(
                'year'  => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day'   => date('d', strtotime($date)),
            ),
        ),
    );
    
    $query = new WP_Query($args);
    $output = '<div class="quote-of-the-day">';
    $output .= '<div id="quote_date_picker"></div>';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<div class="quote-content">';
            $output .= '<h2>' . get_the_title() . '</h2>';
            $output .= '<div>' . get_the_content() . '</div>';
            $output .= '</div>';
        }
    } else {
        $output .= '<p>No quote found for this date.</p>';
    }
    wp_reset_postdata();
    echo $output;
    die();
}