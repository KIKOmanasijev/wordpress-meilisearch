<?php

/**
 * Meilisearch helper functions
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 */

class Wordpress_Meilisearch_Helper {
	// Wordpress_Meilisearch_Admin initialises this static property.
	public static $store;

	public function __construct() {
		add_action('admin_init', [$this, 'register_action_scheduler_store'], 1);
	}

	public function register_action_scheduler_store(){
		self::$store = ActionScheduler::store();
	}

	public static function get_index_by_post_id($post_id){
		return wc_get_product($post_id) ? 'product' : get_post_type($post_id);
	}

	public static function cancel_bg_tasks_for_product_before_delete($post_id){
		$args = array(
			'args' => array( $post_id ),
			'group' => 'wordpress-meilisearch',
			'per_page' => -1,
		);

		$actions = as_get_scheduled_actions($args);

		foreach ($actions as $key => $action) {
			self::$store->mark_complete($key);
		}
	}
}