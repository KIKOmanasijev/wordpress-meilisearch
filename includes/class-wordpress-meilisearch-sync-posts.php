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
		add_action( 'wc_brands_gateway_on_post_add', array( $this, 'handle_wc_brands_gateway_on_post_add' ), 999, 1 );

		add_action( 'wc_brands_gateway_on_post_delete', array( $this, 'handle_wc_brands_gateway_on_post_delete' ), 999, 2 );

		add_action( 'wc_brands_gateway_on_post_trash', array( $this, 'handle_wc_brands_gateway_on_post_trash' ), 999, 1 );

		add_action( 'wc_brands_gateway_on_post_update', array( $this, 'handle_wc_brands_gateway_on_post_update' ), 999, 1 );
	}

	public function action_sync_on_add( $post_id ){
		as_enqueue_async_action('wc_brands_gateway_on_post_add', array( $post_id ), 'wordpress-meilisearch');
	}

	public function action_sync_on_update( $post_id ){
		if ( $post_id ){
			as_enqueue_async_action('wc_brands_gateway_on_post_update', array( $post_id ), 'wordpress-meilisearch');
		}
	}

	public function action_sync_on_trash( $post_id ){
		$product = wc_get_product( $post_id );

		// Ignore variations since we don't store them in Meili.
		if ( is_a($product, WC_Product_Variation::class ) ){
			return;
		}

		as_enqueue_async_action('wc_brands_gateway_on_post_trash', array( $post_id ), 'wordpress-meilisearch');
	}

	public function action_sync_on_delete( $post_id ){
		$post_type = Wordpress_Meilisearch_Helper::get_index_by_post_id($post_id);

		Wordpress_Meilisearch_Helper::cancel_bg_tasks_for_product_before_delete($post_id);

		as_enqueue_async_action(
			'wc_brands_gateway_on_post_delete',
			array( $post_id, $post_type ),
			'wordpress-meilisearch'
		);
	}

	public function handle_wc_brands_gateway_on_post_add( $post_id ){
		$post_type = Wordpress_Meilisearch_Helper::get_index_by_post_id( $post_id );

		$document = $this->generate_document_structure_by_post_id($post_id, $post_type);

		if ( $document ){
			$this->repository->add_documents( [ $document ], $post_type );
		}
	}

	public function handle_wc_brands_gateway_on_post_update( $post_id ){
		$post_type = Wordpress_Meilisearch_Helper::get_index_by_post_id( $post_id );

		$document = $this->generate_document_structure_by_post_id($post_id, $post_type);

		if ( $document ){
			$this->repository->update_documents( [ $document ], $post_type );
		}
	}

	public function handle_wc_brands_gateway_on_post_delete($post_id, $post_type){
		try {
			$this->repository->delete_documents(
				is_array( $post_id ) ? $post_id : array( $post_id ),
				$post_type
			);
		} catch (Exception $e){
			$this->logger->critical($e->getTraceAsString());
		}
	}

	public function handle_wc_brands_gateway_on_post_trash($post_id){
		$post_type = Wordpress_Meilisearch_Helper::get_index_by_post_id($post_id);

		$this->repository->update_status_on_documents( [ $post_id ], $post_type );
	}

	private function generate_document_structure_by_post_id($post_id, $post_type){
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id )  ) {
			return false;
		}

		if ( $product = wc_get_product( $post_id ) ){
			// If the product is a variation, use the parent to generate a document and update it.
			if ( is_a($product, WC_Product_Variation::class) ){
				$post_id = $product->get_parent_id();
				$post_type = 'product';
			}
		}

		$document = apply_filters( "meilisearch_{$post_type}_index_settings", [ "id" => $post_id ], get_post( $post_id ) );

		if ( isset( $document['error'] ) && $document['error'] ){
			return false;
		}

		return $document;
	}
}