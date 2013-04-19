<?php
/*
Plugin Name: CB Gallery
Plugin URI: 
Description: Gallery Plugin
Version: 2.0
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

require 'class/cb_gallery.php';

global $cb_gallery;
$cb_gallery = new CB_Gallery(__FILE__);


?>