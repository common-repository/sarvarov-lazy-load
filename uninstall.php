<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sarvarov_lazy_load' );
delete_transient( 'sarvarov_lazy_load_dynamic_js' );
delete_transient( 'sarvarov_lazy_load_dynamic_css' );
