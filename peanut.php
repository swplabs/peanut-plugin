<?php
/*
Plugin Name: Peanut For WordPress
Plugin URI: https://github.com/swplabs
Description: Peanut for WordPress!
Version: 0.1.0-alpha.2
Author: SWP Labs
Author URI: https://www.sassywackypeanut.com/labs/
License: GPLv2 or later
Text Domain: pfwp
*/

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! defined( 'PFWP_VERSION' ) ) {
	define( 'PFWP_VERSION', '0.1.0-alpha.2' );
}

if ( ! defined( 'PFWP_PLUGIN_FILE' ) ) {
	define( 'PFWP_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'PFWP_PLUGIN_DIR' ) ) {
	define( 'PFWP_PLUGIN_DIR', plugin_dir_path( PFWP_PLUGIN_FILE ) );
}

if ( ! defined( 'PFWP_PLUGIN_URL' ) ) {
	define( 'PFWP_PLUGIN_URL', plugins_url( '', PFWP_PLUGIN_FILE ) );
}

if ( ! defined( 'PFWP_TEMPLATE_DIR' ) ) {
	define( 'PFWP_TEMPLATE_DIR', get_template_directory() );
}

if ( ! defined( 'PFWP_SITE_URL' ) ) {
	define( 'PFWP_SITE_URL', get_site_url() );
}

// Globals
global $pfwp_global_config, $pfwp_ob_replace_vars;

// Utils
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-utils.php';
		
// TODO: add error log and admin_notice message if primary file is not found
$pfwp_global_config = json_decode( @file_get_contents( PFWP_TEMPLATE_DIR . '/pfwp.json' ), false );

if ( property_exists( $pfwp_global_config, 'primary' ) ) {
	$secondary_config_files = glob(PFWP_TEMPLATE_DIR . '\/pfwp.*.json');
	
	foreach( $secondary_config_files as  $secondary_config_file) {
		$secondary_config = json_decode( @file_get_contents( $secondary_config_file ), false );
		$pfwp_global_config->compilations = PFWP_Utils::merge_objects( $pfwp_global_config->compilations, $secondary_config->compilations, array( 'runtime' => array(1) ) );
	}
}

$pfwp_ob_replace_vars = array(
	'search' => array(),
	'replace' => array()
);

// Core
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-core.php';

// TODO: define rest route constants (namespace, version, etc)

// Assets Engine
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-assets.php';

// Component Engine
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-components.php';

// Plugin Engine
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-plugins.php';

// Block Engine
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-blocks.php';

// REST Routes
require PFWP_PLUGIN_DIR . '/classes/class-pfwp-rest.php';
