<?php

class Wordpress_Meilisearch_Cli {
	public function __construct() {
		$this->repository = new Wordpress_Meilisearch_Repository();
	}

	public static function register() : void {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_command( 'meili', 'Wordpress_Meilisearch_Cli' );
		}
	}

	public function sync($args, $assoc_args){
		if (! isset($assoc_args['post_type']) ){
			WP_CLI::error('The post_type arg is not set up. Try appending --post_type=yourcpt');
		}

		$index = $assoc_args['post_type'];
		$per_page = 100;
		$offset = 0;
		$total_batches = ceil(wp_count_posts($index)->publish/$per_page);

		while(true) {
			$args = [
				'posts_per_page' => $per_page,
				'post_type'      => $index,
				'offset'         => $offset,
			];

			$result = Wordpress_Meilisearch_Helper::get_documents_for_index_with_wp_args($index, $args);

			if (! count( $result['documents'] ) ){
				break;
			}

			$this->repository->add_documents( $result['documents'], $index );

			$offset += $per_page;

			WP_CLI::success( sprintf("Successfully re-indexed %s/%s batches for post type `%s`",$offset/$per_page, $total_batches, $index ) );
		}

		// Update Last reindex date for current index.
		update_option("meilisearch_${index}_last_index", date('Y-m-d'));
	}

	public function delete_index($args, $assoc_args) {
		if (! isset($assoc_args['post_type']) ){
			WP_CLI::error('The post_type arg is not set up. Try appending --post_type=yourcpt');
		}

		$index = $assoc_args['post_type'];

		$this->repository->clear_index($index);

		WP_CLI::success("Successfully cleared the index named `$index`");
	}
}

Wordpress_Meilisearch_Cli::register();