<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_settings
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->menu = [];
    $this->submenu = [];
  }

  /**
   * Loads UiPress settings page
   * @since 2.2
   */

  public function run()
  {
    $current_page = false;
    if (array_key_exists("page", $_GET)) {
      $current_page = $_GET["page"];
    }

    //AJAX
    add_action("wp_ajax_uip_get_options", [$this, "uip_get_options"]);
    add_action("wp_ajax_uip_save_options", [$this, "uip_save_options"]);
    add_action("wp_ajax_uip_get_users_and_roles", [$this, "uip_get_users_and_roles"]);
    add_action("wp_ajax_uip_get_post_types", [$this, "uip_get_post_types"]);
    add_action("wp_ajax_uip_check_licence_key", [$this, "uip_check_licence_key"]);
    add_action("wp_ajax_uip_remove_licence_key", [$this, "uip_remove_licence_key"]);
    add_action("wp_ajax_uip_reset_options", [$this, "uip_reset_options"]);

    //MENU ITEM
    add_action("admin_menu", [$this, "add_menu_item"]);
    add_action("network_admin_menu", [$this, "add_menu_item_network"]);

    //PAGE SPECIFIC
    if ($current_page == "uip-settings") {
      add_action("admin_enqueue_scripts", [$this, "add_scripts_and_styles"]);
    }
  }

  /**
   * Renders Admin Pages
   * @since 1.4
   */

  public function add_menu_item()
  {
    $utils = new uipress_util();
    $override = $utils->get_option("general", "network_override");
    if ($override && is_multisite()) {
      return;
    }
    add_options_page("UiPress", $this->pluginName . " " . __("Settings", $this->textDomain), "manage_options", "uip-settings", [$this, "build_settings_page"]);
  }

  /**
   * Renders Admin Pages Network
   * @since 1.4
   */

  public function add_menu_item_network()
  {
    add_submenu_page(
      "settings.php", // Parent element
      "UiPress", // Text in browser title bar
      $this->pluginName . " " . __("Settings", $this->textDomain), // Text to be displayed in the menu.
      "manage_options", // Capability
      "uip-settings", // Page slug, will be displayed in URL
      [$this, "build_settings_page"] // Callback function which displays the page
    );
  }

  /**
   * Loads all required styles and scripts for UiPress settings
   * @since 2.2
   */

  public function add_scripts_and_styles()
  {
    //CODEJAR
    wp_enqueue_script("a2020-codejar-js", $this->pathURL . "assets/js/codejar/codejar-alt.js", ["jquery"], $this->version);
    wp_enqueue_script("a2020-highlight-js", $this->pathURL . "assets/js/codejar/highlight.js", ["jquery"], $this->version);
    wp_register_style("a2020-codejar-css", $this->pathURL . "assets/js/codejar/highlight.css", [], $this->version);
    wp_enqueue_style("a2020-codejar-css");

    ///SETTINGS PAGE
    wp_enqueue_script("uip-settings", $this->pathURL . "assets/js/uip-settings.min.js", ["uip-app"], $this->version, true);
  }

  /**
   * Gets uip settings object
   * @since 2.2
   */
  public function uip_get_options()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $modules = [];
      $utils = new uipress_util();
      $network = $utils->clean_ajax_input($_POST["network"]);
      $allModules = apply_filters("uipress_register_settings", $modules, $network);
      $returndata["options"] = $allModules;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Gets uip settings object
   * @since 2.2
   */
  public function uip_save_options()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $options = $utils->clean_ajax_input_html($_POST["options"]);

      if (!is_array($options) || !$options) {
        $returndata["error"] = true;
        $returndata["message"] = __("Unable to save user settings", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      update_option("uip-settings", $options);
      $returndata["message"] = __("Settings saved", $this->textDomain);
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Gets uip settings object
   * @since 2.2
   */
  public function uip_reset_options()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      update_option("uip-settings", []);
      $returndata["message"] = __("Settings reset", $this->textDomain);
      echo json_encode($returndata);
    }
    die();
  }
  /**
   * Outputs settings page
   * @since 1.4
   */

  public function build_settings_page()
  {
    ///LOAD UP WP IMAGE MODALS
    wp_enqueue_media();

    $settingsUpdater = new uipress_update($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    ?>
		<style>
		  #wpcontent{
			  padding-left: 0;
		  }
		</style>
		
		
		<div id="uip-settings" class="uip-body-font">
			<div class="uip-flex uip-flex-wrap">
				<div class="uip-w-200 uip-padding-m">
          
          <div class="uip-flex uip-flex-center uip-margin-bottom-m">
            <div class="uip-margin-right-s">
              <img :src="defaults.logo" alt="" class="uip-light-logo" style="max-height: 33px;">
              <img :src="defaults.darkLogo" alt="" class="uip-dark-logo" style="max-height: 33px;">
            </div>
            <div class="uip-flex-grow">
              <div class="uip-text-bold uip-text-l uip-text-emphasis"><?php echo $this->pluginName; ?></div>
              <div class="uip-text-muted">
                <?php echo __("Version", $this->textDomain) . " " . $this->version; ?>
              </div>
            </div>
          </div>
          
          <div class="uip-margin-bottom-s">
					 <settings-menu :activemodule="currentModule" :updatemodule="activeModule" :translations="translations" :alloptions="formattedSettings"></settings-menu>
          </div>
          
          <div class="uip-flex uip-margin-bottom-xs">
            <button class="uip-button-primary uip-flex-grow"
            type="button" @click="saveSettings()"><?php _e("Save", $this->textDomain); ?></button>
          </div>
          
          <div class="uip-flex uip-margin-bottom-xs">  
            <template v-if="masterPrefs.dataConnect == true">
              
              <div class="uip-w-50p uip-padding-right-xs">
                <label class="uip-button-default uip-display-block uip-text-center">
                  <?php _e("Import", $this->textDomain); ?>
                  <input hidden accept=".json" type="file" single="" id="uip-import-settings" @change="importSettings()">
                </label>
              </div>
              
              <button class="uip-button-default uip-w-50p"
              type="button" @click="exportSettings()"><?php _e("Export", $this->textDomain); ?></button>
              <a href="#" class="uip-hidden" id="uip-export-settings"></a>
            
            </template>
            
            <template v-else >
              
              <a href="https://uipress.co/pricing/" target="_BLANK" class="uip-no-underline uip-border-round uip-background-primary-wash uip-text-bold uip-text-emphasis" style="padding: var(--uip-padding-button)">
                <div class="uip-flex">
                  <span class="material-icons-outlined uip-margin-right-xs">redeem</span> 
                  <span><?php _e("Unlock Export and Import features with pro", $this->textDomain); ?></span>
                </div> 
              </a>
              
            </template>
            
          </div>
          
          <button class="uip-button-danger uip-w-100p"
          type="button" @click="confirmResetSettings()"><?php _e("Reset Settings", $this->textDomain); ?></button>
          
          
          <?php $settingsUpdater->update_a2020_settings_menu(); ?>
          
				</div>
				
				<div class="uip-flex-grow uip-flex">
					
					<div class="uip-max-w-900 uip-flex-grow uip-margin-auto uip-padding-m">
            <h1 class="uip-margin-bottom-l uip-text-emphasis"><?php _e("Settings", $this->textDomain); ?></h1>
						<output-options :activemodule="currentModule" :translations="translations" :alloptions="formattedSettings"></output-options>
					</div>
					
				</div>
			</div>
		</div>
	  <?php
  }

  /**
   * Fetches users and roles
   * @since 2.2
   */

  public function uip_get_users_and_roles()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $term = $utils->clean_ajax_input($_POST["searchString"]);

      $returndata = [];

      if (!$term || $term == "") {
        $returndata["error"] = __("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $term = strtolower($term);

      $users = new WP_User_Query([
        "search" => "*" . esc_attr($term) . "*",
        "fields" => ["display_name"],
        "search_columns" => ["user_login", "user_nicename", "user_email", "user_url"],
      ]);

      $users_found = $users->get_results();
      $empty_array = [];

      foreach ($users_found as $user) {
        $temp = [];
        $temp["name"] = $user->display_name;
        $temp["label"] = $user->display_name;

        array_push($empty_array, $temp);
      }

      global $wp_roles;

      foreach ($wp_roles->roles as $role) {
        $rolename = $role["name"];

        if (strpos(strtolower($rolename), $term) !== false) {
          $temp = [];
          $temp["label"] = $rolename;
          $temp["name"] = $rolename;

          array_push($empty_array, $temp);
        }
      }

      if (strpos(strtolower("Super Admin"), $term) !== false) {
        $temp = [];
        $temp["name"] = "Super Admin";
        $temp["label"] = "Super Admin";

        array_push($empty_array, $temp);
      }

      $returndata["roles"] = $empty_array;

      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Fetches post types
   * @since 2.2
   */

  public function uip_get_post_types()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $args = [];
      $output = "objects";
      $post_types = get_post_types($args, $output);

      if ($post_types == "") {
        $post_types = [];
      }

      $thePostTypes = [];

      foreach ($post_types as $posy) {
        $name = $posy->name;
        $label = $posy->label;
        $temp = [];
        $temp["name"] = $name;
        $temp["label"] = $label;
        array_push($thePostTypes, $temp);
      }

      echo json_encode($thePostTypes);
    }
    die();
  }

  /**
   * Checks uip licence key
   * @since 2.2
   */

  public function uip_check_licence_key()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $key = $utils->clean_ajax_input($_POST["key"]);
      $returndata = [];

      if (!$key || $key == "") {
        $returndata["error"] = __("No licence key provided", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $debug = new uipress_debug();
      $status = $debug->isValid($key);

      echo json_encode($status);
    }
    die();
  }

  public function uip_remove_licence_key()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $uipOptions = get_option("uip-activation");
      $debug = new uipress_debug();

      if (!$uipOptions) {
        $uipOptions = [];
      }

      if (isset($uipOptions["instance"]) && $uipOptions["instance"] != "") {
        $this->remove_instance($uipOptions["key"], $uipOptions["instance"]);
      }
      $uipOptions["key"] = "";
      $uipOptions["instance"] = "";
      update_option("uip-activation", $uipOptions);

      $debug->update_cache();

      $returndata["message"] = __("Licence removed", $this->textDomain);

      echo json_encode($returndata);
    }
    die();
  }

  public function remove_instance($key, $instance)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.lemonsqueezy.com/v1/licenses/deactivate?license_key={$key}&instance_id={$instance}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);

    $server_output = curl_exec($ch);
    curl_close($ch);
  }
}
