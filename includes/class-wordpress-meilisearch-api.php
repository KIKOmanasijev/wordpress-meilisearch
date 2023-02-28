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
		$search_params = $this->extract_search_params_from_request($request);

		// Run 'empty' query so we can get all possible facet values. Needs to be cached in the future.
		$facet_data = $this->client->index( $search_params['post_type'] )->search('',[ 'facets' => ['*'] ]);

		// Filterable attributes for the index.
		$filterable_attributes = $this->client->index('product')->getSettings()['filterableAttributes'];

		// This is needed since PHP is replacing '.' with '_' in the query param keys.
		$request_params = $this->replace_underscore_suffix_with_dot_in_array_keys($request->get_query_params());

		// Get only the params that will be needed to build the Filter args array. (basically get only existing properties)
		$params = $this->get_filterable_params_from_search_query($request_params, $filterable_attributes);

		$filters = [];

		// Building Filters param array.
		foreach ( $params as $key => $value ){
			// Checkboxes (any multiple choice field) value extraction.
			if ( gettype( $value ) == 'array' ){
				if ( str_contains($key, '_hierarchical') ){
					$lvlPos = strpos($key, '.lvl');
					$keyWithoutLvlSuffix = substr($key, 0, $lvlPos);
					$arrayOfOrs = [];

					foreach ( $params as $sec_loop_key => $sec_loop_value ){
						if (str_starts_with($sec_loop_key, $keyWithoutLvlSuffix)){
							$arrayOfOrs = array_merge(
								$arrayOfOrs,
								$this->generate_array_of_ors($sec_loop_value, $sec_loop_key)
							);

							unset($params[$sec_loop_key]);
						}
					}

					if ( $arrayOfOrs )
						$filters[] = $arrayOfOrs;
				} else {
					// Generate array of ORs from one property - ex: from category_hierarchical.lvl1
					$filters[] = $this->generate_array_of_ors( $value, $key );
				}
			// Search box, radio buttons (any single choice/value field), min-max value extraction.
			} else if ( gettype( $value ) == 'string' && strlen( $value ) ){
				$filters[] = $this->generate_filters_for_simple_facets($key, $value);
			}

			unset( $params[ $key ] );
		}

		$options = [
			'sort'   => [ $search_params['sort_by'] ],
			'facets' => [ '*' ],
			'offset' => $search_params['posts_per_page'] * $search_params['page'],
			'limit'  => $search_params['posts_per_page'],
			'filter'  => $filters
		];

		$results = $this->client->index( $search_params['post_type'] )->search(
			$search_params['search'],
			$options
		);

		if ( $results->getHitsCount() ) {
			// TODO: dynamically find the {index}-holder. Throw exceptions if views are missing.

			echo \Roots\View('partials.meilisearch.archives.items', [
				'results' => $results->getHits(),
				'total_hits' => $results->getEstimatedTotalHits(),
				'total_pages' => ceil( $results->getEstimatedTotalHits() / $search_params['posts_per_page'] ),
				'response' => $results,
				'page' => $search_params['page'] + 1,
				'sort_by' => $search_params['sort_by'],
				'facets' => $facet_data->getFacetDistribution(),
				'params' => $request_params
			] )->render();
		} else {
			echo \Roots\View('partials.meilisearch.archives.items', [
				'results' => [],
				'total_hits' => 0,
				'total_pages' => 1,
				'page' => $search_params['page'] + 1,
				'sort_by' => $search_params['sort_by'],
				'facets' => $facet_data->getFacetDistribution(),
				'params' => $request_params
			] )->render();
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

	private function generate_array_of_ors($elements, $propertyName): array{
		return array_map(
			function ( $item ) use ( $propertyName ) {
				return sprintf( "%s = '%s'", $propertyName,  $item );
			},
			$elements
		);
	}

	private function replace_underscore_suffix_with_dot_in_array_keys(array $params): array {
		foreach ( $params as $key => $value ){
			if ( str_contains($key, '_hierarchical_lvl') ){
				unset($params[$key]);
				$params[str_replace('hierarchical_lvl', 'hierarchical.lvl', $key)] = $value;
			}
		}

		return $params;
	}

	private function extract_search_params_from_request( WP_REST_Request $request ): array {
		return [
			'posts_per_page' => intval( $request['posts_per_page'] ?? 18 ),
			'page'           => abs( intval( $request['current_page'] ?? 1 ) - 1 ),
			'search'         => $request['q'] ?? '',
			'sort_by'        => $request['sort_by'] ?? 'post_date:desc',
			'post_type'      => $request['post_type']
		];
	}

	private function get_filterable_params_from_search_query($request_params, $filterable): array {
		return array_filter( $request_params, function ( $value, $key ) use ( $filterable ) {
			return in_array( $key, $filterable ) ||
			       ( str_starts_with( $key, 'range-min' ) || str_starts_with( $key, 'range-max' ) || in_array( substr( $key, 10 ), $filterable ) )
				;
		}, ARRAY_FILTER_USE_BOTH );
	}

	private function generate_filters_for_simple_facets( int $key, $value ) {
		if ( str_starts_with( $key, 'range-min' ) ) {
			return sprintf( "%s > %s", substr( $key, 10 ), $value );
		} else if ( str_starts_with( $key, 'range-max' ) ){
			return sprintf( "%s < %s", substr( $key, 10 ), $value );
		}
		else {
			return sprintf( "%s = '%s'", $key,  $value );
		}
	}
}