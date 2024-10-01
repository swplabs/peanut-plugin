<?php

class PFWP_REST {
  public static $component_json = null;
  public static $data = array();

  public static function get_json( $comp_name ) {
    global $pfwp_global_config;
    
    if ( isset( PFWP_REST::$component_json ) ) {
      return PFWP_REST::$component_json;
    }
    
    $component_file = PFWP_TEMPLATE_DIR . '/components/' . $comp_name . '/component.json';

    if ( property_exists( $pfwp_global_config->compilations->components_elements->metadata, $comp_name ) ) {
      PFWP_REST::$component_json = json_decode( @file_get_contents( PFWP_TEMPLATE_DIR . '/components/' . $comp_name . '/component.json' ), false );
    } else {
      PFWP_REST::$component_json = (object) [];
    }

    return PFWP_REST::$component_json;
  }

  public static function validate_use( $comp_name ) {
    global $pfwp_global_config;

    return property_exists( $pfwp_global_config->compilations->components_elements->metadata, $comp_name ) && $pfwp_global_config->compilations->components_elements->metadata->{$comp_name}->showInRest === true;
  }

  public static function validate_name( $param ) {
    global $pfwp_global_config;
   
    return !!preg_match( '/^([A-Za-z0-9\-\_]+)$/', $param ) && property_exists( $pfwp_global_config->compilations->components_elements->entry_map, $param ) && property_exists( $pfwp_global_config->compilations->components_elements->entry_map->{$param}, 'php_index' ) && PFWP_REST::validate_use( $param );
  }

  public static function validate_data( $comp_name, $key, $param ) {
    global $pfwp_global_config;

    // TODO: add max string length to component/metadata.json > pfwp.json so that it's configurable
    if ( gettype( $param ) !== 'string' || strlen( $param ) >= 2000 ) {
      return false;
    }

    if ( $pfwp_global_config->data_mode === 'path' ) {
      $data = json_decode( base64_decode( $param ), true );
    } else {
      $data = json_decode( base64_decode( rawurldecode( $param ) ), true );
    }
    
    if ( !is_array( $data ) ) {
      return false;
    }

    PFWP_REST::$data = $data;

    if ( $pfwp_global_config->compilations->components_elements->metadata->{$comp_name}->hasSchema === true ) {
      $component_json = PFWP_REST::get_json( $comp_name );
      return rest_validate_value_from_schema( $data, json_decode( json_encode( $component_json->data_schema ), true ), $key ) === true;
    }

    return false;
  }

	public static function register() {
    register_rest_route(
      'pfwp/v1',
      '/components/(?P<name>[A-Za-z0-9\-\_]+)(?:/(?P<data>[\w\=]+))?',
      array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => array( 'PFWP_REST', 'get_component' ),
        'permission_callback' => '__return_true',
        'args' => array(
          'name' => array(
            'required' =>  true,
            'type' => 'string',
            'validate_callback' => array( 'PFWP_REST', 'validate_name' )
          ),
          'data' => array(
            'type' => 'string',
            'validate_callback' => function( $param, $request, $key ) {
              $comp_name = $request['name'];

              return PFWP_REST::validate_name( $comp_name ) && PFWP_REST::validate_data( $comp_name, $key, $param );
            }
          )
        )
      )
    );
	}

  public static function get_component( $request ) {
    $params = $request->get_params();

    $html = PFWP_Components::get_template_part(
      'components/' . $params['name'] . '/index',
      null,
      array_key_exists( 'data', $params ) ? PFWP_REST::$data : array()
    );

    return rest_ensure_response(
      array(
        'html' => str_replace(array("\r", "\n", "\t"), '', $html),
        'assets' => PFWP_Components::$components,
        'data' => PFWP_Components::$js_data
      )
    );
  }
}

add_action( 'rest_api_init', array( 'PFWP_REST', 'register' ) );
