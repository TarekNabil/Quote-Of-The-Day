<?php
/*
Plugin Name: Quote of the Day
Description: A plugin to display the quote of the day with a calendar to pick all dates.
Version: 1.0
Author: Tarek Nabil
*/
function qotd_enqueue_scripts() {
    wp_enqueue_script('qotd-quote-widget', plugin_dir_url(__FILE__) . 'quote-widget.js', array('jquery-ui-datepicker'), '2.6.6', true);
	wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/flick/jquery-ui.css');
    wp_localize_script('qotd-quote-widget', 'qotd', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
function qotd_enqueue_widget_styles() {
    wp_enqueue_style('qotd-quote-widget', plugin_dir_url(__FILE__) . 'quote-widget.css', array(), '1.1.5');
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

function qotd_add_import_submenu() {
    add_submenu_page(
        'edit.php?post_type=qotd_cpt', // Parent slug
        'Import Quotes',               // Page title
        'Import',                      // Menu title
        'manage_options',              // Capability
        'qotd_import',                 // Menu slug
        'qotd_import_page_callback'    // Callback function
    );
}
add_action('admin_menu', 'qotd_add_import_submenu');


function qotd_import_page_callback() {
    ?>
    <div class="wrap">
        <h1>Import Quotes</h1>
        <p>Please upload a CSV file with the following format:</p>
        <ul>
            <li><strong>Date</strong> - The publication date of the quote (e.g., 2023-10-01).</li>
            <li><strong>Quote</strong> - The content of the quote in the default site language.</li>
            <li><strong>Translation</strong> - The translation of the quote - add language code as a header (optional).</li>
        </ul>
        <p><strong>Warning:</strong> Incorrect file format may result in failed imports.</p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('import_quotes', 'qotd_import_nonce'); ?>
            <input type="file" name="quotes_csv" accept=".csv">
            <input type="hidden" name="import_quotes" value="1">
            <?php submit_button('Import Quotes'); ?>
        </form>
    </div>
    <?php

    if (isset($_POST['import_quotes']) && check_admin_referer('import_quotes', 'qotd_import_nonce')) {
        if (isset($_FILES['quotes_csv']) && $_FILES['quotes_csv']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['quotes_csv']['tmp_name'];
            qotd_import_quotes_from_csv($file);
        }
    }
}

function qotd_import_quotes_from_csv($file) {
    if (($handle = fopen($file, 'r')) !== FALSE) {
        $imported_quotes = array();
        $failed_quotes = array();
        $is_first_row = true; // Flag to check if it's the first row
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if ($is_first_row) {
                $header_date = $data[0];
                $header_content = $data[1];
                $header_translation = isset($data[2]) ? $data[2] : '';
                $is_first_row = false; // Skip the first row (header)
                continue;
            }
            $quote = sanitize_text_field($data[1]);
            $title = sanitize_text_field($data[0]);
            $pub_date = sanitize_text_field($data[0]);

            // Convert the publication date to the correct format
            $pub_date = date('Y-m-d H:i:s', strtotime($pub_date));

            // Insert the original quote into the database
            $original_post_id = wp_insert_post(array(
                'post_title' => $title,
                'post_content' => $quote,
                'post_status' => 'publish',
                'post_type' => 'qotd_cpt',
                'post_date' => $pub_date
            ));

            if (is_wp_error($original_post_id)) {
                $failed_quotes[] = array('title' => $title, 'error' => $original_post_id->get_error_message());
            } else {
                $imported_quotes[] = get_the_title($original_post_id);
            }

            // Check if WPML is installed and active
            if (defined('ICL_SITEPRESS_VERSION') && !empty($header_translation)) {
                // WPML is active, handle translations
                // Assuming the translation is in the third column
                $translation = sanitize_text_field($data[2]);
                if (!empty($translation)) {
                    // Insert the translation as a separate post
                    $translated_post_id = wp_insert_post(array(
                        'post_title' => $title,
                        'post_content' => $translation,
                        'post_status' => 'publish',
                        'post_type' => 'qotd_cpt',
                        'post_date' => $pub_date
                    ));

                    if (!is_wp_error($translated_post_id)) {
                        // Link the translation to the original post
                        $wpml_element_type = apply_filters('wpml_element_type', 'post_qotd_cpt');
                        $get_language_args = array('element_id' => $original_post_id, 'element_type' => $wpml_element_type);
                        $original_post_language_info = apply_filters('wpml_element_language_details', null, $get_language_args);

                        $set_language_args = array(
                            'element_id' => $translated_post_id,
                            'element_type' => $wpml_element_type,
                            'trid' => $original_post_language_info->trid,
                            'language_code' => $header_translation, // Use the header translation to set the language code
                            'source_language_code' => $original_post_language_info->language_code
                        );

                        do_action('wpml_set_element_language_details', $set_language_args);
                    }
                }
            }
        }
        fclose($handle);

        if (!empty($imported_quotes)) {
            echo '<div class="updated"><p>Quotes imported successfully:</p><ul>';
            foreach ($imported_quotes as $quote) {
                echo '<li>' . esc_html($quote) . '</li>';
            }
            echo '</ul></div>';
        }

        if (!empty($failed_quotes)) {
            echo '<div class="error"><p>Some quotes failed to import:</p><ul>';
            foreach ($failed_quotes as $failed) {
                echo '<li>' . esc_html($failed['title']) . ': ' . esc_html($failed['error']) . '</li>';
            }
            echo '</ul></div>';
        }
    }
}
