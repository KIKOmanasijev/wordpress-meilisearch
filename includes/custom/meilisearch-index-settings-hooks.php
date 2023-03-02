<?php

/**
 * This file should be independent, and moved out of the plugin.
 * Keeping it here for testing purposes.
 *
 * TODO: Move this file out of the plugin
 */

add_filter( "meilisearch_product_index_settings", "build_item_document", 10, 2);
function build_item_document( $attributes, $post ){
	$product = wc_get_product( $post );

	// Push all taxonomies by default, including custom ones.
	$taxonomy_objects = get_object_taxonomies( $post->post_type, 'objects' );

	$attributes = [];
	$attributes['taxonomies']              = array();
	$attributes['taxonomies_hierarchical'] = array();

	foreach ( $taxonomy_objects as $taxonomy ) {

		$terms = wp_get_object_terms( $post->ID, $taxonomy->name );
		$terms = is_array( $terms ) ? $terms : array();

		if ( $taxonomy->hierarchical ) {
			$hierarchical_taxonomy_values = Algolia_Utils::get_taxonomy_tree( $terms, $taxonomy->name );
			if ( ! empty( $hierarchical_taxonomy_values ) ) {
				$attributes['taxonomies_hierarchical'][ $taxonomy->name ] = $hierarchical_taxonomy_values;
			}
		}

		$taxonomy_values = wp_list_pluck( $terms, 'name' );
		if ( ! empty( $taxonomy_values ) ) {
			$attributes['taxonomies'][ $taxonomy->name ] = $taxonomy_values;
		}
	}

	$price = 0;
	$regular_price = 0;
	$sale_price = 0;
	$max_price = 0;
	$quantity = 0;
	$gender = '';
	$product_type = '';
	$vendor = '';
	$brands = '';
	$categories = array();
	$categories_hierarchical = array();
	$external_id = array( $product->get_meta( '_external_id' ) );
	$size = array();
	$color = array();
	$priority_ranking = 50;

	if( $product->is_type( 'simple' ) ) {
		$quantity = $product->get_stock_quantity();
		$price = $max_price = wc_get_price_to_display( $product );
		$regular_price = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		$sale_price = wc_get_price_to_display( $product, array( 'price' => $product->get_sale_price() ) );
		$product_type = 'simple';
	}

	if( $product->is_type( 'variable' ) ) {

		$price = $product->get_variation_price( 'min', true );
		$regular_price = $product->get_variation_regular_price( 'min', true );
		$sale_price = $product->get_variation_sale_price( 'min', true );
		$max_price = $product->get_variation_price( 'max', true );
		$product_type = 'variable';

		/* @var WC_Product_Variation $variation */
		foreach ( array_map( 'wc_get_product', $product->get_children() ) as $variation ) {
			$quantity = $quantity + $variation->get_stock_quantity();
			$external_id[] = $variation->get_meta( '_variation_external_id' );
		}
	}

	if ( function_exists('brands_gateway_wc_get_gender_from_product' ) ) {
		$gender = brands_gateway_wc_get_gender_from_product( $product );
	}

	if( defined( 'WC_PRODUCT_VENDORS_TAXONOMY' ) ) {

		/* @var WP_Term $vendor_term */
		foreach ( wc_get_object_terms( $product->get_id(), WC_PRODUCT_VENDORS_TAXONOMY ) as $vendor_term ) {
			$vendor = $vendor_term->name;
		}
	}

	/* @var WP_Term $brand_term */
	foreach ( wc_get_object_terms( $product->get_id(), 'product_brand' ) as $brand_term ) {
		$brands = $brand_term->name;
	}

	/* @var WP_Term $category_term */
	foreach ( wc_get_object_terms( $product->get_id(), 'product_cat' ) as $category_term ) {
		$categories[] = array(
			'id' => $category_term->term_id,
			'name' => $category_term->name,
			'slug' => $category_term->slug,
			'level' => $category_term->parent,
		);
	}

	if( ! empty( $attributes['taxonomies_hierarchical']['product_cat'] ) ) {

		$categories_hierarchical = $attributes['taxonomies_hierarchical']['product_cat'];

		foreach ( $categories as $index => $category ) {

			foreach ( $categories_hierarchical as $level => $category_hierarchical ) {

				if ( substr_compare($category_hierarchical[0], $category['name'], - strlen( $category['name'] ) ) === 0 ) {
					$categories[$index]['level'] = (int) filter_var( $level, FILTER_SANITIZE_NUMBER_INT );
				}
			}
		}
	}

	/* @var WC_Product_Attribute $attribute */
	foreach ( $product->get_attributes() as $attribute ) {

		if( strpos( $attribute->get_name(), 'size' ) !== false ) {
			foreach( wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) as $value ) {
				if( ! in_array( $value, $size ) ) {
					$size[] = $value;
				}
			}
			continue;
		}

		if( strpos( $attribute->get_name(), 'color' ) !== false ) {
			foreach( wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) as $value ) {
				if( ! in_array( $value, $color ) ) {
					$color[] = $value;
				}
			}
		}
	}

	if ( function_exists('get_field') ){
		$terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
		$term_id = end($terms)->term_id;
		$priority_ranking = intval( get_field( 'priority_ranking', get_term( $term_id ) ) ?? 50 );
	}

	$to_be_unset = array(
		'content',
		'is_sticky',
		'record_index',
		'is_featured',
		'post_author',
		'post_type',
		'post_type_label',
		'post_type_label',
		'post_excerpt',
		'post_date_formatted',
		'comment_count',
		'menu_order',
		'post_mime_type',
		'metadata',
		'taxonomies',
		'taxonomies_hierarchical',
	);

	foreach( $to_be_unset as $key ) {
		if( array_key_exists( $key, $attributes ) ) {
			unset( $attributes[$key] );
		}
	}

	$attributes['id'] = $post->ID;
	$attributes['name'] = $product->get_name();
	$attributes['price'] = (float) $price;
	$attributes['regular_price'] = (float) $regular_price;
	$attributes['sale_price'] = (float) $sale_price;
	$attributes['max_price'] = (float) $max_price;
	$attributes['post_date'] = $product->get_date_created()->getTimestamp();
	$attributes['image'] = wp_get_attachment_url( $product->get_image_id() );;
	$attributes['sku'] = $product->get_sku();
	$attributes['status'] = $product->get_status();
	$attributes['permalink'] = $product->get_permalink();
	$attributes['quantity'] = $quantity;
	$attributes['stock_status'] = $product->get_stock_status();
	$attributes['gender'] = $gender;
	$attributes['size'] = $size;
	$attributes['color'] = $color;
	$attributes['vendor'] = $vendor;
	$attributes['brand'] = $brands;
	$attributes['category'] = $categories;
	$attributes['category_hierarchical'] = $categories_hierarchical;
	$attributes['external_ids'] = array_filter( $external_id, function( $value ) { return ! empty( $value ); } );
	$attributes['product_type'] = $product_type;
	$attributes['description'] = $product->get_description();
	$attributes['short_description'] = $product->get_short_description();
	$attributes['priority_ranking'] = $priority_ranking;

	return $attributes;
}

