# Quote of the Day

**Plugin Name:** Quote of the Day  
**Description:** A plugin to display the quote of the day with a calendar to pick all dates.  
**Version:** 1.0  
**Author:** Tarek Nabil

## Description

The "Quote of the Day" plugin allows you to display a quote of the day from a custom post type. Users can select a date from a calendar to view quotes from all dates. The plugin integrates with Elementor to provide a custom widget for displaying the quotes.

## Features

- Custom post type for quotes
- Shortcode to display the quote of the day
- jQuery UI datepicker to select all dates
- Elementor widget for easy integration

## Installation

1. Upload the plugin files to the `/wp-content/plugins/quote-of-the-day` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the shortcode `[quote_of_the_day]` to display the quote of the day.
4. Add the Elementor widget to your page to display the quote of the day with a datepicker.

## Usage

### Shortcode

Use the `[quote_of_the_day]` shortcode to display the quote of the day. The shortcode will display the quote for the current date by default. Users can select a different date using the datepicker.

### Elementor Widget

The plugin provides an Elementor widget named "Quote of the Day". You can add this widget to any page using the Elementor editor. The widget will display the quote of the day with a datepicker to select all dates.

## Custom Post Type

The plugin registers a custom post type named `quote`. You can add new quotes from the WordPress admin dashboard under the "Quotes" menu.

## Enqueue Scripts and Styles

The plugin enqueues the necessary scripts and styles for the jQuery UI datepicker and custom styles for the widget.

## AJAX Handling

The plugin uses AJAX to fetch quotes for the selected date without reloading the page. The AJAX handler is defined in the `quote-widget.js` file.

## Contributions and Feature Requests

Contributions and feature requests are welcome! If you have any ideas for new features or improvements, please feel free to open an issue or submit a pull request on the [GitHub repository](https://github.com/TarekNabil/quote-of-the-day).

## Changelog

### 1.0
- Initial release

## License

This plugin is licensed under the GPLv3 or later.
