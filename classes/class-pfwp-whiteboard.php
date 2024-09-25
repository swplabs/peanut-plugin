<?php

class PFWP_WB {
  public static function rewrite_rules() {
    add_rewrite_rule( '_pfwp_wb/components/([a-z0-9-]+)[/]?$', 'index.php?pfwp_component=$matches[1]', 'top' );
  }

  public static function add_query_vars( $query_vars ) {
    $query_vars[] = 'pfwp_component';
    return $query_vars;
  }

  public static function component_template( $template ) {
    if ( get_query_var( 'pfwp_component' ) === false || get_query_var( 'pfwp_component' ) === '' ) {
        return $template;
    }
  
    return PFWP_PLUGIN_DIR . '/views/component.php';
  }
}

add_action( 'init', array( 'PFWP_WB', 'rewrite_rules' ), 2 );

add_filter( 'query_vars', array( 'PFWP_WB', 'add_query_vars' ) );

add_filter( 'template_include', array( 'PFWP_WB', 'component_template' ) );

// TODO: add any addtional filters here to remove things loaded on the frontend
