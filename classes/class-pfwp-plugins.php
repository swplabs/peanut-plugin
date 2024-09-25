<?php

if ( ! defined( 'PFWP_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Plugins
 */
class PFWP_Plugins {
	public static function register_asset( $handle, $key = '', $entry_key = '' ) {
		global $pfwp_global_config;

		$assets = PFWP_Assets::get_key_assets( 'plugins', $key, $entry_key );
		$wp_deps_file = $pfwp_global_config->wp_root . '/' . PFWP_Assets::get_wp_deps( 'plugins', $key, $entry_key );
	
		$deps = require $wp_deps_file;
	
		if ( isset( $pfwp_global_config->compilations->plugins_elements->runtime ) ) {
			array_push( $deps['dependencies'], 'plugins_elements_webpack_runtime' );
		}

		wp_register_script(
			$handle,
			PFWP_SITE_URL . '/' . $assets->js[0],
			$deps['dependencies'],
			$deps['version']
		);
	}
}
