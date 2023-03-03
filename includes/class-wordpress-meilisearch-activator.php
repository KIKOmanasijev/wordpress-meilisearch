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

	}

	public static function can_activate(): bool {
		if (! class_exists( \Meilisearch\Client::class ) ){
			self::$message = '`\Meilisearch\Client::class` does not exist, please run `composer install` in the plugin folder';
			return false;
		}

		try {
			$client = new \Meilisearch\Client('http://localhost:7700');
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
