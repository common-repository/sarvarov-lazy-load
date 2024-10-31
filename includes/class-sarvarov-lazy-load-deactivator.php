<?php

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 * @subpackage SARVAROV_Lazy_Load/includes
 */
class SARVAROV_Lazy_Load_Deactivator {
	/**
	 * Fired during plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_transient( 'sarvarov_lazy_load_dynamic_js' );
		delete_transient( 'sarvarov_lazy_load_dynamic_css' );
	}
}
