<?php

use MeiliSearch\Client;

class Wordpress_Meilisearch_Repository {
	public function __construct(){
		// TODO: dynamic server credentials
		$this->client = new Client('http://localhost:7700');

		// TODO: Implement this with a WP filter so filterable attributes is modifiable / use GUI settings in future?.
		$this->client->index('item')->updateFilterableAttributes([
			'profit',
			'category',
			'status',
			'market_price',
			'shipping_cost',
			'supplier_price',
			'profit'
		]);

		// TODO: Implement this with a WP filter so sortable attributes is modifiable / use GUI settings in future?.
		$this->client->index('item')->updateSortableAttributes([
			'updated_at',
			'profit',
			'market_price'
		]);
	}

	public function add_documents( $documents, $indexName = 'post' ){
		try {
			$index = $this->client->getIndex($indexName);
		} catch(Exception $e){
			$createIndexResponse = $this->client->createIndex($indexName);

			$this->client->waitForTask( $createIndexResponse['taskUid'] );

			$index = $this->client->getIndex($indexName);
		}

		$index->addDocuments( $documents );
	}

	public function delete_documents( $documents, $index = 'post' ){
		// TODO: dynamic index choosing
		$this->client->index( $index )->deleteDocuments($documents);
	}

	public function update_status_on_documents( $documents, $index = 'post' ){
		if ( ! is_array(  $documents ) ){
			$documents = array( $documents );
		}

		foreach ( $documents as $post_id ){
			$this->client->index( $index )->updateDocuments([
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