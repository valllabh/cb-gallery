<?php
function array_merge_recursive_numbered( array &$array1, array &$array2 ) {
	$merged = $array1;
	foreach ( $array2 as $key => &$value ) {
		if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ){
			$merged [$key] = array_merge_recursive_numbered ( $merged [$key], $value );
		} else {
			$merged [$key] = $value;
		}
	}
	return $merged;
}
?>