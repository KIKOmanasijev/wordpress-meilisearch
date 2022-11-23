<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/admin
 * @author     Hristijan Manasijev <hristijan@digitalnode.com>
 */
class Wordpress_Meilisearch_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private Wordpress_Meilisearch_Repository $repository;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->repository = new Wordpress_Meilisearch_Repository();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wordpress_Meilisearch_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wordpress_Meilisearch_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'dist/css/main.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wordpress_Meilisearch_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wordpress_Meilisearch_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'dist/js/main.bundle.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'wpMeiliRest', array(
			'ajaxUrl' => admin_url('admin-ajax.php')
		) );

	}

	public function create_meilisearch_dashboard_page(){
		add_menu_page(
			"Meilisearch Dashboard",
			"Meilisearch",
			"manage_options",
			"meilisearch-dashboard",
			array( $this, "render_meilisearch_dashboard_page" ),
			"dashicons-database",
			2
		);
	}

	public function render_meilisearch_dashboard_page(){
		ob_start();

		$cpts = $this->get_all_cpts();

		include_once( WORDPRESS_MEILISEARCH_PLUGIN_PATH . 'admin/partials/wordpress-meilisearch-admin-display.php' );

		$template = ob_get_contents();

		ob_end_clean();

		echo $template;
	}

	public function handle_ajax_start_reindex(){

		$index           = $_REQUEST['index'] ?? 'post';
		$offset          = $_REQUEST['offset'] ?? 0;
		$posts_per_page  = 1000;
		$errors          = [];
		$valid_documents = [];

		$query = new WP_Query([
			'posts_per_page' => $posts_per_page,
			'post_type'      => $index,
			'offset'         => $offset * $posts_per_page
		]);

		foreach ( $query->get_posts() as $post ){
			$document = apply_filters( "meilisearch_{$index}_index_settings", get_post( $post->ID, ARRAY_A ), $post );

			if ( isset( $document['error'] ) && $document['error'] ){
				$errors[] = sprintf('Product with id %s missing a category, skipping it.', $post->ID);
				continue;
			}

			if ( $document ){
				$valid_documents[] = $document;
			}
		}

		$this->repository->add_documents( $valid_documents, $index );

		wp_send_json([
			'data'           => $_REQUEST['index'] ?? false,
			'total'          => wp_count_posts( $index )->publish,
			'posts_per_page' => $posts_per_page,
			'succeeded'      => $query->found_posts - count($errors),
			'failed'         => count($errors)
		], 200);
		die;
	}

	public function handle_ajax_clear_index(){
		$index = $_REQUEST['index'] ?? 'post';

		$this->repository->clear_index( $index );
		die;
	}

	private function get_all_cpts(){
		// TODO: Filterable CPT exclusions
		$disabled_cpt_prefixes = apply_filters('meilisearch_disable_cpts_by_prefixes_or_names', []);

		$cpts = array_filter( get_post_types( '', 'names' ), function( $post_type ) use ( $disabled_cpt_prefixes ) {
			foreach ( $disabled_cpt_prefixes as $prefix ){
				if ( str_starts_with( $post_type, $prefix ) ){
					return false;
				}
			}

			return true;
		});

		return $cpts;
	}
}
