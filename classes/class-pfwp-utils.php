<?php

if ( ! defined( 'PFWP_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class PFWP_Utils {
  public static function merge_objects( $obj1, $obj2, $excluded_keys = [] ) {
    return self::merge_recursively( $obj1, $obj2, $excluded_keys );
  }

  private static function merge_recursively( $obj1, $obj2, $excluded_keys, $level = 0 ) {
    if (is_object($obj2)) {
      $keys = array_keys(get_object_vars($obj2));
      
      foreach ($keys as $key) {
        if ( in_array( $key, array_keys( $excluded_keys ) ) && in_array( $level, $excluded_keys[$key] ) ) {
          continue;
        }
        
        if (
            isset($obj1->{$key})
            && is_object($obj1->{$key})
            && is_object($obj2->{$key})
        ) {
          $obj1->{$key} = self::merge_recursively( $obj1->{$key}, $obj2->{$key}, $excluded_keys, $level++ );
        } elseif (isset($obj1->{$key})
        && is_array($obj1->{$key})
        && is_array($obj2->{$key})) {
          $obj1->{$key} = self::merge_recursively( $obj1->{$key}, $obj2->{$key}, $excluded_keys, $level++ );
        } else {
          $obj1->{$key} = $obj2->{$key};
        }
      }
    } elseif ( is_array($obj2) ) {
      if (
          is_array($obj1)
          && is_array($obj2)
      ) {
          $obj1 = array_merge_recursive($obj1, $obj2);
      } else {
        $obj1 = $obj2;
      }
    }

    return $obj1;
  }
}
