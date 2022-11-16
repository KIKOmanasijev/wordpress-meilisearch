<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://brandsgateway.com
 * @since             1.0.0
 * @package           Wordpress_Meilisearch
 *
 * @wordpress-plugin
 * Plugin Name:       Wordpress Meilisearch
 * Plugin URI:        https://brandsgateway.com
 * Description:       Meilisearch sync, indexing and other features on Wordpress.
 * Version:           1.0.0
 * Author:            Hristijan Manasijev
 * Author URI:        https://brandsgateway.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wordpress-meilisearch
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WORDPRESS_MEILISEARCH_VERSION', '1.0.0' );
define( 'WORDPRESS_MEILISEARCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WORDPRESS_MEILISEARCH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wordpress-meilisearch-activator.php
 */
function activate_wordpress_meilisearch() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-meilisearch-activator.php';
	Wordpress_Meilisearch_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wordpress-meilisearch-deactivator.php
 */
function deactivate_wordpress_meilisearch() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-meilisearch-deactivator.php';
	Wordpress_Meilisearch_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wordpress_meilisearch' );
register_deactivation_hook( __FILE__, 'deactivate_wordpress_meilisearch' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-meilisearch.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wordpress_meilisearch() {

	$plugin = new Wordpress_Meilisearch();
	$plugin->run();

}
run_wordpress_meilisearch();
