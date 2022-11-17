<?php

use MeiliSearch\Client;

class Wordpress_Meilisearch_Repository {
	public function __construct(){
		// TODO: dynamic server credentials
		$this->client = new Client('http://localhost:7700');

		$this->client->index('items')->updateFilterableAttributes([
			'profit',
			'category'
		]);
	}

	public function add_documents( $documents ){
		// TODO: dynamic index choosing
		$this->client->index('items')->addDocuments( $documents );
	}
}