<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://brandsgateway.com
 * @since      1.0.0
 *
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wordpress_Meilisearch
 * @subpackage Wordpress_Meilisearch/includes
 * @author     Hristijan Manasijev <hristijan@digitalnode.com>
 */
class Wordpress_Meilisearch {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wordpress_Meilisearch_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WORDPRESS_MEILISEARCH_VERSION' ) ) {
			$this->version = WORDPRESS_MEILISEARCH_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wordpress-meilisearch';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_sync_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wordpress_Meilisearch_Loader. Orchestrates the hooks of the plugin.
	 * - Wordpress_Meilisearch_i18n. Defines internationalization functionality.
	 * - Wordpress_Meilisearch_Admin. Defines all hooks for the admin area.
	 * - Wordpress_Meilisearch_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-loader.php';

		/**
		 * The class responsible for providing helper functions across the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-helpers.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wordpress-meilisearch-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wordpress-meilisearch-public.php';

		/**
		 * The class responsible for transforming WP posts into valid Meilisearch documents
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-mapper.php';

		/**
		 * The class responsible for transferring data between WP and Meilisearch client
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-repository.php';

		/**
		 * TODO: Document this class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-sync-posts.php';

		/**
		 * TODO: Document this class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-api.php';

		/**
		 * TODO: Document this class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wordpress-meilisearch-cli.php';

		/**
		 * TODO: Document this class.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/custom/meilisearch-index-settings-hooks.php';


		$this->loader = new Wordpress_Meilisearch_Loader();

		if ( class_exists( 'Wordpress_Meilisearch_Api' ) ){
			new Wordpress_Meilisearch_Api();
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wordpress_Meilisearch_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wordpress_Meilisearch_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wordpress_Meilisearch_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'create_meilisearch_dashboard_page' );

		$this->loader->add_action( 'wp_ajax_start_reindex', $plugin_admin, 'handle_ajax_start_reindex' );

		$this->loader->add_action( 'wp_ajax_clear_index', $plugin_admin, 'handle_ajax_clear_index' );

		$this->loader->add_filter( 'post_row_actions', $plugin_admin, 'add_sync_row_action', 999, 2);

		$this->loader->add_action( 'admin_action_sync_cpt', $plugin_admin, 'action_sync_post_with_meili', 999, 2);

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_flash_notices', 12 );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_action_scheduler_store' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wordpress_Meilisearch_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to syncing WP posts to Meili
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_sync_hooks() {
		$plugin_sync = new Wordpress_Meilisearch_Sync_Posts( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_insert_post', $plugin_sync, 'action_sync_on_add', 999 );

		$this->loader->add_action( 'publish_post', $plugin_sync, 'action_sync_on_update', 999 );
		$this->loader->add_action( 'save_post', $plugin_sync, 'action_sync_on_update', 999 );

		$this->loader->add_action( 'wp_trash_post', $plugin_sync, 'action_sync_on_trash', 999 );
		$this->loader->add_action( 'before_delete_post', $plugin_sync, 'action_sync_on_delete', 999 );

		$this->loader->add_action( 'woocommerce_rest_insert_product_object', $plugin_sync, 'action_sync_on_update', 999 );
		$this->loader->add_action( 'woocommerce_rest_insert_product_variation_object', $plugin_sync, 'action_sync_on_update', 999 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wordpress_Meilisearch_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
