<?php
/**
 * Plugin Name:       Distributor ACF Add-on
 * Description:       An add-on for the "Distributor" plug-in to manage ACF distribution
 * Version:           1.0.0
 * Author:            Novembit
 * License:           GPLv2 or later
 * Text Domain:       distributor-acf
 */

/**
 * Bootstrap function
 */
function dt_acf_add_on_bootstrap() {
	if ( ! function_exists( '\Distributor\ExternalConnectionCPT\setup' ) ) {
		if ( is_admin() ) {
			add_action( 'admin_notices', function() {
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( 'notice notice-error' ), esc_html( 'You need to have Distributor plug-in activated to run the Distributor ACF Add-on.', 'distributor-acf' ) );
			} );
		}
		return;
	}

	require_once plugin_dir_path( __FILE__ ) . 'manager.php';
}

add_action( 'plugins_loaded', 'dt_acf_add_on_bootstrap' );
