<?php
/**
 * Plugin Name:       SARVAROV Lazy Load
 * Plugin URI:        https://wordpress.org/plugins/sarvarov-lazy-load/
 * Description:       Lazy Load all your images, videos and iframes in just one click. Make your blog faster and look better with blurred LQIP and primary color placeholder.
 * Version:           1.1.0
 * Author:            Roman Sarvarov
 * Author URI:        https://about.me/sarvaroff
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       sarvarov-lazy-load
 * Domain Path:       /languages/
 */

// if this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants.
 */
define( 'SARVAROV_LAZY_LOAD_VERSION', '1.1.0' );
define( 'SARVAROV_LAZY_LOAD_PLUGIN_NAME', 'sarvarov-lazy-load' );
define( 'SARVAROV_LAZY_LOAD_PLUGIN_SLUG', 'sarvarov_lazy_load' );
define( 'SARVAROV_LAZY_LOAD_PLUGIN_TITLE', 'SARVAROV Lazy Load' );
define( 'SARVAROV_LAZY_LOAD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_sarvarov_lazy_load() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sarvarov-lazy-load-deactivator.php';
	SARVAROV_Lazy_Load_Deactivator::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_sarvarov_lazy_load' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sarvarov-lazy-load.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_sarvarov_lazy_load() {

	$plugin = new SARVAROV_Lazy_Load();
	$plugin->run();

}

run_sarvarov_lazy_load();
