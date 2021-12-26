<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_controller
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
  }

  /**
   * Loads UiPress Classes and plugins
   * @since 2.2
   */

  public function run()
  {
    require_once $this->path . "admin/classes/uipress-app.php";
    require_once $this->path . "admin/classes/utilities.php";
    require_once $this->path . "admin/classes/settings.php";
    require_once $this->path . "admin/classes/styles.php";
    require_once $this->path . "admin/classes/folders.php";
    require_once $this->path . "admin/classes/debug.php";
    require_once $this->path . "admin/classes/update.php";
    //require_once $this->path . "admin/classes/admin-pages.php";
    //INCLUDE APPS
    require_once $this->path . "admin/apps/menu-creator/menu-creator.php";
    require_once $this->path . "admin/apps/overview/overview.php";
    require_once $this->path . "admin/apps/overview/analytics.php";
    require_once $this->path . "admin/apps/overview/woocommerce.php";
    require_once $this->path . "admin/apps/content/content.php";

    $uipressapp = new uipress_settings($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressapp->run();

    $uipressapp = new uipress_app($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressapp->run();

    $uipressstyles = new uipress_styles($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressstyles->run();

    $uipressUpdate = new uipress_update($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressUpdate->run();

    //$uipressAdminPages = new uipress_admin_page($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    //$uipressAdminPages->run();

    ///START APPS
    $menuCreator = new uipress_menu_creator($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $menuCreator->run();

    $uipressOverview = new uipress_overview($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressOverview->run();

    $uipressAnalytics = new uipress_analytics($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressAnalytics->run();

    $uipressWooCommerce = new uipress_woocommerce($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressWooCommerce->run();

    $uipressContent = new uipress_content($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressContent->run();

    $this->load_plugin_textdomain();
  }

  /**
   * translation files action
   * @since 1.4
   */
  public function load_plugin_textdomain()
  {
    add_action("plugins_loaded", [$this, "uipress_languages_loader"]);
  }

  /**
   * Loads translation files
   * @since 1.4
   */
  public function uipress_languages_loader()
  {
    load_plugin_textdomain($this->textDomain, false, dirname(dirname(plugin_basename(__FILE__))) . "/languages");
  }
}
