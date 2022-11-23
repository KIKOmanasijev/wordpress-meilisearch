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

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->repository = new Wordpress_Meilisearch_Repository();
	}

	public function action_sync_on_update( $post_id ){
		$post_type = get_post_type( $post_id );
		$errors = [];

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id )  ) {
			return;
		}

		// TODO: implement this with a dynamic whitelisted list of CPTs
		if ( $post_type != 'item' ){
			return;
		}

		$document = apply_filters( "meilisearch_{$post_type}_index_settings", [ "id" => $post_id ], get_post( $post_id ) );

		if ( isset( $document['error'] ) && $document['error'] ){
			return;
		}

		$this->repository->add_documents( $document );
	}

	public function action_sync_on_trash( $post_id ){
		$this->repository->update_status_on_documents( [ $post_id ], get_post_type( $post_id ) );
	}

	public function action_sync_on_delete( $post_id ){
		$this->repository->delete_documents( [ $post_id ], get_post_type( $post_id ) );
	}

}