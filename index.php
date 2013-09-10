<?php
/*
Plugin Name: CB Gallery
Plugin URI: 
Description: Gallery Plugin
Version: 4.2
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

require 'inc/functions.php';

require 'classes/taxonomy-metadata.php';
require 'classes/cb_callable.php';

require 'classes/cb_gallery.php';

global $cb_gallery;
$cb_gallery = new CB_Gallery(__FILE__);

?>