add_filter('meilisearch_product_modify_sort_options', "meili_item_modify_property_labels");
function meili_item_modify_property_labels( $items ){
	$with_order = [];

	foreach ( $items as &$item ){
		switch( $item['label'] ){
			case 'post_date':
				$with_order[] = [
					'label' => 'Sort by oldest',
					'value' => $item['value'] . ':asc'
				];
				$with_order[] = [
					'label' => 'Sort by newest',
					'value' => $item['value'] . ':desc'
				];
				break;
			case 'regular_price':
				$with_order[] = [
					'label' => 'Sort by market price: high to low',
					'value' => $item['value'] . ':desc'
				];

				$with_order[] = [
					'label' => 'Sort by market price: low to high',
					'value' => $item['value'] . ':asc'
				];
				break;
		}
	}

	return array_reverse( $with_order );
}

add_filter('meilisearch_disable_cpts_by_prefixes_or_names', "meili_disable_some_cpts");
function meili_disable_some_cpts( $cpts ){
	return [
		'wp_',
		'appframe_',
		'shop_',
		'oembed_',
		'custom_',
		'acf-',
		'mailpoet_',
		'nav_menu_',
		'customize_',
		'product_variation',
		'user_request',
		'revision',
	];
}

add_filter('meilisearch_product_extra_filters', "meili_append_extra_filters_for_product");
function meili_append_extra_filters_for_product($filters){
	return [ 'status=publish' ];
}

function get_taxonomy_tree( array $terms, $taxonomy, $separator = ' > ' ) {
	$term_ids = wp_list_pluck( $terms, 'term_id' );

	$parents = array();
	foreach ( $term_ids as $term_id ) {
		$path      = get_term_parents( $term_id, $taxonomy, $separator );
		$parents[] = rtrim( $path, $separator );
	}

	$terms = array();
	foreach ( $parents as $parent ) {
		$levels = explode( $separator, $parent );

		$previous_lvl = '';
		foreach ( $levels as $index => $level ) {
			$terms[ 'lvl' . $index ][] = $previous_lvl . $level;
			$previous_lvl             .= $level . $separator;

			// Make sure we have not duplicate.
			// The call to `array_values` ensures that we do not end up with an object in JSON.
			$terms[ 'lvl' . $index ] = array_values( array_unique( $terms[ 'lvl' . $index ] ) );
		}
	}

	return $terms;
}

function get_term_parents( $id, $taxonomy, $separator = '/', $nicename = false, $visited = array() ) {
	$chain  = '';
	$parent = get_term( $id, $taxonomy );
	if ( is_wp_error( $parent ) ) {
		return $parent;
	}

	if ( $nicename ) {
		$name = $parent->slug;
	} else {
		$name = $parent->name;
	}

	if ( $parent->parent && ( $parent->parent !== $parent->term_id ) && ! in_array( $parent->parent, $visited, true ) ) {
		$visited[] = $parent->parent;
		$chain    .= get_term_parents( $parent->parent, $taxonomy, $separator, $nicename, $visited );
	}

	$chain .= $name . $separator;

	return $chain;
}