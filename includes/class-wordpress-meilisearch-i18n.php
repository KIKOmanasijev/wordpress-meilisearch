<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/includes
 * @author     Hristijan Manasijev <hristijan@digitalnode.com>
 */
class Wordpress_Meilisearch_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wordpress-meilisearch',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
