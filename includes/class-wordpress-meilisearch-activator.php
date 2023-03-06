<?php

/**
 * Fired during plugin activation
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/includes
 * @author     Hristijan Manasijev <hristijan@digitalnode.com>
 */
class Wordpress_Meilisearch_Activator {
	public static string $message = '';

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p> 🙌 Congrats, the Wordpress Meilisearch plugin has been activated! </p>';
			echo '</div>';
		} );
	}

	public static function can_activate(): bool {
		return (
			self::settings_defined() &&
			self::meili_client_class_exists() &&
			self::check_server_connection()
		);
	}

	private static function settings_defined():bool{
		if (! defined('MEILISEARCH_HOST') ){
			self::$message = 'You are missing `MEILISEARCH_HOST` in the configuration. Please add it in your wp-config.php or .env file.';
			return false;
		}

		return true;
	}

	private static function meili_client_class_exists() {
		if (! class_exists( \Meilisearch\Client::class ) ){
			self::$message = '`\Meilisearch\Client::class` does not exist, please run `composer install` in the plugin folder';
			return false;
		}

		return true;
	}

	private static function check_server_connection() {
		try {
			$client = new \Meilisearch\Client(MEILISEARCH_HOST);
		} catch (\Exception $exception){
			self::$message = 'The connection to the Meilisearch server failed, please check the server credentials.';
			return false;
		}

		if ( !$client->isHealthy() ){
			self::$message = 'The connection to the Meilisearch server is not healthy, please check your logs.';
			return false;
		}

		return true;
	}

}
