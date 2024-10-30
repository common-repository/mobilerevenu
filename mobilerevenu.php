<?php

/*
Plugin Name: MobileRevenu
Plugin URI: http://wordpress.org/extend/plugins/mobilerevenu/
Description: Plugin permettant de rentabiliser le Trafic Mobile sur votre blog.
Version: 1.3.1
Author: Mobile Revenu
Author URI:  http://www.mobilerevenu.com
Text Domain: mobilerevenu
License: GNU General Public License
License URI: license.txt
*/

include(dirname(__FILE__).'/includes/_mobilerevenu_plugin.class.php');
register_activation_hook(__FILE__, array('MobileRevenuPlugin', 'install'));
register_deactivation_hook(__FILE__, array('MobileRevenuPlugin', 'uninstall'));
add_filter('plugin_action_links', array($MR,'wp_plugin_links'), 10, 3);
add_action('admin_head', array($MR, 'wp_admin_head'));
add_action('admin_menu', array($MR, 'wp_admin_menu'));
add_action('init', array($MR, 'wp_admin_mce'));
