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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'dist/css/main.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
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
		$posts_per_page  = 100;

		$result = Wordpress_Meilisearch_Helper::get_documents_for_index_with_wp_args($index, [
			'posts_per_page'  => $posts_per_page,
			'offset'    => $offset * $posts_per_page,
			'post_type' => $index
		]);

		$this->repository->add_documents( $result['documents'], $index );

		// Update Last reindex date for current index.
		update_option("meilisearch_${index}_last_index", date('Y-m-d'));

		wp_send_json([
			'data'           => $index,
			'total'          => wp_count_posts( $index )->publish,
			'posts_per_page' => $posts_per_page,
			'succeeded'      => count($result['documents']),
			'failed'         => count($result['errors'])
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

	public function add_sync_row_action($actions, $post){
			$actions['sync_meili'] = sprintf(
				"<a href=\"%sedit.php?post_type=%s&action=%s&post=%s&_wpnonce=%s\" aria-label=\"%s\" rel=\"permalink\">%s</a>",
				admin_url(),
				urlencode(get_post_type($post->ID)),
				urlencode('sync_cpt'),
				urlencode($post->ID),
				wp_create_nonce('sync_meili_post'),
				esc_attr__('Sync Post with Meili'),
				esc_html__('🔄 Sync with Meili')
			);

		return $actions;
	}

	public function action_sync_post_with_meili($post_id){
		if ( empty( $_REQUEST['post'] ) ) {
			wp_die( esc_html__( 'No product to duplicate has been supplied!', 'woocommerce' ) );
		}

		$post_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		$index = Wordpress_Meilisearch_Helper::get_index_by_post_id($post_id);

		$document = apply_filters( "meilisearch_{$index}_index_settings", get_post( $post_id, ARRAY_A ), get_post($post_id) );

		// Cancel all other Meilisearch hooks before triggering another update.
		Wordpress_Meilisearch_Helper::cancel_bg_tasks_for_product_before_delete($post_id);

		// Force-update the document
		$this->repository->update_documents([$document], $index);

		$this->add_flash_notice(
			__( "🥳 The post was synced with the Meilisearch database."),
			"success"
		);

		$redirect_link = $this->get_admin_cpt_page_for_post($post_id);

		wp_redirect($redirect_link);
		die;
	}

	public function add_flash_notice( $notice = "", $type = "warning", $dismissible = true ) {
		// Here we return the notices saved on our option, if there are not notices, then an empty array is returned
		$notices = get_option( "my_flash_notices", array() );

		$dismissible_text = ( $dismissible ) ? "is-dismissible" : "";

		// We add our new notice.
		array_push( $notices, array(
			"notice" => $notice,
			"type" => $type,
			"dismissible" => $dismissible_text
		) );

		// Then we update the option with our notices array
		update_option("my_flash_notices", $notices );
	}

	public function display_flash_notices() {
		$notices = get_option( "my_flash_notices", array() );

		// Iterate through our notices to be displayed and print them.
		foreach ( $notices as $notice ) {
			printf('<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
				$notice['type'],
				$notice['dismissible'],
				$notice['notice']
			);
		}

		// Now we reset our options to prevent notices being displayed forever.
		if( ! empty( $notices ) ) {
			delete_option( "my_flash_notices", array() );
		}
	}

	private function get_admin_cpt_page_for_post( int $post_id ) {
		$preview_link = admin_url('edit.php');

		if ($preview_link) {
			$preview_link = add_query_arg('post_type', get_post_type($post_id), $preview_link);
			$preview_link = add_query_arg('preview', 'true', $preview_link);
		}

		return $preview_link;
	}

	public function register_action_scheduler_store(){
		Wordpress_Meilisearch_Helper::$store = ActionScheduler::store();
	}
}