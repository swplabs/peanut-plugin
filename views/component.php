<?php
global $pfwp_global_config, $wp_query;

// TODO: $_GET component args as url params;
?>
<html>
  <head>
    <?php
    do_action( 'wp_head' );

    $wb_components_config = $pfwp_global_config->whiteboard->components;

    if ( property_exists( $wb_components_config, 'default_head' ) && is_array( $wb_components_config->default_head ) ) {
      foreach ( $wb_components_config->default_head as $key => $value ) {
        get_template_part( 'components/' . $value . '/index' );
      }
    }
    ?>    
  </head>
  <body>
    <div id="pfwp_component">
      <?php
      $component = $wp_query->query_vars['pfwp_element_key'];

      get_template_part( 'components/' . $component . '/index' );
      ?>
    </div>
    <?php
    do_action( 'wp_footer' );
    ?>
  </body>
</html>
