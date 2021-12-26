<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_menu_creator
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->utils = new uipress_util();
  }

  /**
   * Loads menu editor actions
   * @since 1.0
   */

  public function run()
  {
    ///REGISTER THIS COMPONENT
    add_filter("uipress_register_settings", [$this, "menu_creator_settings_options"], 1, 2);

    $utils = new uipress_util();
    $creatorDisabled = $utils->get_option("menu-creator", "status");

    if ($creatorDisabled == "true") {
      return;
    }

    if (function_exists("is_network_admin")) {
      if (is_network_admin()) {
        return;
      }
    }
    add_action("admin_menu", [$this, "add_menu_item"]);

    if (isset($_GET["page"])) {
      if ($_GET["page"] == "uip-menu-creator") {
        add_action("admin_enqueue_scripts", [$this, "add_scripts"]);
        add_action("wp_print_scripts", [$this, "uip_dequeue_script"], 100);
        add_action("parent_file", [$this, "capture_wp_menu"], 999);
      }
    }

    add_filter("uipress_get_custom_menu", [$this, "deliver_custom_menu"]);
    add_action("init", [$this, "uipress_create_menu_cpt"], 0);

    //AJAX
    add_action("wp_ajax_uipress_get_users_and_roles", [$this, "uipress_get_users_and_roles"]);
    add_action("wp_ajax_uipress_save_menu", [$this, "uipress_save_menu"]);
    add_action("wp_ajax_uipress_get_menus", [$this, "uipress_get_menus"]);
    add_action("wp_ajax_uipress_delete_menu", [$this, "uipress_delete_menu"]);
    add_action("wp_ajax_uipress_switch_menu_status", [$this, "uipress_switch_menu_status"]);
    add_action("wp_ajax_uipress_duplicate_menu", [$this, "uipress_duplicate_menu"]);
    add_action("wp_ajax_uipress_get_menu_items", [$this, "uipress_get_menu_items"]);
  }

  /**
   * Blocks default wp menu output
   * @since 2.2
   */
  public function capture_wp_menu($parent_file)
  {
    ///CHECK FOR CUSTOM MENU FIRST
    $userid = get_current_user_id();

    ///NO CUSTOM MENU SO PREPARE DEFAULT MENU
    global $menu, $submenu, $self, $parent_file, $submenu_file, $plugin_page, $typenow;
    $this->menu = $menu;
    //CREATE MENU CONSTRUCTOR OBJECT
    $mastermenu["self"] = $self;
    $mastermenu["parent_file"] = $parent_file;
    $mastermenu["submenu_file"] = $submenu_file;
    $mastermenu["plugin_page"] = $plugin_page;
    $mastermenu["typenow"] = $typenow;
    $mastermenu["menu"] = $menu;
    $mastermenu["submenu"] = $submenu;
    ///FORMAT DEFAULT MENU
    $formattedMenu = $this->uip_format_admin_menu($mastermenu);
    $mastermenu["menu"] = $formattedMenu;

    set_transient("uip-admin-menu-" . $userid, $mastermenu, 0.5 * HOUR_IN_SECONDS);

    return $parent_file;
  }

  /**
   * Processes menu for output
   * @since 2.2
   */
  public function uip_format_admin_menu($mastermenu, $submenu_as_parent = true)
  {
    $self = $mastermenu["self"];
    $parent_file = $mastermenu["parent_file"];
    $submenu_file = $mastermenu["submenu_file"];
    $plugin_page = $mastermenu["plugin_page"];
    $typenow = $mastermenu["typenow"];
    $menu = $mastermenu["menu"];
    $submenu = $mastermenu["submenu"];

    $first = true;
    $returnmenu = [];
    $returnsubmenu = [];

    foreach ($menu as $key => $item) {
      $admin_is_parent = false;
      $class = [];
      $aria_attributes = "";
      $aria_hidden = "";
      $is_separator = false;

      if ($first) {
        $class[] = "wp-first-item";
        $first = false;
      }

      $submenu_items = [];
      if (!empty($submenu[$item[2]])) {
        $class[] = "wp-has-submenu";
        $submenu_items = $submenu[$item[2]];
      }

      if (($parent_file && $item[2] === $parent_file) || (empty($typenow) && $self === $item[2])) {
        if (!empty($submenu_items)) {
          $class[] = "wp-has-current-submenu wp-menu-open";
          $item["active"] = true;
        } else {
          $class[] = "current";
          $aria_attributes .= 'aria-current="page"';
          $item["active"] = true;
        }
      } else {
        $class[] = "wp-not-current-submenu";
        $item["active"] = false;
        if (!empty($submenu_items)) {
          $aria_attributes .= 'aria-haspopup="true"';
        }
      }

      if (!empty($item[4])) {
        $class[] = esc_attr($item[4]);
      }

      $class = implode(" ", $class);
      $id = !empty($item[5]) ? ' id="' . preg_replace("|[^a-zA-Z0-9_:.]|", "-", $item[5]) . '"' : "";
      $img = "";
      $img_style = "";
      $img_class = " dashicons-before";

      if (false !== strpos($class, "wp-menu-separator")) {
        $is_separator = true;
      }

      $title = wptexturize($item[0]);

      // Hide separators from screen readers.
      if ($is_separator) {
        $aria_hidden = ' aria-hidden="true"';

        $item["type"] = "sep";

        if (isset($menu_item["name"])) {
          $item["name"] = $item["name"];
        }
      } else {
        $item["id"] = $item[5];
        $item["name"] = $item[0];
        $item["icon"] = $this->get_icon($item);
        $item["classes"] = $class;
        $item["type"] = "menu";
      }

      //$classes = $this->get_menu_clases($menu_item,$thesubmenu);

      if ($is_separator) {
      } elseif ($submenu_as_parent && !empty($submenu_items)) {
        $submenu_items = array_values($submenu_items); // Re-index.
        $menu_hook = get_plugin_page_hook($submenu_items[0][2], $item[2]);
        $menu_file = $submenu_items[0][2];
        $pos = strpos($menu_file, "?");

        if (false !== $pos) {
          $menu_file = substr($menu_file, 0, $pos);
        }

        if (!empty($menu_hook) || ("index.php" !== $submenu_items[0][2] && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !file_exists(ABSPATH . "/wp-admin/$menu_file"))) {
          $admin_is_parent = true;
          $item["url"] = "admin.php?page=" . $submenu_items[0][2];
        } else {
          $item["url"] = $submenu_items[0][2];
        }
      } elseif (!empty($item[2]) && current_user_can($item[1])) {
        $menu_hook = get_plugin_page_hook($item[2], "admin.php");
        $menu_file = $item[2];
        $pos = strpos($menu_file, "?");

        if (false !== $pos) {
          $menu_file = substr($menu_file, 0, $pos);
        }

        if (!empty($menu_hook) || ("index.php" !== $item[2] && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !file_exists(ABSPATH . "/wp-admin/$menu_file"))) {
          $admin_is_parent = true;
          $item["url"] = "admin.php?page=" . $item[2];
        } else {
          $item["url"] = $item[2];
        }
      }

      if (!empty($submenu_items)) {
        $first = true;
        $tempsub = [];

        foreach ($submenu_items as $sub_key => $sub_item) {
          $sub_item["active"] = false;

          if (!current_user_can($sub_item[1])) {
            continue;
          }

          $class = [];
          $aria_attributes = "";

          if ($first) {
            $class[] = "wp-first-item";
            $first = false;
          }

          $menu_file = $item[2];
          $pos = strpos($menu_file, "?");

          if (false !== $pos) {
            $menu_file = substr($menu_file, 0, $pos);
          }

          // Handle current for post_type=post|page|foo pages, which won't match $self.
          $self_type = !empty($typenow) ? $self . "?post_type=" . $typenow : "nothing";

          if (isset($submenu_file)) {
            if ($submenu_file === $sub_item[2]) {
              $class[] = "current";
              $aria_attributes .= ' aria-current="page"';
            }
            // If plugin_page is set the parent must either match the current page or not physically exist.
            // This allows plugin pages with the same hook to exist under different parents.
          } elseif (
            (!isset($plugin_page) && $self === $sub_item[2]) ||
            (isset($plugin_page) && $plugin_page === $sub_item[2] && ($item[2] === $self_type || $item[2] === $self || file_exists($menu_file) === false))
          ) {
            $class[] = "current";
            $aria_attributes .= ' aria-current="page"';
          }

          if (!empty($sub_item[4])) {
            $class[] = esc_attr($sub_item[4]);
          }

          $class = $class ? ' class="' . implode(" ", $class) . '"' : "";

          $menu_hook = get_plugin_page_hook($sub_item[2], $item[2]);
          $sub_file = $sub_item[2];
          $pos = strpos($sub_file, "?");
          if (false !== $pos) {
            $sub_file = substr($sub_file, 0, $pos);
          }

          $title = wptexturize($sub_item[0]);

          if ($aria_attributes != "") {
            $sub_item["active"] = true;
          }

          if (!empty($menu_hook) || ("index.php" !== $sub_item[2] && file_exists(WP_PLUGIN_DIR . "/$sub_file") && !file_exists(ABSPATH . "/wp-admin/$sub_file"))) {
            // If admin.php is the current page or if the parent exists as a file in the plugins or admin directory.
            if ((!$admin_is_parent && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !is_dir(WP_PLUGIN_DIR . "/{$item[2]}")) || file_exists($menu_file)) {
              $sub_item_url = add_query_arg(["page" => $sub_item[2]], $item[2]);
            } else {
              $sub_item_url = add_query_arg(["page" => $sub_item[2]], "admin.php");
            }

            $sub_item_url = $sub_item_url;
            //echo "<li$class><a href='$sub_item_url'$class$aria_attributes>$title</a></li>";
            $sub_item["url"] = $sub_item_url;
          } else {
            //echo "<li$class><a href='{$sub_item[2]}'$class$aria_attributes>$title</a></li>";
            $sub_item["url"] = $sub_item[2];
          }

          $sub_item["name"] = $sub_item[0];
          $sub_item["id"] = $item["id"] . $sub_item["url"];
          $sub_item["type"] = "menu";
          array_push($tempsub, $sub_item);
        }

        $item["submenu"] = $tempsub;
        //echo '</ul>';
      }
      //echo '</li>';
      $submenu_items = [];
      if (!empty($submenu[$item[2]])) {
        $returnsubmenu[$item[2]] = $tempsub;
      }

      array_push($returnmenu, $item);
    }

    return $returnmenu;
  }

  /**
   * Gets menu icon
   * @since 2.2
   */

  public function get_icon($menu_item)
  {
    /// LIST OF AVAILABLE MENU ICONS
    $icons = [
      "dashicons-dashboard" => "grid_view",
      "dashicons-admin-post" => "article",
      "dashicons-database" => "perm_media",
      "dashicons-admin-media" => "collections",
      "dashicons-admin-page" => "description",
      "dashicons-admin-comments" => "forum",
      "dashicons-admin-appearance" => "palette",
      "dashicons-admin-plugins" => "extension",
      "dashicons-admin-users" => "people",
      "dashicons-admin-tools" => "build_circle",
      "dashicons-chart-bar" => "analytics",
      "dashicons-admin-settings" => "tune",
    ];

    // SET MENU ICON
    $theicon = "";
    $wpicon = $menu_item[6];

    if (isset($menu_item["icon"])) {
      if ($menu_item["icon"] != "") {
        ob_start(); ?><span class="uk-icon-button" uk-icon="icon:<?php echo $menu_item["icon"]; ?>;ratio:0.8"></span><?php return ob_get_clean();
      }
    }

    if (isset($icons[$wpicon])) {
      //ICON IS SET BY ADMIN 2020
      ob_start(); ?><span class="material-icons-outlined"><?php echo $icons[$wpicon]; ?></span><?php return ob_get_clean();
    }

    if (!$theicon) {
      if (strpos($wpicon, "http") !== false || strpos($wpicon, "data:") !== false) {
        ///ICON IS IMAGE
        ob_start(); ?><span class="uip-icon-image uip-background-muted uip-border-round uip-h-18 uip-w-18" style="background-image: url(<?php echo $wpicon; ?>);"></span><?php return ob_get_clean();
      } else {
        ///ICON IS ::BEFORE ELEMENT
        ob_start(); ?><div class="wp-menu-image dashicons-before <?php echo $wpicon; ?> uip-background-muted uip-border-round uip-h-18 uip-w-18 uip-icon-image"></div><?php return ob_get_clean();
      }
    }
  }

  /**
   * Dequeue scripts that cause compatibility issues
   * @since 1.4
   */
  public function uip_dequeue_script()
  {
    wp_dequeue_script("wp-ultimo");
    wp_dequeue_script("wu-admin");
    wp_dequeue_script("wu-vue");
    wp_deregister_script("wu-vue");
  }

  /**
   * Returns settings options for settings page
   * @since 2.2
   */
  public function menu_creator_settings_options($settings, $network)
  {
    $utils = new uipress_util();

    ///////FOLDER OPTIONS
    $moduleName = "menu-creator";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Menu Creator", $this->textDomain);
    $category["description"] = __("Creates custom admin menus.", $this->textDomain);
    $category["icon"] = "segment";

    $temp = [];
    $temp["name"] = __("Disable Menu Creator?", $this->textDomain);
    $temp["description"] = __("If disabled, the menu creator will not be available to any users.", $this->textDomain);
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    return $settings;
  }

  public function deliver_custom_menu($menu)
  {
    $custommenu = false;
    $mainSiteId = false;
    $multisiteMenu = false;

    if (!is_main_site() && is_multisite()) {
      $multisiteMenu = $this->get_multisite_menus();

      if ($multisiteMenu != false) {
        return $multisiteMenu;
      }
    }

    $args = [
      "post_type" => "uipress_admin_menu",
      "post_status" => "publish",
      "numberposts" => -1,
      "meta_query" => [
        [
          "key" => "status",
          "value" => "true",
          "compare" => "=",
        ],
      ],
    ];

    $menus = get_posts($args);

    error_log(json_encode($menus));

    foreach ($menus as $menu) {
      $temp = [];
      $temp["id"] = $menu->ID;
      $temp["items"] = get_post_meta($menu->ID, "items", true);
      $temp["subsites"] = get_post_meta($menu->ID, "subsites", true);
      $temp["status"] = get_post_meta($menu->ID, "status", true);
      $temp["roleMode"] = get_post_meta($menu->ID, "role_mode", true);
      $temp["appliedTo"] = get_post_meta($menu->ID, "applied_to", true);

      if ($temp["status"] == "false") {
        continue;
      }

      $status = false;

      if (is_array($temp["appliedTo"]) && count($temp["appliedTo"]) > 0) {
        $status = $this->menu_valid_for_user($temp["appliedTo"], $temp["roleMode"]);
      }

      if ($status && $temp["roleMode"] == "inclusive") {
        if (is_array($temp["items"]) && count($temp["items"]) > 0) {
          $custommenu = $temp["items"];
          break;
        }
      }

      if (!$status && $temp["roleMode"] == "exclusive") {
        if (is_array($temp["items"]) && count($temp["items"]) > 0) {
          $custommenu = $temp["items"];
          break;
        }
      }
    }

    return $custommenu;
  }

  public function get_multisite_menus()
  {
    $mainSiteId = get_main_site_id();
    switch_to_blog($mainSiteId);
    $custommenu = false;
    $args = [
      "post_type" => "uipress_admin_menu",
      "post_status" => "publish",
      "numberposts" => -1,
      "meta_query" => [
        [
          "key" => "status",
          "value" => "true",
          "compare" => "=",
        ],
      ],
    ];

    $menus = get_posts($args);

    foreach ($menus as $menu) {
      $temp = [];
      $temp["id"] = $menu->ID;
      $temp["items"] = get_post_meta($menu->ID, "items", true);
      $temp["subsites"] = get_post_meta($menu->ID, "subsites", true);
      $temp["status"] = get_post_meta($menu->ID, "status", true);
      $temp["roleMode"] = get_post_meta($menu->ID, "role_mode", true);
      $temp["appliedTo"] = get_post_meta($menu->ID, "applied_to", true);

      $status = false;

      if (isset($temp["subsites"]) && $temp["subsites"] == "false") {
        continue;
      }

      if (is_array($temp["appliedTo"]) && count($temp["appliedTo"]) > 0) {
        $status = $this->menu_valid_for_user($temp["appliedTo"], $temp["roleMode"]);
      }

      if ($status && $temp["roleMode"] == "inclusive") {
        if (is_array($temp["items"]) && count($temp["items"]) > 0) {
          $custommenu = $temp["items"];
          break;
        }
      }

      if (!$status && $temp["roleMode"] == "exclusive") {
        if (is_array($temp["items"]) && count($temp["items"]) > 0) {
          $custommenu = $temp["items"];
          break;
        }
      }
    }

    restore_current_blog();

    return $custommenu;
  }

  public function menu_valid_for_user($rolesandusernames, $mode)
  {
    if (!function_exists("wp_get_current_user")) {
      return false;
    }

    $current_user = wp_get_current_user();

    $current_name = $current_user->display_name;
    $current_roles = $current_user->roles;
    $formattedroles = [];
    $all_roles = wp_roles()->get_names();

    if (in_array($current_name, $rolesandusernames)) {
      return true;
    }

    ///MULTISITE SUPER ADMIN
    if (is_super_admin() && is_multisite()) {
      if (in_array("Super Admin", $rolesandusernames)) {
        return true;
      } else {
        return false;
      }
    }

    ///NORMAL SUPER ADMIN
    if ($current_user->ID === 1) {
      if (in_array("Super Admin", $rolesandusernames)) {
        return true;
      } else {
        return false;
      }
    }

    foreach ($current_roles as $role) {
      $role_name = $all_roles[$role];
      if (in_array($role_name, $rolesandusernames)) {
        return true;
      }
    }
  }
  /**
   * Creates custom folder post type
   * @since 1.4
   */
  public function uipress_create_menu_cpt()
  {
    $labels = [
      "name" => _x("Admin Menu", "post type general name", $this->textDomain),
      "singular_name" => _x("admin menu", "post type singular name", $this->textDomain),
      "menu_name" => _x("Admin Menus", "admin menu", $this->textDomain),
      "name_admin_bar" => _x("Admin Menu", "add new on admin bar", $this->textDomain),
      "add_new" => _x("Add New", "Admin Menu", $this->textDomain),
      "add_new_item" => __("Add New Admin Menu", $this->textDomain),
      "new_item" => __("New Admin Menu", $this->textDomain),
      "edit_item" => __("Edit Admin Menu", $this->textDomain),
      "view_item" => __("View Admin Menu", $this->textDomain),
      "all_items" => __("All Admin Menus", $this->textDomain),
      "search_items" => __("Search Admin Menus", $this->textDomain),
      "not_found" => __("No Admin Menus found.", $this->textDomain),
      "not_found_in_trash" => __("No Admin Menus found in Trash.", $this->textDomain),
    ];
    $args = [
      "labels" => $labels,
      "description" => __("Description.", "Add New Admin Menu"),
      "public" => false,
      "publicly_queryable" => false,
      "show_ui" => false,
      "show_in_menu" => false,
      "query_var" => false,
      "has_archive" => false,
      "hierarchical" => false,
    ];
    register_post_type("uipress_admin_menu", $args);
  }
  /**
   * Fetches users and roles
   * @since 2.0.8
   */

  public function uipress_get_menu_items()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $userid = get_current_user_id();
      $menu = get_transient("uip-admin-menu-" . $userid);
      $returndata["menu"] = $menu;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Fetches users and roles
   * @since 2.0.8
   */

  public function uipress_get_menus()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $returndata = [];

      $args = [
        "post_type" => "uipress_admin_menu",
        "post_status" => "publish",
        "numberposts" => -1,
      ];

      $menus = get_posts($args);
      $formattedmenus = [];

      foreach ($menus as $menu) {
        $temp = [];
        $temp["id"] = $menu->ID;
        $temp["name"] = esc_html(get_the_title($menu->ID));
        $temp["items"] = get_post_meta($menu->ID, "items", true);
        $temp["status"] = get_post_meta($menu->ID, "status", true);
        $temp["subsites"] = get_post_meta($menu->ID, "subsites", true);
        $temp["roleMode"] = get_post_meta($menu->ID, "role_mode", true);
        $temp["appliedTo"] = get_post_meta($menu->ID, "applied_to", true);

        if (!is_array($temp["appliedTo"])) {
          $temp["appliedTo"] = [];
        }

        if (!is_array($temp["items"])) {
          $temp["items"] = [];
        }

        $temp["date"] = get_the_date(get_option("date_format"), $menu->ID);

        $formattedmenus[] = $temp;
      }

      $returndata["menus"] = $formattedmenus;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_switch_menu_status()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $menuid = $this->utils->clean_ajax_input($_POST["menuid"]);
      $status = $this->utils->clean_ajax_input($_POST["status"]);

      $returndata = [];

      if (!$menuid || $menuid == "" || $status == "") {
        $returndata["error"] = _e("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      update_post_meta($menuid, "status", $status);

      $returndata["message"] = __("Status Updated", $this->textDomain);

      $userid = get_current_user_id();
      delete_transient("uip-custom-admin-menu-" . $userid);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_delete_menu()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $menuid = $this->utils->clean_ajax_input($_POST["menuid"]);

      $returndata = [];

      if (!$menuid || $menuid == "") {
        $returndata["error"] = _e("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      if (!current_user_can("delete_post", $menuid)) {
        $returndata["error"] = _e('You don\'t have permission to delete this', $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $status = wp_delete_post($menuid);

      if (!$status) {
        $returndata["error"] = _e("Unable to delete menu", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $returndata["message"] = __("Menu deleted", $this->textDomain);

      $userid = get_current_user_id();
      delete_transient("uip-custom-admin-menu-" . $userid);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_duplicate_menu()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $menu = $this->utils->clean_ajax_input_html($_POST["menu"]);

      $returndata = [];

      if (!$menu || $menu == "") {
        $returndata["error"] = _e("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      if (!isset($menu["items"]) || !is_array($menu["items"])) {
        $returndata["error"] = _e("Unable to duplicate menu, menu is corrupted", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $my_post = [
        "post_title" => $menu["name"] . " " . __("(copy)", $this->textDomain),
        "post_status" => "publish",
        "post_type" => "uipress_admin_menu",
      ];

      $themenuID = wp_insert_post($my_post);

      if (!$themenuID) {
        $returndata["error"] = __("Unable to duplicate menu", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      update_post_meta($themenuID, "items", $menu["items"]);
      update_post_meta($themenuID, "status", "false");
      update_post_meta($themenuID, "role_mode", $menu["roleMode"]);
      update_post_meta($themenuID, "applied_to", $menu["appliedTo"]);

      $returndata["message"] = __("Menu duplicated", $this->textDomain);
      $returndata["original"] = $menu["items"];

      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Fetches users and roles
   * @since 2.0.8
   */

  public function uipress_save_menu()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $menu = json_decode(stripslashes($_POST["menu"]));

      $sanitized = $this->utils->clean_ajax_input_menu_editor($menu);

      $returndata = [];

      if (!$menu || $menu == "") {
        $returndata["error"] = _e("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      if (!isset($menu->items) || !is_array($menu->items)) {
        $returndata["error"] = _e("Unable to save, menu is corrupted", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $my_post = [
        "post_title" => wp_strip_all_tags($menu->name),
        "post_status" => "publish",
        "post_type" => "uipress_admin_menu",
      ];

      // Insert the post into the database.
      // UPDATE OR CREATE NEW
      if (isset($menu->id) && $menu->id > 0) {
        $my_post["ID"] = $menu->id;
        $themenuID = wp_update_post($my_post);
      } else {
        $themenuID = wp_insert_post($my_post);
      }

      if (!$themenuID) {
        $returndata["error"] = __("Unable to save menu", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      if ($menu->status == true) {
        $stat = "true";
      } else {
        $stat = "false";
      }

      if (isset($menu->subsites) && $menu->subsites == true) {
        $subs = "true";
      } else {
        $subs = "false";
      }

      update_post_meta($themenuID, "items", $menu->items);
      update_post_meta($themenuID, "status", $stat);
      update_post_meta($themenuID, "subsites", $subs);
      update_post_meta($themenuID, "role_mode", $menu->roleMode);
      update_post_meta($themenuID, "applied_to", $menu->appliedTo);

      $returndata["message"] = __("Menu Saved", $this->textDomain);
      $returndata["menuID"] = $themenuID;

      $userid = get_current_user_id();
      delete_transient("uip-custom-admin-menu-" . $userid);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_get_users_and_roles()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-menu-creator-security-nonce", "security") > 0) {
      $term = $this->utils->clean_ajax_input($_POST["searchString"]);

      $returndata = [];

      if (!$term || $term == "") {
        $returndata["error"] = _e("Something went wrong", $this->textDomain);
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
   * Grabs unmodified menu
   * @since 1.4
   */

  public function set_menu($parent_file)
  {
    global $menu, $submenu;
    $this->menu = $this->sort_menu_settings($menu);
    $this->submenu = $this->sort_sub_menu_settings($this->menu, $submenu);

    return $parent_file;
  }

  /**
   * Enqueue menu editor scripts
   * @since 1.4
   */

  public function add_scripts()
  {
    global $menu, $submenu, $parent_file, $submenu_file;

    wp_enqueue_script("vue-menu-creator-js", $this->pathURL . "admin/apps/menu-creator/js/vue-menu-creator.js", ["jquery"], $this->version, false);
    wp_enqueue_script("sortable-js", $this->pathURL . "admin/apps/menu-creator/js/sortable.js", ["jquery"], $this->version, false);
    wp_enqueue_script("vue-sortable-js", $this->pathURL . "admin/apps/menu-creator/js/vuedraggable.umd.js", ["jquery"], $this->version, false);

    ///MENU EDITOR
    wp_enqueue_script("admin-menu-creator-js", $this->pathURL . "admin/apps/menu-creator/js/admin-menu-creator-app.min.js", ["jquery"], $this->version, true);
    wp_localize_script("admin-menu-creator-js", "a2020_menucreator_ajax", [
      "ajax_url" => admin_url("admin-ajax.php"),
      "security" => wp_create_nonce("uipress-menu-creator-security-nonce"),
    ]);
  }

  /**
   * Adds menu editor page to settings
   * @since 1.4
   */

  public function add_menu_item()
  {
    add_options_page("UiPress Menu Creator", __("Menu Creator", $this->textDomain), "manage_options", "uip-menu-creator", [$this, "admin2020_menu_creator_app"]);
  }

  public function add_menu_item_network()
  {
    add_submenu_page(
      "settings.php", // Parent element
      "Menu Creator", // Text in browser title bar
      __("Menu Creator", $this->textDomain), // Text to be displayed in the menu.
      "manage_options", // Capability
      "admin-2020-menu-creator", // Page slug, will be displayed in URL
      [$this, "admin2020_menu_creator_app"] // Callback function which displays the page
    );
  }

  /**
   * Creates menu editor page
   * @since 1.4
   */

  public function admin2020_menu_creator_app()
  {
    $previewImage = $this->pathURL . "assets/img/menu-creator-preview.png"; ?>
		<style>
			  #wpcontent{
				  padding-left: 0;
			  }
			  #wpfooter{
					display: none;
				}
				#wpbody-content{
					padding:0;
				}
		</style>
		
		<div id="menu-creator-app" class="uip-text-normal uip-background-default">
			
			<div class="uip-fade-in uip-hidden" :class="{'uip-nothidden' : !loading}">
			
				<div  v-if="!loading && !dataConnect" class="uip-width-100p uip-position-relative">
					<img class="uip-w-100p " src="<?php echo $previewImage; ?>">
					
					
					<div class="uip-position-absolute uip-top-0 uip-bottom-0 uip-left-0 uip-right-0" 
					style="background: linear-gradient(0deg, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);"></div>
					
					<div class="uip-position-absolute uip-top-0 uip-bottom-0 uip-left-0 uip-right-0 uip-flex uip-flex-center uip-flex-middle">
					  
					  
					  <div class="uip-background-default uip-border-round uip-padding-m uip-shadow uip-flex uip-flex-center uip-flex-column">
						<div class="uip-flex uip-text-l uip-text-bold uip-margin-bottom-s">
						  <span class="material-icons-outlined uip-margin-right-xs">redeem</span>
						  <span><?php _e("Pro Feature", $this->textDomain); ?></span>
						</div> 
						
						<p class="uip-text-normal uip-margin-bottom-m"><?php _e("Upgrade to UiPress Pro to unlock the menu creator", $this->textDomain); ?></p>
						
						<a href="https://uipress.co/pricing/" target="_BLANK" class="uip-button-primary uip-no-underline"><?php _e("See UiPress Pro Plans", $this->textDomain); ?></a>
					  </div>
					  
					</div>
				</div>
				
				<template v-if="!loading && dataConnect">
				
					
					
					<?php $this->build_menu_list(); ?>
					<?php $this->build_editor(); ?>
				
				</template>
			
			</div>
			
		</div>
		
		<?php
  }

  public function build_menu_list()
  {
    ?>
		<div class="uip-padding-m uip-max-w-900 uip-margin-auto" v-if="!ui.editingMode">
			
			<div class="uip-flex uip-margin-bottom-l">
				<div class="uip-flex-grow">
					<div class="uip-text-emphasis uip-text-xxl uip-text-bold">
						<?php _e("Menu Creator", $this->textDomain); ?>
					</div>
				</div>
				
				<div class="">
					<button @click="createNewMenu()" class="uip-button-primary" type="button"><?php _e("New", $this->textDomain); ?></button>
				</div>
			
			</div>
			
			<div v-if="user.allMenus.length < 1" class="uip-padding-m uip-text-center ">
				<p class="uip-text-xl uip-text-muted"><?php _e('Looks like you haven\'t created any admin menus yet', $this->textDomain); ?></p>
				<button class="uip-button-primary " type="button" @click="createNewMenu()"><?php _e("Create your first admin menu", $this->textDomain, $this->textDomain); ?></button>
			</div>
			
			<div v-if="user.allMenus.length > 0" class="uip-background-muted uip-border-round uip-padding-s uip-margin-bottom-s" >
				<div class="uip-flex">
					
					
					<div class="uip-text-bold uip-flex-grow">
						<?php _e("Name", $this->textDomain); ?>
					</div>
					
					<div class="uip-text-bold uip-w-200">
						<?php _e("Status", $this->textDomain); ?>
					</div>
										
					<div class=" uip-text-bold uip-w-200">
						<?php _e("Date", $this->textDomain); ?>
					</div>
					
					<div style="width:40px;">
					</div>
					
					
				</div>
				
			</div>
			
			<template v-for="menu in user.allMenus">
			
				<div class="uip-padding-s">
					
					<div class="uip-flex uip-flex-between">
						
						
						<div class="uip-flex-grow">
							<a href="#" class="uip-text-bold uip-link-muted uip-no-underline uip-text-emphasis" @click="openMenu(menu)">{{menu.name}}</a>
						</div>
						
						<div class="uip-w-200">
							<label class="uip-switch">
							  <input type="checkbox" v-model="menu.status" @change="switchStatus(menu.id, menu.status)">
							  <span class="uip-slider"></span>
							</label>
						</div>
						
						<div class="uip-w-200">
							{{menu.date}}
						</div>
						
						<div style="width:40px;">
							
								
								<uip-dropdown type="icon" icon="more_horiz" pos="botton-left" size="small">
							
									
										
										<ul class="uip-flex uip-flex-column uip-margin-remove">
											<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
												<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex" @click="openMenu(menu)" >
													<span class="material-icons-outlined uip-margin-right-xs">edit</span>
													<?php _e("Edit", $this->textDomain); ?>
												</a>
											</li>
											
											<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
												<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex"@click="duplicateMenu(menu)" >
													<span class="material-icons-outlined uip-margin-right-xs">copy</span>
													<?php _e("Duplicate", $this->textDomain); ?>
												</a>
											</li>
											
											<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
												<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex" @click="exportMenu(menu)" >
													<span class="material-icons-outlined uip-margin-right-xs">file_download</span>
													<?php _e("Export", $this->textDomain); ?>
												</a>
												<a href="#" id="uipress-export-menus" class="uip-hidden"></a>
											</li>
											
											<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
												<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex" @click="confirmDelete(menu)" >
													<span class="material-icons-outlined uip-margin-right-xs">delete</span>
													<?php _e("Delete", $this->textDomain); ?>
												</a>
											</li>
										</ul>
										
								</uip-dropdown>
							
							<!-- END OF DROPDOWN -->
							
							
						</div>
						
						
					</div>
					
				</div>
				
			</template>
			
		</div>
		
		<?php
  }

  public function build_editor()
  {
    $logo = esc_url($this->pathURL . "/assets/img/default_logo.png");
    $dark_logo = "";
    ?>
		
		<div class="uip-padding-s uip-border-box uip-border-bottom uip-border-top uip-background-default" v-if="ui.editingMode">
			<?php $this->build_header(); ?>
		</div>
		
		<div  v-if="ui.editingMode && isSmallScreen()">
			<div class="uip-padding-m">
				<div class="notice">
					<p class="uip-text-bold"><?php _e('Menu creator isn\'t optimised for mobile devices. For best results switch to a larger screen', $this->textDomain); ?></p>
				</div>
			</div>
		</div>
		
		<div  class="uip-flex" v-if="ui.editingMode" style="height:calc(100vh - 73px - var(--uip-toolbar-height)); max-height:calc(100vh - 73px - var(--uip-toolbar-height))">
			
			<div v-if="!isSmallScreen()"
			class="uip-w-300 uip-background-default uip-h-100p uip-border-right uip-overflow-auto uip-padding-s uip-flex uip-flex-column uip-border-box"  >
				
				<div class="uip-w-100p uip-margin-bottom-m">
					
					<div class="uip-background-muted uip-border-round uip-padding-xxs uip-margin-bottom-xs">
						<button type="button" class="uip-button-default uip-w-50p" :class="{ 'uip-background-default' : ui.activeTab == 'items'}" 
						  @click="ui.activeTab = 'items'"> 
							<?php _e("Menu Items", $this->textDomain); ?>
						</button>
						<button type="button" class="uip-button-default uip-w-50p" :class="{ 'uip-background-default' : ui.activeTab == 'settings'}" 
						  @click="ui.activeTab = 'settings'">
							<?php _e("Menu Settings", $this->textDomain); ?>
						</button>
					</div>
					
				</div>
				
				<div class="" v-if="ui.activeTab == 'settings'">
					
					<div class="">
					
						<div class="uip-margin-bottom-m">
							<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Status", $this->textDomain); ?></div>
							<label class="uip-switch">
							  <input type="checkbox" v-model="user.currentMenu.status">
							  <span class="uip-slider"></span>
							</label>
						</div>
						
						<?php if (is_main_site() && is_multisite()) { ?>
						
						<div class="uip-margin-bottom-m">
							<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Apply to subsites", $this->textDomain); ?></div>
							<label class="uip-switch">
							  <input type="checkbox" v-model="user.currentMenu.subsites">
							  <span class="uip-slider"></span>
							</label>
						</div>
						
						<?php } ?>
						
						<div class="uip-margin-bottom-m">
							<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Menu Name", $this->textDomain); ?></div>
							<input v-model="user.currentMenu.name" type="text" placeholder="<?php _e("Menu Name", $this->textDomain); ?>">
						</div>
						
						<div class="uip-margin-bottom-s">
							<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Menu Applies to", $this->textDomain); ?></div>
							<div class=" uip-background-muted uip-border-round uip-padding-xxs uip-margin-bottom-xs">
								<button type="button" class="uip-button-default uip-w-50p" :class="{ 'uip-background-default' : user.currentMenu.roleMode == 'inclusive'}" 
								  @click="user.currentMenu.roleMode = 'inclusive'"> 
									<?php _e("Inclusive", $this->textDomain); ?>
								</button>
								<button type="button" class="uip-button-default uip-w-50p" :class="{ 'uip-background-default' : user.currentMenu.roleMode == 'exclusive'}" 
								  @click="user.currentMenu.roleMode = 'exclusive'">
									<?php _e("Exclusive", $this->textDomain); ?>
								</button>
							</div>
							<p class="uip-text-muted" v-if="user.currentMenu.roleMode == 'inclusive'">
								<?php _e("In Inclusive mode, this menu will load for all Usernames and roles selected below.", $this->textDomain); ?>
							</p>
							<p class="uip-text-muted" v-if="user.currentMenu.roleMode == 'exclusive'">
								<?php _e("In Exclusive mode, this menu will load for every user except those Usernames and roles selected below.", $this->textDomain); ?>
							</p>
						</div>
						
						<div class="uip-margin-bottom-m">
							<multi-select :selected="user.currentMenu.appliedTo"
							:name="'<?php _e("Choose users or roles...", $this->textDomain); ?>'"
							:single='false'
							:placeholder="'<?php _e("Search roles and users...", $this->textDomain); ?>'"></multi-select>
						</div>
					
					</div>
					
				</div>
				
				<div class="uk-padding" v-if="ui.activeTab == 'items'">
					
					<div class="uip-text-emphasis uip-text-bold uip-margin-bottom-s"><?php _e("Add custom Item", $this->textDomain); ?></div>
					
					<div class="uip-margin-bottom-m">
						<div class="uip-margin-bottom-xs">
							<button class="uip-button-default uip-w-100p" @click="addDivider()"><?php _e("New Separator", $this->textDomain); ?></button>
						</div>
						<div class="">
							<button class="uip-button-default uip-w-100p" @click="addBlank()"><?php _e("New Custom Link", $this->textDomain); ?></button>
						</div>
					</div>
					
					
					<div class="uip-text-emphasis uip-text-bold uip-margin-bottom-s"><?php _e("Available Menu Items", $this->textDomain); ?></div>
					
					<div class="uip-margin-bottom-s uip-padding-xxs uip-background-muted uip-border-round">
						<div class="uip-flex uip-flex-center">
							<span class="uip-margin-right-xs uip-text-muted">
								<span class="material-icons-outlined">search</span>
							</span> 
							<input type="search" v-model="master.searchString" placeholder="<?php _e("Search menu items", $this->textDomain); ?>"
							class="uip-blank-input uip-min-width-0 uip-flex-grow">
						</div>
					</div>
					
					
					<div class="" id="">
						
						<draggable 
						v-model="originalMenu" 
						@start="drag=true" 
						@end="drag=false" 
						:clone="cloneMenuItem"
						:group="{ name: 'menuItems', pull: 'clone', put: 'false' }"
						:sort="false"
						item-key="id">
						
						  	<template #item="{element, index, parentindex = index}">
								  
								<div class="">  
							
									<div v-if="element.type != 'sep' && element.name.toLowerCase().includes(master.searchString.toLowerCase())" class="uip-marin-bottom-s" >
										
										<div class="uip-flex uip-icon-hover-container uip-border-round hover:uip-background-muted uip-padding-xxs uip-margin-bottom-xs">
											
											
											<div @click="element.expand = !element.expand" 
											class="uip-flex-grow uip-flex uip-flex-center uip-cursor-pointer uip-text-bold">
												
												<span v-if="element.submenu  && element.submenu.length > 0 && element.expand" 
												class="material-icons-outlined uip-margin-right-xxs" >expand_more</span>
												<span v-if="element.submenu && element.submenu.length > 0 && !element.expand" 
												class="material-icons-outlined uip-margin-right-xxs" >chevron_right</span>
											
												<span  class="uk-text-bold" v-html="element.name" ></span>
											
											</div>
																				
											<a href="#" @click="addToMenu(element)" class="material-icons-outlined uip-no-underline uip-link-default uip-icon-hover">add_circle</a>
											
										</div>
										
									</div>
									
									<template v-if="element.submenu">
										
										<div v-if="element.expand || master.searchString.length > 0" class="uip-margin-left-s uip-padding-xs uip-border-dashed uip-border-round uip-margin-bottom-xs">
										
											<draggable 
											v-model="element.submenu" 
											@start="drag=true" 
											@end="drag=false" 
											:clone="cloneMenuItem"
											:group="{ name: 'menuItems', pull: 'clone', put: 'false' }"
											:sort="false"
											item-key="id">
											
												  <template #item="{element, index, parentindex = index}">
															
															<div class="uip-icon-hover-container">
																
																<div v-if="element.name.toLowerCase().includes(master.searchString.toLowerCase())"
																class="addable_menu_item uip-flex uip-flex-center uip-border-round hover:uip-background-muted uip-padding-xxs">
																	
																	<span  class="uip-flex-grow" v-html="element.name"></span>
																	
																	<a href="#" @click="addToMenu(element)" class="material-icons-outlined uip-no-underline uip-link-default uip-icon-hover">add_circle</a>
																	
																	
																</div>
																
															</div>
														
												  </template>
												  
											  </draggable>
										  
										</div>
										
									</template>
								
								</div>
							
							</template>
						</draggable>
						
					</div>
				</div>
			</div>
			
			<div class="uip-flex-grow  uip-background-muted" style="height:calc(100vh - 73px - var(--uip-toolbar-height)); max-height:calc(100vh - 73px - var(--uip-toolbar-height))">
				
				<div class="uip-padding-l" style="padding-right:0;">
					
					<div class="uip-text-xl uip-text-emphasis uip-margin-bottom-m uip-text-bold"><?php _e("Preview"); ?></div>	
					
					
					<div class="uip-border-round uip-shadow uip-flex uip-background-default" >
						
						<div class="uip-w-250 uip-padding-xs uip-border-right" style="min-height:600px;">
							
							<div class="uip-w-100p"> 
								<div class="uip-padding-xs uip-margin-bottom-s" >
									<img style="height:30px;" alt="<?php echo get_bloginfo("name"); ?>" class="light" src="<?php echo $logo; ?>">
								</div>
							</div>
							
					
							<div id="menu_preview" class="drop-zone uip-max-h-800 uip-overflow-auto"  >
							
								<?php $this->build_menu_area(); ?>
							
							</div>
						
						</div>
						
						<div class="uip-flex-grow">
							
							<div class="uip-padding-l" style="min-height:600px;">
								
								<div><?php $this->add_loader_placeholder(); ?></div>
								<div><?php $this->add_loader_placeholder(); ?></div>
								
							</div>
							
						</div>
					
					</div>
				
				</div>
				
			</div>
			
		</div>
		
		<?php
  }

  public function build_menu_area()
  {
    ?>
		
		
		<div v-if="user.currentMenu.items.length < 1" class="uip-text-meta">
			<?php _e("Add some menu items from the left toolbar to get started", $this->textDomain); ?>
		</div>
		
		
		<draggable 
		  v-model="user.currentMenu.items" 
		  group="menuItems" 
		  @start="drag=true" 
		  @end="drag=false" 
		  @change="itemsMoved"
		  item-key="id">
		  <template #item="{element, index, parentindex = index}">
			
			<span class="uip-display-block uip-margin-bottom-xxs">
				
				<div  v-if="element.type == 'sep'" 
				class="uip-padding-xs uip-border-round uip-background-muted uip-margin-bottom-xxs uip-margin-bottom-s uip-margin-top-s uip-icon-hover-container uip-cursor-pointer" :class="element.userClasses">
					
					<div v-if="!element.name" class="addable_menu_item uip-flex uip-flex-between uip-flex-middle">
						<span @click="editMenuItem(element)"><?php _e("Separator", $this->textDomain); ?></span>
						
						<a href="#" class="add_menu_item uk-link-muted uip-link-muted uip-icon-hover uip-no-underline" 
						  @click="removeMenuItem(index)">
							  <span class="material-icons-outlined">delete_forever</span>
						</a>
					</div>
					
					<div v-if="element.name.length > 0" class="addable_menu_item uip-flex uip-flex-between uip-flex-middlen">
						<span @click="editMenuItem(element)">{{element.name}}</span>
						
						<a href="#" class="add_menu_item uk-link-muted uip-link-muted uip-icon-hover uip-no-underline" 
						  @click="removeMenuItem(index)">
							  <span class="material-icons-outlined">delete_forever</span>
						</a>
						
					</div>
					
				</div>
				
				<div v-if="element.type == 'menu' || element.type == 'submenu'" 
				class="uip-border-round addable_menu_item uip-margin-bottom-xxs uip-padding-xxs hover:uip-background-muted uip-icon-hover-container"
				:class="element.userClasses">
					
					<div class="uip-flex uip-flex-between uip-flex-middle">
						
						<div class="uip-flex uip-text-bold">
							
							<div @click="element.expand = !element.expand" class="uip-margin-right-xxs">
								<span v-if="element.expand" class="material-icons-outlined">expand_more</span>
								<span v-if="!element.expand"  class="material-icons-outlined">chevron_right</span>
							</div>
							
							
							<div @click="editMenuItem(element)" class="uip-flex uip-cursor-pointer">
								
								<span v-if="element.icon"  class="uip-margin-right-xs" v-html="element.icon" ></span>
							
								<span  class="uk-text-bold" v-html="element.name" ></span>
							
							</div>
						
						</div>
						
						
							
						<a href="#" class="add_menu_item uip-link-muted uip-icon-hover uip-no-underline" 
						  @click="removeMenuItem(index)">
							<span class="material-icons-outlined" >delete_forever</span>
						</a>
							
					</div>
					
				</div>
				
				
				<div v-if="element.expand" class="sub_menu_drag uip-border-dashed uip-border-round uip-h-40 uip-margin-left-m uip-padding-xxs">
					
					<draggable 
					  v-model="element.submenu" 
					  group="menuItems" 
					  @start="drag=true" 
					  @end="drag=false"
					  item-key="name">
					  <template #item="{element, index, parentPlace = parentindex}" >
						  
						  <div class="uip-border-round addable_menu_item  uip-padding-xxs hover:uip-background-muted uip-icon-hover-container uip-flex"  :class="element.userClasses">
						  
							  <span class="uip-flex-grow" @click="editMenuItem(element)" style="cursor:pointer" v-html="element.name"></span>
							  
							  <a href="#" class="add_menu_item uip-link-muted uip-icon-hover uip-no-underline" 
							  @click="removeSubMenuItem(index, parentPlace)">
								  <span class="material-icons-outlined">
								  delete_forever
								</span>
							  </a>
							  
							  
						  </div>
						  
					  </template>
					</draggable>
					
				</div>
				
			</span>
			
			
			
			
			
		   </template>
		</draggable>
		
		
		<div v-if="ui.editPanel" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal"
		style="background:rgba(0,0,0,0.3);z-index:99999;top:0;left:0;right:0;max-height:100vh" :class="{'uip-nothidden' : ui.editPanel}">
		
			<div class="uip-flex uip-w-100p">
				<div class="uip-flex-grow" @click="ui.editPanel = false" ></div>
				<!-- OFFCANVAS SIDE PANEL -->
				<div class="uip-w-500 uip-background-default uip-padding-m uip-text-normal uip-h-viewport" style="max-height: 100vh;">
					
					<div class="uip-flex uip-flex-center uip-margin-bottom-m">
						<div class="uip-flex-grow">
							<div class="uip-text-bold uip-text-l uip-text-emphasis"><?php _e("Edit Menu Item", $this->textDomain); ?></div>
						</div>
						<div @click="ui.editPanel = false"
						 class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">
							close
						 </div>
					</div>
					
					<div class="uip-margin-bottom-s">
						<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Name", $this->textDomain); ?></div>
						<input class="uip-w-100p" v-model="user.currentItem.name " type="text" placeholder="<?php _e("Name", $this->textDomain); ?>">
					</div>
					
					<div class="uip-margin-bottom-s" v-if="user.currentItem.type != 'sep'">
						<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Link", $this->textDomain); ?></div>
						<input class="uip-w-100p" v-model="user.currentItem.href" type="text" placeholder="<?php _e("Link", $this->textDomain); ?>">
					</div>
					
					<div class="uip-margin-bottom-s">
						<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Classes", $this->textDomain); ?></div>
						<input class="uip-w-100p" v-model="user.currentItem.userClasses" type="text" 
						placeholder="<?php _e("Custom classes", $this->textDomain); ?>">
					</div>
					
					<div class="uip-margin-bottom-s" v-if="user.currentItem.type != 'sep'">
						<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Opens in new tab?", $this->textDomain); ?></div>
						<div class="uip-w-200">
							<label class="uip-switch">
							  <input type="checkbox" v-model="user.currentItem.blankPage">
							  <span class="uip-slider"></span>
							</label>
						</div>
					</div>
					
					<div class="uip-margin-bottom-s" v-if="user.currentItem.type != 'sep'">
						<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Icon", $this->textDomain); ?></div>
						<div class="uip-position-relative">
							<icon-select :translations="translations" :menuitemicon="user.currentItem.icon" 
							@iconchange="user.currentItem.icon = getdatafromIcon($event)"></icon-select>
						</div>
					</div>
					
					
				</div>
			</div>	
		</div>
		
		
		<?php
  }

  public function build_header()
  {
    $logo = esc_url($this->pathURL . "/assets/img/default_logo.png"); ?>
	
	<div class="uip-flex" >
		<div class="uip-flex-grow">
			<div class="uip-text-bold uip-text-emphasis uip-text-l uip-margin-bottom-xxs"><?php _e("Menu Creator", $this->textDomain); ?></div>
			<a v-if="ui.editingMode" @click="ui.editingMode = false" href="#" class="uip-link-muted uip-no-outline uip-no-underline uip-text-muted uip-flex">
				<span class="material-icons-outlined " >chevron_left</span>
				<?php _e("Back to all menus", $this->textDomain); ?>
			</a>
		</div>
		<div class="">
			
			<div class="uip-flex uip-flex-middle">
				
				<button class="uip-button-primary uip-margin-right-xs" @click="saveSettings()"><?php _e("Save", $this->textDomain); ?></button>
				
				<uip-dropdown type="icon" icon="tune" pos="botton-left">
					
						
						<ul class="uip-flex uip-flex-column uip-margin-remove">
							<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
								<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex" @click="exportMenu(user.currentMenu)" >
									<span class="material-icons-outlined uip-margin-right-xxs"  >file_download</span>
									<?php _e("Export", $this->textDomain); ?>
									<a href="#" id="uipress-export-menus" class="uip-hidden"></a>
								</a>
							</li>
							
							<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
								<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex" >
									<label class="uip-flex">
										<span class="material-icons-outlined uip-margin-right-xxs">file_upload</span>
										<?php _e("Import Menu", $this->textDomain); ?>
										<input hidden accept=".json" type="file" single="" id="uipress_import_menu" @change="import_menu()">
									</label>
								</a>
							</li>
							
							<li class="uip-padding-xxs hover:uip-background-grey uip-border-round">
								<a href="#" class="uip-link-default uip-no-underline uip-no-outline uip-flex" @click="reset_settings()">
									<span class="material-icons-outlined uip-margin-right-xxs" >restart_alt</span>
									<?php _e("Reset Settings", $this->textDomain); ?></a>
								</a>
							</li>
							
						</ul>	
						
				</uip-dropdown>
			</div>
		</div>
	</div>
	<?php
  }

  public function add_loader_placeholder()
  {
    ?>
		
		<svg
		  role="img"
		  width="70%"
		  height="84"
		  aria-labelledby="loading-aria"
		  viewBox="0 0 340 84"
		  preserveAspectRatio="none"
		>
		  <title id="loading-aria">Loading...</title>
		  <rect
			x="0"
			y="0"
			width="100%"
			height="100%"
			clip-path="url(#clip-path)"
			style='fill: url("#fill");'
		  ></rect>
		  <defs>
			<clipPath id="clip-path">
				<rect x="0" y="0" rx="3" ry="3" width="67" height="11" /> 
				<rect x="76" y="0" rx="3" ry="3" width="140" height="11" /> 
				<rect x="127" y="48" rx="3" ry="3" width="53" height="11" /> 
				<rect x="187" y="48" rx="3" ry="3" width="72" height="11" /> 
				<rect x="18" y="48" rx="3" ry="3" width="100" height="11" /> 
				<rect x="0" y="71" rx="3" ry="3" width="37" height="11" /> 
				<rect x="18" y="23" rx="3" ry="3" width="140" height="11" /> 
				<rect x="166" y="23" rx="3" ry="3" width="173" height="11" />
			</clipPath>
			<linearGradient id="fill">
			  <stop
				offset="0.599964"
				stop-color="rgba(175, 175, 175, 11%)"
				stop-opacity="1"
			  >
				
			  </stop>
			</linearGradient>
		  </defs>
		</svg>
		
		<?php
  }
}
