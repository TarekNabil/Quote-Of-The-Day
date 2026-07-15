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

function qotd_enqueue_admin_datepicker($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'qotd_cpt') {
        return;
    }

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/flick/jquery-ui.css');

    $inline_script = "jQuery(function($){ $('#qotd_quote_date').datepicker({ dateFormat: 'yy-mm-dd' }); var box = $('#qotd_quote_date_meta_box'); var side = $('#side-sortables'); if (box.length && side.length) { side.prepend(box); } });";
    wp_add_inline_script('jquery-ui-datepicker', $inline_script);
}
add_action('admin_enqueue_scripts', 'qotd_enqueue_admin_datepicker');

function qotd_add_quote_date_meta_box() {
    add_meta_box(
        'qotd_quote_date_meta_box',
        __('Quote Date', 'quote-of-the-day'),
        'qotd_render_quote_date_meta_box',
        'qotd_cpt',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'qotd_add_quote_date_meta_box');

function qotd_render_quote_date_meta_box($post) {
    wp_nonce_field('qotd_save_quote_date', 'qotd_quote_date_nonce');

    $quote_date = get_post_meta($post->ID, '_qotd_quote_date', true);
    ?>
    <p>
        <label for="qotd_quote_date"><?php esc_html_e('Select quote date:', 'quote-of-the-day'); ?></label>
    </p>
    <input
        type="text"
        id="qotd_quote_date"
        name="qotd_quote_date"
        value="<?php echo esc_attr($quote_date); ?>"
        class="regular-text"
        style="max-width:100%;"
        placeholder="YYYY-MM-DD"
    />
    <?php
}

function qotd_save_quote_date_meta($post_id) {
    if (!isset($_POST['qotd_quote_date_nonce']) || !wp_verify_nonce($_POST['qotd_quote_date_nonce'], 'qotd_save_quote_date')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['qotd_quote_date'])) {
        return;
    }

    $raw_date = sanitize_text_field(wp_unslash($_POST['qotd_quote_date']));

    if ($raw_date === '') {
        delete_post_meta($post_id, '_qotd_quote_date');
        return;
    }

    $date = DateTime::createFromFormat('Y-m-d', $raw_date);
    $is_valid = $date && $date->format('Y-m-d') === $raw_date;

    if ($is_valid) {
        update_post_meta($post_id, '_qotd_quote_date', $raw_date);
    }
}
add_action('save_post_qotd_cpt', 'qotd_save_quote_date_meta');

function qotd_display_quote_of_the_day($atts) {
    $output = '<div class="quote-of-the-day">';
    $output .= '<div id="quote_date_picker"></div>';
    $is_default_date = !isset($_GET['quote_date']) || $_GET['quote_date'] === '';
    $date = isset($_GET['quote_date']) ? sanitize_text_field(wp_unslash($_GET['quote_date'])) : current_time('Y-m-d');
    $parsed_date = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed_date || $parsed_date->format('Y-m-d') !== $date) {
        $date = current_time('Y-m-d');
        $is_default_date = true;
    }

    $args = array(
        'post_type' => 'qotd_cpt',
        'posts_per_page' => 1,
        'meta_key' => '_qotd_quote_date',
        'meta_value' => $date,
        'meta_compare' => '=',
    );

    $query = new WP_Query($args);

    // If no date was selected and there is no quote for today, show the latest published quote.
    if ($is_default_date && !$query->have_posts()) {
        $query = new WP_Query(array(
            'post_type' => 'qotd_cpt',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
    }

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<div class="quote-content">';
            $output .= '<h2>' . get_the_title() . '</h2>';
            $output .= '<div>' . get_the_content() . '</div>';
            $output .= '</div>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>No quote found for this date.</p>';
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
    $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : current_time('Y-m-d');
    $parsed_date = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed_date || $parsed_date->format('Y-m-d') !== $date) {
        $date = current_time('Y-m-d');
    }

    $args = array(
        'post_type' => 'qotd_cpt',
        'posts_per_page' => 1,
        'meta_key' => '_qotd_quote_date',
        'meta_value' => $date,
        'meta_compare' => '=',
    );
    
    $query = new WP_Query($args);
    $output = '<div class="quote-of-the-day">';
    $output .= '<div id="quote_date_picker"></div>';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<div class="quote-content">';
            $output .= '<h4>' . get_the_title() . '</h4>';
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
            $csv_date = sanitize_text_field($data[0]);
            $quote_date_meta = date('Y-m-d', strtotime($csv_date));
            $import_post_date = current_time('mysql');
            $import_post_date_gmt = current_time('mysql', true);

            // Convert the publication date to the correct format for title
            $title = date('d/m/Y', strtotime($csv_date));

            // Insert the original quote into the database
            $original_post_id = wp_insert_post(array(
                'post_title' => $title,
                'post_content' => $quote,
                'post_status' => 'publish',
                'post_type' => 'qotd_cpt',
                'post_date' => $import_post_date,
                'post_date_gmt' => $import_post_date_gmt
            ));

            if (is_wp_error($original_post_id)) {
                $failed_quotes[] = array('title' => $title, 'error' => $original_post_id->get_error_message());
            } else {
                update_post_meta($original_post_id, '_qotd_quote_date', $quote_date_meta);
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
                        'post_date' => $import_post_date,
                        'post_date_gmt' => $import_post_date_gmt
                    ));

                    if (!is_wp_error($translated_post_id)) {
                        update_post_meta($translated_post_id, '_qotd_quote_date', $quote_date_meta);

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
