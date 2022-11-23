<?php

use MeiliSearch\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wordpress_Meilisearch_Api {
	public function __construct() {
		$this->register_hooks();
		$this->client = new Client('http://localhost:7700');
	}

	private function register_hooks(){
		// Actions
		add_action( 'rest_api_init', [ $this, 'meilisearch_rest_api_init' ], PHP_INT_MAX );

		// Hooks
		add_filter( 'meilisearch_get_widget_options', [ $this, 'dropshipping_get_widget_options' ], 10, 3 );
		add_filter( 'meilisearch_get_sort_options', [ $this, 'dropshipping_get_sort_options' ], 10, 2 );
	}

	public function meilisearch_rest_api_init() {
		register_rest_route(
			'wc-meilisearch/v1',
			'/(?P<post_type>\w+)/fetch',
			array(
				'methods'  => 'get',
				'callback' => [ $this, 'wordpress_meilisearch_fetch' ],
				'permission_callback' => '__return_true'
			));
	}

	function wordpress_meilisearch_fetch( WP_REST_Request $request ) {
		$posts_per_page = intval( $request['posts_per_page'] ?? 18 );
		$page = intval( $request['page'] ?? 0 );
		$search = $request['q'] ?? '';
		$sort_by = $request['sort_by'] ?? 'updated_at:desc';

		$post_type = $request['post_type'];

		// Filterable attributes for the index.
		$filterable_attributes = $this->client->index('item')->getSettings()['filterableAttributes'];

		// Allowed query params to be sent from the front end.
		// TODO: Implement this with filter so plugin-users can modify the list.
		$allowed_params = [ 'posts_per_page', 'page', 'q', 'sort_by' ];

		$params = array_filter( $request->get_query_params(), function ( $key ) use ( $allowed_params, $filterable_attributes ) {
			return in_array( $key, $filterable_attributes ) || in_array( $key, $allowed_params );
		}, ARRAY_FILTER_USE_KEY );

		$filters = [];

		// Building Filters param array.
		foreach ( $params as $key => $value ){
			if ( ! in_array( $key, $filterable_attributes ) ){
				continue;
			}

			if ( gettype( $value ) == 'array' ){
				$arrayOfOrs = array_map(
					function ( $item ) use ( $key ) {
						return sprintf( "%s = '%s'", $key, htmlentities( $item ) );
					},
					$value
				);

				$filters[] = $arrayOfOrs;
			} else if ( gettype( $value ) == 'string' ) {
				$filters[] = sprintf( "%s = '%s'", $key, htmlentities( $value ) );
			}

			unset( $params[ $key ] );
		}

		$options = [
			'sort'   => [ $sort_by ],
			'facets' => [ '*' ],
			'offset' => $posts_per_page * $page,
			'limit'  => $posts_per_page,
			'filter'  => $filters
		];

		$results = $this->client->index( $post_type )->search(
			$search,
			$options
		);

		if ( $results->getHitsCount() ) {
			echo \Roots\View('partials.meilisearch.partials.items-holder', [
				'results' => $results->getHits(),
				'totalHits' => $results->getEstimatedTotalHits(),
				'response' => $results,
				'page' => $results->getOffset(),
				'sort_by' => $sort_by
			] )->render();
		} else {
			return new WP_Error( 400, 'No products found' );
		}

		die;
	}

	function dropshipping_get_widget_options( $options, $filter, $index = 'post' ){
		$results = $this->client->index( $index )->search('', [ 'facets' => ['*'] ]);

		$facet_options = array_filter(
			$results->getFacetDistribution(),
			function( $facetFilter ) use ( $filter ) {
				return $facetFilter == $filter;
			},
			ARRAY_FILTER_USE_KEY
		);

		return array_pop($facet_options) ?? [];
	}

	function dropshipping_get_sort_options( $options, $index ){
		$results =  array_map(
			function( $option ){
				return [
					'label' => $option,
					'value' => $option
				];
			},
			$this->client->index( $index )->getSortableAttributes()
		);

		return apply_filters("meilisearch_${index}_modify_sort_options", $results );
	}
}