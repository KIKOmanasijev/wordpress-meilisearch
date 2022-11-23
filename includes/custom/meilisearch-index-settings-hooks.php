<?php

/**
 * This file should be independent, and moved out of the plugin.
 * Keeping it here for testing purposes.
 *
 * TODO: Move this file out of the plugin
 */

add_filter( "meili_item_index_settings", "build_item_document", 10, 2);
function build_item_document( $attributes, $post ){
	$categories = wp_get_post_terms( $post->ID, 'item-category' );

	if ( !$categories ){
		return [
			'error' => true,
			'message' => sprintf('Product with id %s missing a category, skipping it.', $post->ID)
		];
	}

	$category = $categories[0]->name;
	$category_link = get_term_link( $categories[0]->slug, 'item-category' );

	$supplier_price = get_field('price', $post->ID );
	$market_price = get_field('suggested_sale_price', $post->ID );
	$profit = floatval( $market_price ) - floatval( $supplier_price );
	$shipping_cost = get_field('shipping_cost', $post->ID) ?? 0;

	$images = get_field( 'images', $post->ID );
	$image = '';

	if ( isset( $images ) && gettype( $images ) == 'array' && count( $images ) )
		$image = wp_get_attachment_image_url( $images[0], 'full' );

	return [
		'id' => $post->ID,
		'title' => $post->post_title,
		'image' => $image,
		'permalink' => get_the_permalink( $post->ID ),
		'category' => $category,
		'category_link' => $category_link,
		'supplier_price' => $supplier_price,
		'market_price' => $market_price,
		'shipping_cost' => $shipping_cost,
		'status' => $post->post_status,
		'profit' => $profit,
		'updated_at' => time()
	];
}

add_filter('meilisearch_modify_items_property_labels', "meili_item_modify_property_labels");
function meili_item_modify_property_labels( $items ){
	$with_order = [];

	foreach ( $items as &$item ){
		switch( $item['label'] ){
			case 'updated_at':
				$with_order[] = [
					'label' => 'Sort by oldest',
					'value' => $item['value'] . ':asc'
				];
				$with_order[] = [
					'label' => 'Sort by newest',
					'value' => $item['value'] . ':desc'
				];
				break;
			case 'profit':
				$with_order[] = [
					'label' => 'Sort by profit margin: high to low',
					'value' => $item['value'] . ':desc'
				];

				$with_order[] = [
					'label' => 'Sort by profit margin: low to high',
					'value' => $item['value'] . ':asc'
				];
				break;
			case 'market_price':
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