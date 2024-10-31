<?php

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 * @subpackage SARVAROV_Lazy_Load/includes
 */
class SARVAROV_Lazy_Load {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SARVAROV_Lazy_Load_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The title of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_title The title of this plugin.
	 */
	protected $plugin_title;

	/**
	 * The slug of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_slug The slug of this plugin.
	 */
	protected $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_version The current version of the plugin.
	 */
	protected $plugin_version;

	/**
	 * The plugin settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array $plugin_settings The plugin settings.
	 */
	protected $plugin_settings;

	/**
	 * The basename of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_version The basename of the plugin.
	 */
	protected $plugin_basename;

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
		$this->plugin_name     = SARVAROV_LAZY_LOAD_PLUGIN_NAME;
		$this->plugin_title    = SARVAROV_LAZY_LOAD_PLUGIN_TITLE;
		$this->plugin_slug     = SARVAROV_LAZY_LOAD_PLUGIN_SLUG;
		$this->plugin_version  = SARVAROV_LAZY_LOAD_VERSION;
		$this->plugin_basename = SARVAROV_LAZY_LOAD_PLUGIN_BASENAME;
		$this->plugin_settings = get_option( $this->plugin_slug );

		$this->load_dependencies();
		$this->set_locale();
		$this->define_public_hooks();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-sarvarov-lazy-load-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-sarvarov-lazy-load-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin or front-end area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-sarvarov-lazy-load-public.php';

		if ( is_admin() ) {
			require_once plugin_dir_path( __DIR__ ) . 'admin/class-sarvarov-lazy-load-admin.php';
		}

		$this->loader = new SARVAROV_Lazy_Load_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new SARVAROV_Lazy_Load_i18n();

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
		if ( ! is_admin() ) {
			return;
		}

		$plugin_admin = new SARVAROV_Lazy_Load_Admin( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_plugin_slug(), $this->get_version(), $this->get_settings() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'admin_init' );

		if ( isset( $_GET['page'] ) && ( $_GET['page'] === $this->get_plugin_slug() . '_settings' ) ) {
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		}

		$this->loader->add_filter( 'plugin_action_links_' . SARVAROV_LAZY_LOAD_PLUGIN_BASENAME, $plugin_admin, 'settings_link' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new SARVAROV_Lazy_Load_Public( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_plugin_slug(), $this->get_version(), $this->get_settings() );

		$this->loader->add_action( 'after_setup_theme', $plugin_public, 'after_setup_theme' );

		$this->loader->add_action( 'save_post', $plugin_public, 'save_post' );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'script_loader_tag', $plugin_public, 'add_async_to_lazysizes', 10, 2 );

		$this->loader->add_filter( 'the_content', $plugin_public, 'lazy_process', 110, 1 );
        $this->loader->add_filter( 'post_thumbnail_html', $plugin_public, 'lazy_process', 110, 1 );
        $this->loader->add_filter( 'post_thumbnail_html', $plugin_public, 'remove_size_atts_from_thumbnail', 100, 1 );
		$this->loader->add_filter( 'lazy_process', $plugin_public, 'lazy_process', 1, 2 );
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
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The title of the plugin.
	 *
	 * @since     1.0.0
	 */
	public function get_plugin_title() {
		return $this->plugin_title;
	}

	/**
	 * The slug of the plugin.
	 *
	 * @since     1.0.0
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->plugin_version;
	}

	/**
	 * Get the settings of the plugin.
	 *
	 * @since     1.0.0
	 */
	public function get_settings() {
		$default_settings = array(
			'enable_on_images'               => true,
			'enable_on_iframes'              => true,
			'preload_enable'                 => false,
			'expand_value'                   => 0,
			'placeholder_color'              => 'rgba(0, 0, 0, 0.05)',
			'lqip_enable'                    => true,
			'lqip_blur_radius'               => 20,
			'lqip_size'                      => 33,
			'lqip_base64_enable'             => true,
			'image_transition_time'          => 400,
			'image_transition_delay'         => 400,
			'image_transition_effect'        => 'ease',
			'image_lqip_transition_time'     => 100,
			'image_lqip_transition_delay'    => 200,
			'image_lqip_transition_effect'   => 'ease',
			'image_average_color_bg_enable'  => true,
			'iframe_transition_time'         => 400,
			'iframe_transition_delay'        => 0,
			'iframe_transition_effect'       => 'ease',
			'iframe_average_color_bg_enable' => true,
			'noscript_enable'                => true,
			'cache_enable'                   => true,
			'placeholders_disable'           => false,
			'custom_settings'                => ''
		);

		return array_merge(
			apply_filters( 'sarvarov_lazy_load_default_settings', $default_settings ),
			$this->plugin_settings ?: array()
		);
	}
}
