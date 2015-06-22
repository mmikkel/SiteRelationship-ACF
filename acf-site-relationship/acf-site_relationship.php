<?php

/*
Plugin Name: Advanced Custom Fields: Site Relationship
Plugin URI: https://github.com/mmikkel/SiteRelationship-ACF
Description: Site Relationship field for ACF5
Version: 0.1.2
Author: Mats Mikkel Rummelhoff
Author URI: http://mmikkel.no
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// 1. set text domain
// Reference: https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
load_plugin_textdomain( 'acf-site_relationship', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );


// 2. Include field type for ACF5
// $version = 5 and can be ignored until ACF6 exists
function include_field_types_site_relationship( $version ) {
	include_once('acf-site_relationship-v5.php');
}

add_action('acf/include_field_types', 'include_field_types_site_relationship');


?>
