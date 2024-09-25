<?php

if ( ! defined( 'PFWP_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class PFWP_Assets {
	private static $assets;
	private static $preloads = [];

	public static function initialize() {
		global $pfwp_global_config;

		self::$assets = (object) array(
			'blocks' => property_exists( $pfwp_global_config->compilations, 'blocks_elements' ) ? $pfwp_global_config->compilations->blocks_elements : (object) array(),
			'components' => property_exists( $pfwp_global_config->compilations, 'components_elements' ) ? $pfwp_global_config->compilations->components_elements : (object) array(),
			'plugins' => property_exists( $pfwp_global_config->compilations, 'plugins_elements' ) ? $pfwp_global_config->compilations->plugins_elements : (object) array(),
		);
	}

	private static function register_runtime_script( $script_name ) {
		global $pfwp_global_config;

		// TODO: add hash as version
		if ( isset( $pfwp_global_config->compilations->{$script_name}->runtime ) ) {
			wp_register_script(
				$script_name . '_webpack_runtime',
				'/' . $pfwp_global_config->compilations->{$script_name}->runtime
			);
		}
	}

	public static function register_scripts() {
		self::register_runtime_script( 'plugins_elements' );
		self::register_runtime_script( 'blocks_elements' );
	}

	public static function get_assets( $group = null ) {
		if ( isset( $group ) ) {
			return self::$assets->{$group};
		} else {
			return self::$assets;
		}
	}

	public static function has_asset( $group = 'components', $key = '', $entry_key = '' ) {
		return property_exists( self::$assets->{$group}->entry_map, $key ) && property_exists( self::$assets->{$group}->entry_map->{$key}, $entry_key );
	}

	private static function simple_minify( $content ) {
		$content = preg_replace( '/\s{2,}/', ' ', $content );
		$content = preg_replace( '/\s*([:;{}])\s*/', '$1', $content );
		$content = preg_replace( '/;}/', '}', $content );

		return $content;
	}

	public static function get_asset_key( $group = 'components', $key = '', $entry_key = '' ) {
		$entry_map = self::$assets->{$group}->entry_map->{$key};

		if ( property_exists( $entry_map, $entry_key ) ) {
			return $entry_map->{$entry_key};
		}

		return false;
	}

	public static function get_key_assets( $group = 'components', $key = '', $entry_key = '' ) {
		$asset_key = self::get_asset_key( $group, $key, $entry_key );

		if ( $asset_key ) {
			$assets = self::$assets->{$group}->chunk_groups->{$asset_key}->main_assets;
			$key_assets = (object) array();

			foreach ( $assets as $key => $value ) {
				$file = $value->name;
				$ext  = $value->type;

				if ( ! property_exists( $key_assets, $ext ) ) {
					$key_assets->$ext = array();
				}

				array_push( $key_assets->$ext, $file );
			}

			return $key_assets;
		}

		return (object) array();
	}

	public static function get_wp_deps( $group = 'components', $key = '', $entry_key = '' ) {
		$asset_key = self::get_asset_key( $group, $key, $entry_key );

		if ( $asset_key ) {
			return self::$assets->{$group}->chunk_groups->{$asset_key}->wp_deps->name;
		}

		return false;
	}


	public static function get_content( $file_path, $minify = false, $is_url = true ) {
		global $pfwp_global_config;

		if ( $is_url ) {
			$file_path = parse_url( $file_path)['path'];
		}

		if ( !str_starts_with( $file_path, '/' ) ) {
			$file_path = '/' . $file_path;
		}

		$content = file_get_contents( $pfwp_global_config->wp_root . $file_path );
		return $minify ? self::simple_minify( $content ) : $content;
	}

	public static function add_preload( $tag ) {
		array_push( self::$preloads, $tag . PHP_EOL );
	}

	public static function add_head_preload_tags() {
		global $pfwp_ob_replace_vars;

		array_push( $pfwp_ob_replace_vars['search'], '<!-- pfwp:head:preload -->' );
		array_push( $pfwp_ob_replace_vars['replace'], count( self::$preloads ) > 0 ? implode( '', self::$preloads ) : '' );
	}

	public static function mark_head_preload() {
		echo "\n<!-- pfwp:head:preload -->\n";
	}
}

add_action( 'after_setup_theme', array( 'PFWP_Assets', 'initialize' ), 1 );

add_action( 'init', array( 'PFWP_Assets', 'register_scripts' ), 2 );

// TODO: create custom "pfwp_end_marker" action for this
add_action( 'wp_footer', array( 'PFWP_Assets', 'add_head_preload_tags' ), 2000 );

add_action( 'wp_head', array( 'PFWP_Assets', 'mark_head_preload' ), 1 );
