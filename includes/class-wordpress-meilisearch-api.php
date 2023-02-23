<?php

use MeiliSearch\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wordpress_Meilisearch_Api {
	private Client $client;

	public function __construct() {
		$this->register_hooks();
		$this->client = new Client('http://localhost:7700');
	}

	private function register_hooks(){
		// Actions
		add_action( 'rest_api_init', [ $this, 'meilisearch_rest_api_init' ], PHP_INT_MAX );

		// Hooks
		add_filter( 'meilisearch_get_widget_options', [ $this, 'meilisearch_get_widget_options' ], 10, 3 );
		add_filter( 'meilisearch_get_sort_options', [ $this, 'meilisearch_get_sort_options' ], 10, 2 );
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
		$page = abs( intval( $request['current_page'] ?? 1 ) - 1 );

		$search = $request['q'] ?? '';
		$sort_by = $request['sort_by'] ?? 'post_date:desc';
		$post_type = $request['post_type'];

		$facet_data = $this->client->index( $post_type )->search(
			'',
			[ 'facets' => ['*'] ]
		);

		// Filterable attributes for the index.
		$filterable_attributes = $this->client->index('product')->getSettings()['filterableAttributes'];

		$request_params = $request->get_query_params();

		foreach ( $request_params as $key => $value ){
			if ( str_contains($key, '_hierarchical_lvl') ){
				unset($request_params[$key]);
				$request_params[str_replace('hierarchical_lvl', 'hierarchical.lvl', $key)] = $value;
			}
		}

		$params = array_filter( $request_params, function ( $value, $key ) use ( $filterable_attributes ) {
			return in_array( $key, $filterable_attributes ) ||
			       ( str_starts_with( $key, 'range-min' ) || str_starts_with( $key, 'range-max' ) || in_array( substr( $key, 10 ), $filterable_attributes ) )
				;
		}, ARRAY_FILTER_USE_BOTH );

		$filters = [];

		// Building Filters param array.
		foreach ( $params as $key => $value ){
			// Checkboxes (any multiple choice field) value extraction.
			if ( gettype( $value ) == 'array' ){
				$arrayOfOrs = array_map(
					function ( $item ) use ( $key ) {
						return sprintf( "%s = '%s'", $key,  $item );
					},
					$value
				);

				$filters[] = $arrayOfOrs;
			// Search box, radio buttons (any single choice/value field), min-max value extraction.
			} else if ( gettype( $value ) == 'string' && strlen( $value ) )
				if ( str_starts_with( $key, 'range-min' ) ) {
					$filters[] = sprintf( "%s > %s", substr( $key, 10 ), $value );
				} else if ( str_starts_with( $key, 'range-max' ) ){
					$filters[] = sprintf( "%s < %s", substr( $key, 10 ), $value );
				}
				else {
					$filters[] = sprintf( "%s = '%s'", $key,  $value );
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
			// TODO: dynamically find the {index}-holder. Throw exceptions if views are missing.

			echo \Roots\View('partials.meilisearch.archives.items', [
				'results' => $results->getHits(),
				'total_hits' => $results->getEstimatedTotalHits(),
				'total_pages' => ceil( $results->getEstimatedTotalHits() / $posts_per_page ),
				'response' => $results,
				'page' => $page + 1,
				'sort_by' => $sort_by,
				'facets' => $facet_data->getFacetDistribution()
			] )->render();
		} else {
			return new WP_Error( 400, 'No products found' );
		}

		die;
	}

	function meilisearch_get_widget_options( $options, $filter, $index = 'post' ){
		$results = $this->client->index( $index )->search('', [ 'facets' => ['*'] ]);

		$facet_options = array_filter(
			$results->getFacetDistribution(),
			function( $facetFilter ) use ( $filter ) {
				if (gettype($filter) == 'array')
					return in_array($facetFilter, $filter);

				return $facetFilter == $filter;
			},
			ARRAY_FILTER_USE_KEY
		);

		return array_pop($facet_options) ?? [];
	}

	function meilisearch_get_sort_options( $options, $index ){
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