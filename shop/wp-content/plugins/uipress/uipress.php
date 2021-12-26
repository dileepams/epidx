<?php
/*
Plugin Name: UiPress
Plugin URI: https://uipress.co
Description: Powerful WordPress admin extension with a streamlined dashboard, Google Analytics & WooCommerce Integration, intuitive media library, dark mode and much more.
Version: 2.2.9
Author: Admin 2020
Text Domain: uipress
Domain Path: /languages/
Author URI: https://uipress.com
*/

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
  exit();
}

$version = "2.2.9";
$pluginName = "UiPress";
$textDomain = "uipress";
$pluginURL = plugin_dir_url(__FILE__);
$pluginPath = plugin_dir_path(__FILE__);

require $pluginPath . "admin/uipress-controller.php";

$uipress = new uipress_controller($version, $pluginName, $pluginPath, $textDomain, $pluginURL);
$uipress->run();

/// SHOW ERRORS
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
