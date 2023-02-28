<?php

use MeiliSearch\Client;

class Wordpress_Meilisearch_Repository {
	private array $deleted_posts;

	public function __construct(){
		// TODO: dynamic server credentials
		$this->client = new Client('http://localhost:7700');

		// TODO: Implement this with a WP filter so filterable attributes is modifiable / use GUI settings in future?.
		$this->client->index('product')->updateFilterableAttributes([
			'brand',
			'category_hierarchical.lvl0',
			'category_hierarchical.lvl1',
			'category_hierarchical.lvl2',
			'brand'
		]);

		// TODO: Implement this with a WP filter so sortable attributes is modifiable / use GUI settings in future?.
		$this->client->index('product')->updateSortableAttributes([
			'regular_price',
			'sale_price',
			'post_date'
		]);

		// TODO: Implement this with a WP filter
		$this->client->index('product')->updateSettings([
			'pagination' => [
				'maxTotalHits'=> 150000
			]
		]);

		$this->deleted_posts = [];
	}

	public function add_documents( $documents, $indexName = 'post' ){
		foreach ( $documents as $key => $post_id ){
			if ( in_array( $post_id, $this->deleted_posts ) ){
				unset( $documents[$key] );
			}
		}

		$index = $this->get_or_create_index($indexName);

		$index->addDocuments( $documents );
	}

	public function update_documents( $documents, $indexName = 'post' ){
		foreach ( $documents as $key => $post_id ){
			if ( in_array( $post_id, $this->deleted_posts ) ){
				unset( $documents[$key] );
			}
		}

		$index = $this->get_or_create_index($indexName);

		$index->updateDocuments( $documents );
	}

	public function delete_documents( $documents, $index = 'post' ){
		$logger = new WC_Logger();

		foreach ( $documents as $key => $post_id ){
			if ( in_array( $post_id, $this->deleted_posts ) ){
				unset( $documents[$key] );
			}
		}

		try {
			// TODO: dynamic index choosing
			$this->client->index( $index )->deleteDocuments($documents);
		} catch (Exception $e){
			$logger->error($e->getTraceAsString());
		}
	}

	public function update_status_on_documents( $documents, $index = 'post' ){
		if ( ! is_array(  $documents ) ){
			$documents = array( $documents );
		}

		foreach ( $documents as $post_id ){
			if ( in_array( $post_id, $this->deleted_posts ) ){
				continue;
			}

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

	private function get_or_create_index($indexName){
		try {
			return $this->client->getIndex($indexName);
		} catch(Exception $e){
			$createIndexResponse = $this->client->createIndex($indexName);

			$this->client->waitForTask( $createIndexResponse['taskUid'] );

			return $this->client->getIndex($indexName);
		}
	}
}