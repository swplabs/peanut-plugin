<?php

class PFWP_WB {
  public static function rewrite_rules() {
    add_rewrite_rule( '_pfwp_wb/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?pfwp_element=$matches[1]&pfwp_element_key=$matches[2]', 'top' );
  }

  public static function add_query_vars( $query_vars ) {
    $query_vars[] = 'pfwp_element';
    $query_vars[] = 'pfwp_element_key';
    return $query_vars;
  }

  public static function get_template( $template ) {
    $element = get_query_var( 'pfwp_element' );
    $key = get_query_var( 'pfwp_element_key' );

    if ( $element === 'components' && isset( $key ) ) {
      return PFWP_PLUGIN_DIR . '/views/component.php';
    }
    
    return $template;  
  }
}

add_action( 'init', array( 'PFWP_WB', 'rewrite_rules' ), 2 );

add_filter( 'query_vars', array( 'PFWP_WB', 'add_query_vars' ) );

add_filter( 'template_include', array( 'PFWP_WB', 'get_template' ) );

// TODO: add any addtional filters here to remove things loaded on the frontend
