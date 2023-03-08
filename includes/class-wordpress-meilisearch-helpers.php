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

	public static function get_documents_for_index_with_wp_args($index, $settings){
		$valid_documents = [];
		$errors = [];

		$query = new WP_Query([
			'posts_per_page' => $settings['posts_per_page'],
			'post_type'      => $settings['post_type'],
			'offset'         => $settings['offset'],
		]);

		foreach ( $query->get_posts() as $post ){
			$document = apply_filters( "meilisearch_{$index}_index_settings", get_post( $post->ID, ARRAY_A ), $post );

			if ( isset( $document['error'] ) && $document['error'] ){
				$errors[] = sprintf('Product with id %s missing a category, skipping it.', $post->ID);
				continue;
			}

			if ( $document ){
				$valid_documents[] = $document;
			}
		}

		return [ 'documents' => $valid_documents, 'errors' => $errors ];
	}
}