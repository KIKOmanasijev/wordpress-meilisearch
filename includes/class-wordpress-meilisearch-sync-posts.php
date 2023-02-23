<?php

/**
 * Handles the sync between Wordpress and the Meilisearch client.
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 */

class Wordpress_Meilisearch_Sync_Posts {
	private $plugin_name;
	private $version;
	private $store = null;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->repository = new Wordpress_Meilisearch_Repository();
		$this->logger = new WC_Logger();
		$this->deleted_posts = [];

		$this->register_handling_as_actions();

		add_action('init', [$this, 'register_action_scheduler_store'], 999);
	}

	public function register_action_scheduler_store(){
		$this->store = ActionScheduler::store();
	}

	public function register_handling_as_actions() {
		add_action( 'wc_brands_gateway_on_post_delete', array( $this, 'handle_wc_brands_gateway_on_post_delete' ), 10, 2 );
		add_action( 'wc_brands_gateway_on_post_trash', array( $this, 'handle_wc_brands_gateway_on_post_trash' ), 10, 1 );
		add_action( 'wc_brands_gateway_on_post_update', array( $this, 'handle_wc_brands_gateway_on_post_update' ), 10, 1 );
	}

	public function action_sync_on_update( $post_id ){
		as_enqueue_async_action('wc_brands_gateway_on_post_update', [$post_id], 'wordpress-meilisearch');
	}

	public function action_sync_on_trash( $post_id ){
		as_enqueue_async_action('wc_brands_gateway_on_post_trash', [$post_id], 'wordpress-meilisearch');
	}

	public function action_sync_on_delete( $post_id ){
		$this->cancel_bg_tasks_for_product_before_delete($post_id);

		$post_type = wc_get_product($post_id) ? 'product' : get_post_type($post_id);

		as_enqueue_async_action('wc_brands_gateway_on_post_delete', [$post_id, $post_type], 'wordpress-meilisearch');
	}

	public function handle_wc_brands_gateway_on_post_update( $post_id ){
		$post_type = get_post_type( $post_id );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id )  ) {
			return;
		}

		if ( $product = wc_get_product( $post_id ) ){
			if ( $product->get_parent_id() !== 0 ){
				$post_id = $product->get_parent_id();
				$post_type = 'product';
			}
		}

		$document = apply_filters( "meilisearch_{$post_type}_index_settings", [ "id" => $post_id ], get_post( $post_id ) );

		if ( isset( $document['error'] ) && $document['error'] ){
			return;
		}

		$this->repository->add_documents( $document, $post_type );
	}

	public function handle_wc_brands_gateway_on_post_delete($post_id, $post_type){
		try {
			$this->repository->delete_documents( [ $post_id ], $post_type );
		} catch (Exception $e){
			$this->logger->critical($e->getTraceAsString());
		}
	}

	public function handle_wc_brands_gateway_on_post_trash($post_id){
		$post_type = wc_get_product($post_id) ? 'product' : get_post_type($post_id);

		$this->repository->update_status_on_documents( [ $post_id ], $post_type );
	}

	public function cancel_bg_tasks_for_product_before_delete($post_id) {
		$args = array(
			'args' => array( $post_id ),
			'group' => 'wordpress-meilisearch',
			'per_page' => -1,
		);

		$actions = as_get_scheduled_actions($args);

		foreach ($actions as $key => $action) {
			$this->store->mark_complete($key);
		}
	}
}