<?php

if ( ! defined( 'PFWP_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * @todo disable print out of component during wp-cli import
 */
class PFWP_Components {
	public static $components;
	public static $js_data;
	public static $lazy_load_data;
	public static $comp_css_data;
 	public static $comp_css_metadata;
	private static $inline_styles = array();
		
	public static $current_template_uuid;
	public static $current_template_uuids;
	
	private static function get_key( $slug, $name = null ) {
		$matches = self::match_file_name( $slug );

		if ( isset( $matches['element'] ) ) {
			$key = $matches['element'];

			if ( isset( $name ) && strlen( $name ) > 0 ) {
				return $key . '-' . $name;
			} else {
				return $key;
			}
		} else {
			return null;
		}
	}
		
	private static function match_file_name( $file_name ) {
		preg_match( '/\/?components\/(?P<element>[^\/]+)\/index(\.php)?$/', $file_name, $matches );
		return $matches;
	}
	
	private static function uuid( $key = null, $uuid = null ) {
		if ( !isset( $uuid ) ) {
			$uuid = uniqid();
		}

		if ( $key ) {
			if ( isset( self::$current_template_uuids->{$key} ) ) {
				$uuid = self::$current_template_uuids->{$key};
			} else {
				$uuid = $key . '-' . $uuid;
				self::$current_template_uuids->{$key} = $uuid;
			}
		}	

		return $uuid;	
	}
	
	private static function set_uuid( $key, $uuid = null ) {
		if ( !isset( $uuid ) ) {
			$uuid = uniqid();
		}
		
		return self::uuid( $key, $uuid );
	}
	
	private static function set_js_data( $key, $uuid, $js_data ) {
		if ( !property_exists( self::$js_data, $key ) ) {
			self::$js_data->$key = (object) array();
		}	
		
		self::$js_data->$key->$uuid = $js_data;
	}
	
	public static function initialize() {
		self::$components = (object) array();
		self::$js_data = (object) array();
		self::$lazy_load_data = (object) array();
		self::$current_template_uuids = (object) array();
	}

	// TODO: can we define defaults for a components parse_args in JSON metadata schema and use here if available?
	public static function parse_args( $array1, $array2, $sort = true ) {
		$merged = $array2;

		foreach ( $array1 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged [ $key ] = self::parse_args( $value, $merged [ $key ] );
			} else {
				$merged [ $key ] = $value;
			}
		}

		if ( $sort ) {
			PFWP_Core::sort_assoc_array( $merged );
		}
		
		return $merged;
	}


	/**
	 * Returns component as executed php stored in a variable
	 */
	public static function get_template_part( $slug, $name = null, $args = array() ) {	
		ob_start();

		if ( false !== get_template_part( $slug, $name, $args ) ) {
			return ob_get_clean();
		} else {
			ob_get_clean();
			return '';
		}
	}

	public static function process_assets( $component_assets ) {
		global $pfwp_global_config;

		$assets = (object) array(
			'js'  => array(),
			'css' => array(),
		);

		// TODO: add option to serve css from link src'd files OR style tags
		if ( property_exists( $component_assets, 'css' ) ) {
			foreach ( $component_assets->css as $key => $value ) {
				array_push( $assets->css, $pfwp_global_config->public_path . $value );
			}
		}

		if ( property_exists( $component_assets, 'js' ) ) {
			foreach ( $component_assets->js as $key => $value ) {
				array_push( $assets->js, $pfwp_global_config->public_path . $value );
			}
		}

		return $assets;
	}

	public static function process_template_part( $slug, $name = null, $template = array(), $args = array() ) {
		global $pfwp_global_config;

		// Execute on theme presentation only
		if ( is_admin() || is_feed() ) {
			return;
		} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			if ( !str_starts_with( home_url( $_SERVER['REQUEST_URI'] ),  get_rest_url( null, '/pfwp/v1/components/' ) ) ) {
				return;
			}
		}

		$key = self::get_key( $slug, $name );
		
		if ( !$key ) {
			return;	
		}
				
		$data = self::parse_args(
			$args,
			array(
				'attributes' => array(),
				'pfwp_lazy_loader' => array(
					'resources' => null,
					'conditional' => false,
					'observed' => false,
					'lazy_id' => null,
					'fetch_priority' => 'low',
					'preload_html' => ''
				)
			)
		);
		
		$attrs = $data['attributes'];
		
		$lazy_options = $data['pfwp_lazy_loader'];
		$lazy_resources = $lazy_options['resources'];
		
		self::set_uuid( $key, $lazy_options['lazy_id'] );
				
		if ( $key ) {
			if ( !property_exists( self::$components, $key ) ) {
				// Resource Data Initialization
				self::$components->$key = (object) array(
					'key'    => $key,
					'slug'   => $slug,
					'name'   => $name
				);				
			}
			
			$assets = property_exists( self::$components->$key, 'assets' ) ? self::$components->$key->assets : (object) array(
				'js'  => array(),
				'css' => array()
			);

			if ( $lazy_resources !== 'all' ) {
				if ( PFWP_Assets::has_asset( 'components', $key, 'style' ) ) {
					$style_assets = self::process_assets( PFWP_Assets::get_key_assets( 'components', $key, 'style' ) );

					if ( $pfwp_global_config->css_inject ) {
						$assets->js = array_unique( array_merge( $assets->js, $style_assets->js ) );
					} else {
						$assets->css = array_unique( array_merge( $assets->css, $style_assets->css ) );
					}
				}

				if ( PFWP_Assets::has_asset( 'components', $key, 'view' ) ) {
					$client_assets = self::process_assets( PFWP_Assets::get_key_assets( 'components', $key, 'view' ) );
										
					if ( $lazy_resources === 'js' ) {
						// TODO: handle load of js on observation of component
						if ( !property_exists( self::$lazy_load_data, $key ) ) {
							self::$lazy_load_data->{$key} = (object) array(
								'assets' => (object) array(
									'js'  => $client_assets->js,
									'css' => $client_assets->css
								),
								'instances' => array()
							);
						}
						
						array_push( self::$lazy_load_data->{$key}->instances, self::$current_template_uuids->{$key} );
					} else {
						$assets->js = array_unique( array_merge( $assets->js, $client_assets->js ) );
						$assets->css = array_unique( array_merge( $assets->css, $client_assets->css ) );		
					}
				}

				// Store Data
				self::$components->$key->assets = $assets;
			}
		}
	}

	
	public static function lazy_load( $slug, $name = null, $args = array() ) {
		// check if component exists
		$component =self::get_key( $slug, $name );

		if ( !$component ) {
			return;
		}
	
		$data = self::parse_args(
			$args,
			array(
				'attributes' => array(),
				'pfwp_lazy_loader' => array(
					'resources' => 'all',
					'conditional' => false,
					'observed' => true,
					'lazy_id' => null,
					'fetch_priority' => 'low',
					'preload_html' => ''
				)
			)
		);

		
		$attrs = $data['attributes'];
		$options = $data['pfwp_lazy_loader'];
		$resources = $options['resources'];
		
		$html = '';
		
		if ( $resources === 'all' ) {
			$key = 'pfwp-lazy-loader';

			$file_name = 'components/' . $key . '/index.php';
			
			self::set_uuid( $key, $options['lazy_id'] );
			
			/*
			if ( !property_exists( self::$js_data, $key ) ) {
				self::$js_data->$key = (object) array();
			}
			*/
			
			$base_classes = array( $key );
			
			$html = '<div ' . self::html_attributes( $file_name, $data, array( 'base' => implode( ' ', $base_classes ) ), false, $options['lazy_id'] ) . '>' . $options['preload_html'] . '</div>';
			
			self::create_js_data(
				$file_name,
				array(
					'component' => $component,
					'attributes' => $attrs,
					'options' => $options
				)
			);
			
			self::post_template_part( $file_name );
		} else if ( $resources === 'js' ) {						
			$html = self::get_template_part( $slug, $name, $args );
		}
		
		return $html;
	}
	
	public static function html_classes( $file_name, $component_data, $base_classes = '' ) {
		$key = self::get_key( $file_name );

		$attrs = $component_data['attributes'];
		
		// TODO: namespace with pfwp_css_class_name
		if ( array_key_exists( 'css_class_name', $attrs ) && $css_class_name = $attrs['css_class_name'] ) {
			return 'class="' . $css_class_name . '"';
		}
		
		$classes = array();
			
		if ( array_key_exists( 'variant', $attrs ) && $variant = $attrs['variant'] ) {
			array_push( $classes, $key . '-variant-' . $variant );
		}

		if ( array_key_exists( 'css_classes', $attrs ) && $css_classes = $attrs['css_classes'] ) {
			$classes = array_merge( $classes, $css_classes );
		}
		
  	$appended = count( $classes ) > 0 ? implode( ' ', $classes ) : '';
		
		$html = $base_classes . ( $appended ? ' ' . $appended : '' );
  
  	return $html ? 'class="' . $html . '"' : '';
	}
	
	public static function html_attributes( $file_name, $component_data = array( 'attributes' => array(), 'pfwp_lazy_loader' => array() ), $classes = array( 'base' => '' ), $skip_uuid = null ) {
		$key = self::get_key( $file_name );
		
		$html = array();

		if ( $attr_html = self::html_classes( $file_name, $component_data, $classes['base'] ) ) {
			array_push( $html, $attr_html );
		}

		$lazy_options = array_key_exists( 'pfwp_lazy_loader', $component_data ) ? $component_data['pfwp_lazy_loader'] : array();
		$lazy_resources = array_key_exists( 'resources', $lazy_options ) ? $lazy_options['resources'] : null;
				
		// If UUID not set, check to see if there is a js file
		if ( !isset( $skip_uuid ) ) {
			$skip_uuid = !PFWP_Assets::has_asset( 'components', $key, 'view' );
		}
		
		if ( !$skip_uuid ) {
			$id_html = 'id="' . self::$current_template_uuids->{$key} . '"';
			array_push( $html, $id_html );
			
			self::set_js_data( $key, self::$current_template_uuids->{$key}, (object) array() );
			
			$data_html = '';
			
			// Lazy load data and attributes
			if ( $lazy_resources === 'js' ) {
				$data_html = 'data-pfwp-lazy-loader-status="loading" data-pfwp-lazy-loader="js"';
			} else if ( $lazy_resources === 'all' ) {
				$data_html = 'data-pfwp-lazy-loader-status="loading" data-pfwp-lazy-loader="all"';
			}
			
			array_push( $html, $data_html );
		}
		
		return implode( ' ', $html );
	}

	
	public static function create_js_data( $file_name, $data ) {	
		$key = self::get_key( $file_name );
		
		if ( !$key || !self::$current_template_uuids->{$key} ) {
			return;
		}
				
		self::set_js_data( $key, self::$current_template_uuids->{$key}, $data );
	}

	// Deprecated
	public static function store_instance_js_data( $file_name, $uuid, $data ) {
		/*
		if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return '';
		}
		*/

		$key = self::get_key( $file_name );
		
		self::set_js_data( $key, self::$current_template_uuids->{$key}, $data );
	}
	
	public static function get_uuid( $file_name, $uuid = null ) {
		$key = self::get_key( $file_name );
		
		// backwards compat
		self::set_js_data( $key, self::$current_template_uuids->{$key}, (object) array() );

		return self::uuid( $key, $uuid );
	}
	
	public static function post_template_part( $file_name ) {
		$key = self::get_key( $file_name );
		
		self::$current_template_uuids->{$key} = null;
	}

	public static function inline_instance_js_data() {
		echo '<script>' . PHP_EOL;
		echo '  window.pfwp_comp_instances = ' . json_encode( self::$js_data ) . ';' . PHP_EOL;
		echo '</script>' . PHP_EOL;
	}

	public static function inject_footer() {
		global $pfwp_global_config;

		$metadata = property_exists( $pfwp_global_config->compilations, 'components_elements' ) && property_exists( $pfwp_global_config->compilations->components_elements, 'metadata' ) ? $pfwp_global_config->compilations->components_elements->metadata : (object) array();
		
		$components = PFWP_Assets::get_assets( 'components' );
		
		$component_chunks = property_exists( $components, 'chunk_groups' ) ? $components->chunk_groups : (object) array();
		
		$config_data = (object) array(
			'data_mode' => $pfwp_global_config->data_mode
		);		
		
		echo '<script>' . PHP_EOL;
		echo '  window.pfwp_global_config = ' . json_encode( $config_data ) . ';' . PHP_EOL;
		echo '</script>' . PHP_EOL;
		
		if ( property_exists( $component_chunks, 'pfwp_sdk' ) ) {
			echo '<script src="' . $pfwp_global_config->public_path . $components->chunk_groups->pfwp_sdk->main_assets[0]->name . '" id="pfwp_js_sdk"></script>' . PHP_EOL;
		} else {
			$sdk_js = PFWP_PLUGIN_URL . '/assets/pfwp_sdk.b29a4bcbc006a330bcfb.js';
			echo '<script src="' . $sdk_js . '" id="pfwp_js_sdk"></script>' . PHP_EOL;
		}

		$comp_js_data = (object) [];
		$comp_js_metadata = (object) [];

		// TODO: support component dependencies
		echo '<div id="pfwp_footer_scripts">' . PHP_EOL;

		foreach ( self::$components as $key => $value ) {
			if ( property_exists( $value->assets, 'js' ) ) {
				if ( count( $value->assets->js ) ) {
					$comp_js_data->{$key} = $value->assets->js;
				}

				$js_metadata = property_exists( $metadata, $key ) && property_exists( $metadata->{$key}, 'javascript' ) ? $metadata->{$key}->javascript : false;

				if ( $js_metadata ) {
					$comp_js_metadata->{$key} = $js_metadata;

					if ( property_exists( $js_metadata, 'async' ) && !$js_metadata->async ) {
						foreach ( $value->assets->js as $asset_key => $asset_value ) {
							echo '<script src="' . $asset_value . '" id="pfwp_js_' . $key . '_' . $asset_key . '"></script>' . PHP_EOL;
						}
					}
				}
			}
		}

		echo '</div>' . PHP_EOL;

		$pfwp_js_data = (object) array(
			'components' => array(
				'js' => $comp_js_data,
				'css' => self::$comp_css_data
			),
			'metadata' => array(
				'js' => $comp_js_metadata
			),
			'lazy_load' => self::$lazy_load_data
		);

		echo '<script>' . PHP_EOL;
		echo '  window.pfwpInitialize(document.getElementById(\'pfwp_footer_scripts\'), ' . json_encode( $pfwp_js_data ) . ');'. PHP_EOL;
		echo '</script>' . PHP_EOL;
	}
	
	public static function add_inline_style( $css ) {
		array_push( self::$inline_styles, $css . PHP_EOL );
	}

	public static function add_head_style_var() {
		global $pfwp_global_config, $pfwp_ob_replace_vars;

		$metadata = property_exists( $pfwp_global_config->compilations, 'components_elements' ) && property_exists( $pfwp_global_config->compilations->components_elements, 'metadata' ) ? $pfwp_global_config->compilations->components_elements->metadata : (object) array();

		$styles = '';
		self::$comp_css_data = (object) [];

		foreach ( self::$components as $key => $value ) {
			if ( property_exists( $value->assets, 'css' ) ) {
				if ( count( $value->assets->css ) ) {
					self::$comp_css_data->{$key} = $value->assets->css;
				}

				$external_css = property_exists( $metadata, $key ) && property_exists( $metadata->{$key}, 'css' ) && property_exists( $metadata->{$key}->css, 'external' ) ?  $metadata->{$key}->css->external : false;

				foreach ( $value->assets->css as $asset_key => $asset_value ) {
					if ( $external_css ) {
						$styles .= '<link rel="stylesheet" href="' . $asset_value . '" id="pfwp_css_' . $key . '_' . $asset_key . '"/>' . PHP_EOL;
					} else {
						$styles .= '<style id="pfwp_css_' . $key . '_' . $asset_key . '">' . PFWP_Assets::get_content( $asset_value ) . '</style>' . PHP_EOL;
					}
				}
			}
		}

		array_push( $pfwp_ob_replace_vars['search'], '<!-- pfwp:head:styles -->' );
		array_push( $pfwp_ob_replace_vars['replace'], $styles . ( count( self::$inline_styles ) > 0 ? implode( '', self::$inline_styles ) : '' ) );
	}

	public static function mark_head_styles() {
		echo "\n<!-- pfwp:head:styles -->\n";
	}
}

add_action( 'after_setup_theme', array( 'PFWP_Components', 'initialize' ), 2 );
add_action( 'wp_footer', array( 'PFWP_Components', 'inline_instance_js_data' ), 998 );
add_action( 'wp_footer', array( 'PFWP_Components', 'inject_footer' ), 1000 );

add_action( 'get_template_part', array( 'PFWP_Components', 'process_template_part' ), 10, 4 );
add_action( 'wp_after_load_template', array( 'PFWP_Components', 'post_template_part' ), 11, 1 );

// TODO: create custom "pfwp_end_marker" action for this
add_action( 'wp_footer', array( 'PFWP_Components', 'add_head_style_var' ), 997 );

add_action( 'wp_head', array( 'PFWP_Components', 'mark_head_styles' ), 1000 );
