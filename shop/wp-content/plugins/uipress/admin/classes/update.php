<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_update
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->transient = "uip-update-transient";
    $this->transientFailed = "uip-failed-transient";
    $this->updateURL = "https://uipress.co/validate/latest.php";
    $this->expiry = 12 * HOUR_IN_SECONDS;
  }

  /**
   * Registers plugin licence
   * @since 1.4
   */

  public function run()
  {
    $current_page = false;
    if (array_key_exists("page", $_GET)) {
      $current_page = $_GET["page"];
    }
    $disallowedPages = ["uip-settings", "uip-styles", "uip-menu-creator", "uip-overview", "uip-content"];
    if (!in_array($current_page, $disallowedPages)) {
      add_action("admin_head", [$this, "update_a2020_settings"]);
    }

    add_filter("plugins_api", [$this, "a2020_plugin_info"], 20, 3);
    add_filter("site_transient_update_plugins", [$this, "a2020_push_update"]);
    add_action("upgrader_process_complete", [$this, "a2020_after_update"], 10, 2);
    add_filter("plugin_row_meta", [$this, "add_settings_link"], 10, 2);
    add_action("wp_ajax_uip_check_for_updates", [$this, "uip_check_for_updates"]);
    add_action("wp_ajax_uip_import_old_settings", [$this, "uip_import_old_settings"]);
    add_action("wp_ajax_uip_hide_import_old_settings", [$this, "uip_hide_import_old_settings"]);
  }

  /**
   * Sets user preferences from ajax
   * @since 1.4
   */
  public function update_a2020_settings()
  {
    //update_option("uip-settings-updated", "false");
    $oldSettings = get_option("admin2020_settings");
    $network = "false";
    $updated = get_option("uip-settings-updated");

    if (!current_user_can("administrator")) {
      return;
    }

    if ($updated == "true") {
      return;
    }

    if (is_network_admin()) {
      $network = "true";
      $oldSettings = get_option("admin2020_settings_network");
    }

    if (!$oldSettings || !is_array($oldSettings)) {
      return;
    }
    ?>
    <div class="notice update-message uip-border-round uip-no-border uip-background-primary-wash uip-padding-s" id="uip-automatic-settings-importer">
      <div class="uip-text-l uip-text-bold uip-text-emphasis uip-margin-bottom-xs">
        <span><?php echo __("Welcome to UiPress version ", $this->textDomain) . $this->version; ?></span>
        <span class="uip-margin-left-xs">🎉</span>
      </div>
      <div class="uip-text-normal uip-margin-bottom-m"><?php _e("Would you like to automatically import settings from your older version of uipress?", $this->textDomain); ?></div>
      <div class="">
        <button class="uip-button-default uip-margin-right-s" onclick="hideImportSettings()"><?php _e("Don't show again", $this->textDomain); ?></button>
        <button class="uip-button-secondary" onclick="importOldSettings('<?php echo $network; ?>')"><?php _e("Import settings", $this->textDomain); ?></button>
      </div>
    </div>
    <?php
  }

  /**
   * Sets user preferences from ajax
   * @since 1.4
   */
  public function update_a2020_settings_menu()
  {
    $oldSettings = get_option("admin2020_settings");
    $network = "false";

    if (!current_user_can("administrator")) {
      return;
    }

    if (is_network_admin()) {
      $network = "true";
      $oldSettings = get_option("admin2020_settings_network");
    }

    if (!$oldSettings || !is_array($oldSettings)) {
      return;
    }
    ?>
    <div class="uip-border-round uip-no-border uip-background-primary-wash uip-padding-s uip-margin-top-m" id="uip-automatic-settings-importer">
      <div class="uip-text-normal uip-margin-bottom-s"><?php _e("Would you like to automatically import settings from your older version of uipress?", $this->textDomain); ?></div>
      <div class="">
        <button class="uip-button-secondary" onclick="importOldSettings('<?php echo $network; ?>')"><?php _e("Import settings", $this->textDomain); ?></button>
      </div>
    </div>
    <?php
  }
  /**
   * Adds link to look for update
   * @since 1.4
   */
  public function add_settings_link($plugin_meta, $plugin_file_name)
  {
    if ($plugin_file_name == "uipress/uipress.php") {
      $plugin_meta[] = '<a href="#" id="update-uip" onclick="uip_check_for_updates()">' . __("Check for updates", $this->textDomain) . "</a>";
    }
    return $plugin_meta;
  }

  /**
   * Sets user preferences from ajax
   * @since 1.4
   */
  public function uip_check_for_updates()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      delete_transient($this->transient);
      delete_transient($this->transientFailed);

      // info.json is the file with the actual plugin information on your server
      $remote = wp_remote_get($this->updateURL, [
        "timeout" => 10,
        "headers" => [
          "Accept" => "application/json",
        ],
      ]);

      if ($this->is_response_clean($remote)) {
        set_transient($this->transient, $remote, $this->expiry); // 12 hours cache
      } else {
        $message = __("Unable to contact update server", $this->textDomain);
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      if ($remote) {
        $body = json_decode($remote["body"]);

        // your installed plugin version should be on the line below! You can obtain it dynamically of course
        if ($body && version_compare($this->version, $body->version, "<")) {
          set_transient($this->transient, $remote, $this->expiry);
          $returndata = [];
          $returndata["success"] = true;
          $returndata["message"] = __("Update available", $this->textDomain);
          echo json_encode($returndata);
          die();
        }
      }

      $message = __("No Updates available", $this->textDomain);
      $returndata["error"] = true;
      $returndata["message"] = $message;
      echo json_encode($returndata);
      die();
    }
    die();
  }

  /**
   * Imports old uipress settings object
   * @since 1.4
   */
  public function uip_hide_import_old_settings()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      update_option("uip-settings-updated", "true");
      $returndata["success"] = true;
      $returndata["message"] = __("Message dismissed", $this->textDomain);
      echo json_encode($returndata);
      die();
    }
  }

  /**
   * Imports old uipress settings object
   * @since 1.4
   */
  public function uip_import_old_settings()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $network = $utils->clean_ajax_input($_POST["network"]);

      $oldSettings = get_option("admin2020_settings");
      $uipSettings = get_option("uip-settings");

      if ($network == "true") {
        $oldSettings = get_option("admin2020_settings_network");
      }

      if (!$oldSettings || !is_array($oldSettings)) {
        $message = __("No settings to import", $this->textDomain);
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      ////GENERAL OPTIONS
      if (isset($oldSettings["modules"]["admin2020_general"]["network_override"])) {
        $uipSettings["general"]["options"]["network_override"]["value"] = $oldSettings["modules"]["admin2020_general"]["network_override"];
      }
      ///TOOLBAR OPTIONS
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["status"])) {
        if ($oldSettings["modules"]["admin2020_admin_bar"]["status"] == "false") {
          $uipSettings["toolbar"]["options"]["status"]["value"] = "true";
        } else {
          $uipSettings["toolbar"]["options"]["status"]["value"] = "false";
        }
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["disabled-for"])) {
        $uipSettings["toolbar"]["options"]["disabled-for"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["disabled-for"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["light-logo"])) {
        $uipSettings["menu"]["options"]["light-logo"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["light-logo"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["dark-logo"])) {
        $uipSettings["menu"]["options"]["dark-logo"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["dark-logo"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["legacy-admin"])) {
        $uipSettings["toolbar"]["options"]["legacy-admin"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["legacy-admin"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["search-enabled"])) {
        $uipSettings["toolbar"]["options"]["search-disabled"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["search-enabled"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["new-enabled"])) {
        $uipSettings["toolbar"]["options"]["new-enabled"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["new-enabled"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["view-enabled"])) {
        $uipSettings["toolbar"]["options"]["view-enabled"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["view-enabled"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["dark-enabled"])) {
        $uipSettings["general"]["options"]["dark-default"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["dark-enabled"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["load-front"])) {
        $uipSettings["toolbar"]["options"]["load-front"]["value"] = $oldSettings["modules"]["admin2020_admin_bar"]["load-front"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["post-types-search"])) {
        $uipSettings["toolbar"]["options"]["load-front"]["post-types-search"] = $oldSettings["modules"]["admin2020_admin_bar"]["post-types-search"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["post-types-create"])) {
        $uipSettings["toolbar"]["options"]["load-front"]["post-types-create"] = $oldSettings["modules"]["admin2020_admin_bar"]["post-types-create"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["notifcations-disabled-for"])) {
        $uipSettings["toolbar"]["options"]["load-front"]["notifcations-disabled-for"] = $oldSettings["modules"]["admin2020_admin_bar"]["notifcations-disabled-for"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["updates-disabled-for"])) {
        $uipSettings["toolbar"]["options"]["load-front"]["updates-disabled-for"] = $oldSettings["modules"]["admin2020_admin_bar"]["updates-disabled-for"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_bar"]["show-site-logo"])) {
        $uipSettings["menu"]["options"]["load-front"]["show-site-logo"] = $oldSettings["modules"]["admin2020_admin_bar"]["show-site-logo"];
      }
      ///MENU OPTIONS
      if (isset($oldSettings["modules"]["admin2020_admin_menu"]["status"])) {
        if ($oldSettings["modules"]["admin2020_admin_menu"]["status"] == "false") {
          $uipSettings["menu"]["options"]["status"]["value"] = "true";
        } else {
          $uipSettings["menu"]["options"]["status"]["value"] = "false";
        }
      }
      if (isset($oldSettings["modules"]["admin2020_admin_menu"]["disabled-for"])) {
        $uipSettings["menu"]["options"]["load-front"]["disabled-for"] = $oldSettings["modules"]["admin2020_admin_menu"]["disabled-for"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_menu"]["search-enabled"])) {
        $uipSettings["menu"]["options"]["load-front"]["search-enabled"] = $oldSettings["modules"]["admin2020_admin_menu"]["search-enabled"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_menu"]["shrunk-enabled"])) {
        $uipSettings["menu"]["options"]["load-front"]["shrunk-default"] = $oldSettings["modules"]["admin2020_admin_menu"]["shrunk-enabled"];
      }
      ///THEME OPTIONS
      if (isset($oldSettings["modules"]["admin2020_admin_theme"]["status"])) {
        if ($oldSettings["modules"]["admin2020_admin_theme"]["status"] == "false") {
          $uipSettings["theme"]["options"]["status"]["value"] = "true";
        } else {
          $uipSettings["theme"]["options"]["status"]["value"] = "false";
        }
      }
      if (isset($oldSettings["modules"]["admin2020_admin_theme"]["disabled-for"])) {
        $uipSettings["theme"]["options"]["disabled-for"]["value"] = $oldSettings["modules"]["admin2020_admin_theme"]["disabled-for"];
      }
      ///LOGIN PAGE
      if (isset($oldSettings["modules"]["admin2020_admin_login"]["status"])) {
        if ($oldSettings["modules"]["admin2020_admin_login"]["status"] == "false") {
          $uipSettings["login"]["options"]["status"]["value"] = "true";
        }
      }
      if (isset($oldSettings["modules"]["admin2020_admin_login"]["login-redirect"])) {
        $uipSettings["general"]["options"]["redirect-overview"]["value"] = $oldSettings["modules"]["admin2020_admin_login"]["login-redirect"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_login"]["login-redirect-custom"])) {
        $uipSettings["general"]["options"]["redirect-custom"]["value"] = $oldSettings["modules"]["admin2020_admin_login"]["login-redirect-custom"];
      }
      ///OVERVIEW PAGE
      if (isset($oldSettings["modules"]["admin2020_admin_overview"]["status"])) {
        if ($oldSettings["modules"]["admin2020_admin_overview"]["status"] == "false") {
          $uipSettings["overview"]["options"]["status"]["value"] = "true";
        } else {
          $uipSettings["overview"]["options"]["status"]["value"] = "false";
        }
      }
      if (isset($oldSettings["modules"]["admin2020_admin_overview"]["disabled-for"])) {
        $uipSettings["overview"]["options"]["disabled-for"]["value"] = $oldSettings["modules"]["admin2020_admin_overview"]["disabled-for"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_overview"]["editing-disabled-for"])) {
        $uipSettings["overview"]["options"]["editing-disabled-for"]["value"] = $oldSettings["modules"]["admin2020_admin_overview"]["editing-disabled-for"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_overview"]["custom-welcome"])) {
        $uipSettings["overview"]["options"]["custom-welcome"]["value"] = $oldSettings["modules"]["admin2020_admin_overview"]["custom-welcome"];
      }

      ////ADVANCED
      if (isset($oldSettings["modules"]["admin2020_admin_advanced"]["custom-css"])) {
        $uipSettings["advanced"]["options"]["admin-css"]["value"] = $oldSettings["modules"]["admin2020_admin_advanced"]["custom-css"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_advanced"]["custom-js"])) {
        $uipSettings["advanced"]["options"]["admin-js"]["value"] = $oldSettings["modules"]["admin2020_admin_advanced"]["custom-js"];
      }
      if (isset($oldSettings["modules"]["admin2020_admin_advanced"]["head-html"])) {
        $uipSettings["advanced"]["options"]["admin-html"]["value"] = $oldSettings["modules"]["admin2020_admin_advanced"]["head-html"];
      }
      ////DASHBOARD
      if (isset($oldSettings["modules"]["Uipress_module_overview"]["dashcards"])) {
        if (is_array($oldSettings["modules"]["Uipress_module_overview"]["dashcards"])) {
          $cards = $oldSettings["modules"]["Uipress_module_overview"]["dashcards"];
          $uipDashSettings = get_option("uip-overview");
          $uipDashSettings["cards"] = $cards;
          update_option("uip-overview", $uipDashSettings);
        }
      }

      if (is_array($uipSettings)) {
        $uipSettings = update_option("uip-settings", $uipSettings);
      }

      update_option("uip-settings-updated", "true");

      $returndata["success"] = true;
      $returndata["message"] = __("Settings imported", $this->textDomain);
      echo json_encode($returndata);
      die();
    }
  }

  /**
   * Fetches plugin update info
   * @since 1.4
   */

  public function a2020_plugin_info($res, $action, $args)
  {
    // do nothing if this is not about getting plugin information
    if ("plugin_information" !== $action) {
      return $res;
    }

    if (true == get_transient($this->transientFailed)) {
      return $res;
    }

    $plugin_slug = "uipress"; // we are going to use it in many places in this function

    // do nothing if it is not our plugin
    if ($plugin_slug !== $args->slug) {
      return $res;
    }

    // trying to get from cache first
    if (false == ($remote = get_transient($this->transient))) {
      $remote = wp_remote_get($this->updateURL, [
        "timeout" => 10,
        "headers" => [
          "Accept" => "application/json",
        ],
      ]);

      if ($this->is_response_clean($remote)) {
        set_transient($this->transient, $remote, $this->expiry); // 12 hours cache
        $latest = $remote;
      } else {
        set_transient($this->transientFailed, true, $this->expiry); // 12 hours cache
        return $res;
      }
    } else {
      $remote = get_transient($this->transient);
      if ($this->is_response_clean($remote)) {
        $latest = $remote;
      } else {
        set_transient($this->transientFailed, true, $this->expiry);
        return $res;
      }
    }

    $remote = json_decode($latest["body"]);

    $res = new stdClass();

    $res->name = $remote->name;
    $res->slug = $plugin_slug;
    $res->version = $remote->version;
    $res->tested = $remote->tested;
    $res->requires = $remote->requires;
    $res->download_link = $remote->download_url;
    $res->trunk = $remote->download_url;
    $res->trunk = $remote->download_url;
    $res->requires_php = "5.3";
    $res->last_updated = $remote->last_updated;
    $res->sections = [
      "description" => $remote->sections->description,
      "installation" => $remote->sections->installation,
      "changelog" => $remote->sections->changelog,
      // you can add your custom sections (tabs) here
    ];

    if (!empty($remote->sections->screenshots)) {
      $res->sections["screenshots"] = $remote->sections->screenshots;
    }

    $res->banners = [
      "low" => $remote->banners->low,
      "high" => $remote->banners->high,
    ];

    return $res;
  }

  public function is_response_clean($status)
  {
    if (isset($status->errors)) {
      return false;
    }

    if (isset($status["response"]["code"]) && $status["response"]["code"] != 200) {
      return false;
    }

    if (is_wp_error($status)) {
      return false;
    }

    return true;
  }

  /**
   * Pushes plugin update to plugin table
   * @since 1.4
   */

  public function a2020_push_update($transient)
  {
    if (empty($transient->checked)) {
      return $transient;
    }

    if (true == get_transient($this->transientFailed)) {
      return $transient;
    }

    // trying to get from cache first, to disable cache comment 10,20,21,22,24
    if (false == ($remote = get_transient($this->transient))) {
      // info.json is the file with the actual plugin information on your server
      $remote = wp_remote_get($this->updateURL, [
        "timeout" => 10,
        "headers" => [
          "Accept" => "application/json",
        ],
      ]);

      if ($this->is_response_clean($remote)) {
        set_transient($this->transient, $remote, $this->expiry); // 12 hours cache
      } else {
        set_transient($this->transientFailed, true, $this->expiry); // 12 hours cache
        return $transient;
      }
    }

    if ($remote && !is_wp_error($remote)) {
      $remote = json_decode($remote["body"]);

      // your installed plugin version should be on the line below! You can obtain it dynamically of course
      if ($remote && version_compare($this->version, $remote->version, "<")) {
        $res = new stdClass();
        $res->slug = "uipress";
        $res->plugin = "uipress/uipress.php"; // it could be just YOUR_PLUGIN_SLUG.php if your plugin doesn't have its own directory
        $res->new_version = $remote->version;
        $res->tested = $remote->tested;
        $res->package = $remote->download_url;
        $transient->response[$res->plugin] = $res;
      }
    }

    return $transient;
  }

  /**
   * Cleans cache after update
   * @since 1.4
   */

  public function a2020_after_update($upgrader_object, $options)
  {
    if ($options["action"] == "update" && $options["type"] === "plugin") {
      // just clean the cache when new plugin version is installed
      delete_transient($this->upgrade_transient);
    }
  }
}
