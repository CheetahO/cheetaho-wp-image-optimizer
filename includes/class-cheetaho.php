<?php

/**
 *
 * The file that defines the core plugin class
 *
 * This is used to define internationalization, admin-specific hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/includes
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.4.3
	 * @access   protected
	 * @var      CheetahO_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.4.3
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.4.3
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Settings data.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      array $cheetaho_settings
	 */
	private $cheetaho_settings;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.4.3
	 */
	public function __construct() {
		if ( defined( 'CHEETAHO_VERSION' ) ) {
			$this->version = CHEETAHO_VERSION;
		} else {
			$this->version = '1.4.3';
		}

		$this->cheetaho_settings = $this->get_cheetaho_settings_data();
		$this->plugin_name       = 'cheetaho-image-optimizer';
		$this->load_dependencies();
		$this->set_locale();

		if ( is_admin() ) {
			$this->define_admin_hooks();
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - CheetahO_Loader. Orchestrates the hooks of the plugin.
	 * - CheetahO_I18n. Defines internationalization functionality.
	 * - CheetahO_Admin. Defines all hooks for the admin area.
	 * - CheetahO_Settings. Defines all hooks for the settings area.
	 * - CheetahO_Stats. Statistics area.
	 * - CheetahO_API. API with CheetahO.
	 * - CheetahO_Alert. Plugin alerts.
	 * - CheetahO_Backup. Handle backups.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.4.3
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for cheetaho helper functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cheetaho-helpers.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cheetaho-loader.php';
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cheetaho-i18n.php';
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the admin settings.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-settings.php';

		/**
		 * The class responsible for defining all plugin statistics.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-stats.php';

		/**
		 * The class responsible for CheetahO api.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-api.php';

		/**
		 * The class responsible for CheetahO alert messages.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-alert.php';

		/**
		 * The class responsible for CheetahO backup.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-backup.php';

		/**
		 * The class responsible for CheetahO image optimization.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-optimizer.php';

		/**
		 * The class responsible for CheetahO bulk image optimization page.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-bulk.php';

		/**
		 * The class responsible for CheetahO Retina image optimization.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cheetaho-retina.php';

		$this->loader = new CheetahO_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CheetahO_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.4.3
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new CheetahO_I18n( $this->get_plugin_name() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.4.3
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new CheetahO_Admin( $this );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'cheetaho_redirect' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'manage_media_columns', $plugin_admin, 'add_media_columns' );
		$this->loader->add_action( 'manage_media_custom_column', $plugin_admin, 'fill_media_columns', 10, 2 );
		$this->loader->add_action( 'wp_ajax_cheetaho_reset', $plugin_admin, 'media_library_reset' );
		$this->loader->add_action( 'wp_ajax_cheetaho_reset_all', $plugin_admin, 'cheetaho_media_library_reset_batch' );
		$this->loader->add_action( 'delete_attachment', $plugin_admin, 'delete_attachment_img' );

		$plugin_admin_settings = new CheetahO_Settings( $this );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( CHEETAHO_PLUGIN_FILE ), $plugin_admin_settings, 'plugin_action_links' );
		$this->loader->add_action( 'admin_menu', $plugin_admin_settings, 'register_settings_page' );

		$cheetaho_optimizer = new CheetahO_Optimizer( $this );
		$this->loader->add_action( 'wp_ajax_cheetaho_request', $cheetaho_optimizer, 'cheetaho_ajax_callback' );
		$this->loader->add_action( 'wr2x_retina_file_added', $cheetaho_optimizer, 'optimize_after_wr2x_retina_file_added', 10, 3 );

		$plugin_admin_bulk = new CheetahO_Bulk( $this );
		$this->loader->add_action( 'admin_menu', $plugin_admin_bulk, 'register_bulk_page' );

		$plugin_admin_alert = new CheetahO_Alert( $this );

		if ( ( false != $this->cheetaho_settings && ! empty( $this->cheetaho_settings ) && ! empty( $this->cheetaho_settings['auto_optimize'] ) ) || ( false != $this->cheetaho_settings && ! isset( $this->cheetaho_settings['auto_optimize'] ) ) ) {
			$this->loader->add_action( 'add_attachment', $cheetaho_optimizer, 'cheetaho_uploader_callback' );
			$this->loader->add_filter( 'wp_generate_attachment_metadata', $cheetaho_optimizer, 'optimize_thumbnails_filter' );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.4.3
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.4.3
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since    1.4.3
	 * @return    CheetahO_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.4.3
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve settings data of the plugin.
	 *
	 * @since     1.4.3
	 * @return    array
	 */
	public function get_cheetaho_settings_data() {
		return get_option( '_cheetaho_options' );
	}

	/**
	 * Retrieve settings  of the plugin.
	 *
	 * @since     1.4.3
	 * @return    array
	 */
	public function get_cheetaho_settings() {
		return $this->cheetaho_settings;
	}
}
