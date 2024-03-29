<?php

class Wordpress_Meilisearch_Mapper {
	public function __construct(){}

	public static function build_item_document( $post ){
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
			'profit' => $profit,
			'updated_at' => time()
		];
	}


}