<?php

if ( ! defined( 'PFWP_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class PFWP_Core {
  private static $enable_dev = false;
  
  public static function initialize() {
    global $pfwp_global_config, $pfwp_ob_replace_vars;

    if ( $pfwp_global_config->mode === 'development' ) {
      add_action( 'admin_notices', function () {
        if  (!defined( 'SCRIPT_DEBUG' ) || !SCRIPT_DEBUG ) {
          $class = 'notice notice-error';
          $message = __( '<strong>Peanut For Wordpress</strong>: Script Debugging must be set to true when in development mode for Peanut editor scripts to function. See <a href="https://wordpress.org/documentation/article/debugging-in-wordpress/#script_debug" target="_blank">Wordpress debugging mode</a> for instructions.', 'pfwp' );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
        }
      });
    }
    
    switch ( wp_get_environment_type() ) {
      case 'local':
      case 'development': {
        PFWP_Core::$enable_dev = true;
        
        require PFWP_PLUGIN_DIR . '/classes/class-pfwp-whiteboard.php';
    
        register_activation_hook( PFWP_PLUGIN_FILE, function () {
          PFWP_WB::rewrite_rules();
          flush_rewrite_rules();
        } );
        break;
      }
    }
  }

  public static function is_dev_enabled() {
    return PFWP_Core::$enable_dev;
  }
  
  public static function capture_ob() {
    ob_start();
  }

  public static function process_ob() {
    global $pfwp_ob_replace_vars;

    $html = ob_get_clean();

    // Replace custom output buffer markers
    if (  is_array( $pfwp_ob_replace_vars ) ) {
      $html = str_replace( $pfwp_ob_replace_vars['search'], $pfwp_ob_replace_vars['replace'], $html );
    }

    echo $html;
  }

  public static function sort_assoc_array(&$a) {
    if (!is_array($a)) {
      return false;
    }

    ksort($a);

    foreach ($a as $k=>$v) {
      self::sort_assoc_array($a[$k]);
    }

    return true;
  }
}

// Initialize Core
PFWP_Core::initialize();

// Add Core actions
add_action( 'template_redirect', array( 'PFWP_Core', 'capture_ob' ), 8 );

// TODO: create custom "pfwp_end_marker" action for this
add_action( 'wp_footer', array( 'PFWP_Core', 'process_ob' ), 9999 );

?>
