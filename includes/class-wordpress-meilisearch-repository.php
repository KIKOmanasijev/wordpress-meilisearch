<?php

use MeiliSearch\Client;

class Wordpress_Meilisearch_Repository {
	public function __construct(){
		// TODO: dynamic server credentials
		$this->client = new Client('http://localhost:7700');

		// TODO: Implement this with a WP filter so filterable attributes is modifiable / use GUI settings in future?.
		$this->client->index('items')->updateFilterableAttributes([
			'profit',
			'category',
			'status'
		]);

		// TODO: Implement this with a WP filter so sortable attributes is modifiable / use GUI settings in future?.
		$this->client->index('items')->updateSortableAttributes([
			'updated_at',
			'profit',
			'market_price'
		]);
	}

	public function add_documents( $documents ){
		// TODO: dynamic index choosing
		$this->client->index('items')->addDocuments( $documents );
	}

	public function delete_documents( $documents ){
		// TODO: dynamic index choosing
		$this->client->index('items')->deleteDocuments($documents);
	}

	public function update_status_on_documents( $documents ){
		if ( ! is_array(  $documents ) ){
			$documents = array( $documents );
		}

		foreach ( $documents as $post_id ){
			$this->client->index('items')->updateDocuments([
				[
					'id' => $post_id,
					'status' => get_post( $post_id )->post_status
				]
			]);
		}
	}

	public function clear_index( $index ){
		return $this->client->index( $index )->delete();
	}
}