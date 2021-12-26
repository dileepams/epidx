<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_overview
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
   * Loads menu actions
   * @since 1.0
   */

  public function run()
  {
    ///REGISTER THIS COMPONENT
    add_filter("uipress_register_settings", [$this, "overview_settings_options"], 1, 2);
    ///ADD ACTIOS FOR OVERVIEW PAGE
    add_action("plugins_loaded", [$this, "add_overview_functions"]);

    //AJAX
    add_action("wp_ajax_uipress_get_posts", [$this, "uipress_get_posts"]);
    add_action("wp_ajax_uipress_get_comments", [$this, "uipress_get_comments"]);
    add_action("wp_ajax_uipress_get_system_info", [$this, "uipress_get_system_info"]);
    add_action("wp_ajax_uipress_get_system_health", [$this, "uipress_get_system_health"]);
    add_action("wp_ajax_uipress_save_dash", [$this, "uipress_save_dash"]);
    add_action("wp_ajax_uipress_get_shortcode", [$this, "uipress_get_shortcode"]);
    add_action("wp_ajax_uipress_reset_overview", [$this, "uipress_reset_overview"]);

    ///
    add_filter("uipress_register_card", [$this, "register_default_cards"]);
  }

  /**
   * Adds actions for overview page
   * @since 1.4
   */

  public function add_overview_functions()
  {
    if (!is_admin()) {
      return;
    }

    $utils = new uipress_util();
    $overviewOn = $utils->get_option("overview", "status");
    $overviewDisabledForUser = $utils->valid_for_user($utils->get_option("overview", "disabled-for", true));

    if ($overviewOn == "true" || $overviewDisabledForUser) {
      return;
    }

    add_action("admin_menu", [$this, "add_menu_item"]);
    add_action("network_admin_menu", [$this, "add_menu_item"]);

    if (isset($_GET["page"])) {
      if ($_GET["page"] == "uip-overview") {
        add_action("admin_enqueue_scripts", [$this, "add_scripts"], 0);
        add_action("admin_head", [$this, "add_components"], 0);
        add_action("wp_print_scripts", [$this, "uip_dequeue_script"], 100);
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
  public function overview_settings_options($settings, $network)
  {
    $utils = new uipress_util();

    ///////FOLDER OPTIONS
    $moduleName = "overview";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Overview", $this->textDomain);
    $category["description"] = __("Creates the overview page.", $this->textDomain);
    $category["icon"] = "analytics";

    $temp = [];
    $temp["name"] = __("Disable Overview Page?", $this->textDomain);
    $temp["description"] = __("Overview page will be disaplyed when this option is activated.", $this->textDomain);
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Overview Page Disabled For", $this->textDomain);
    $temp["description"] = __("Overview Page will be disabled for any users or roles you select", $this->textDomain);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Who can edit the overview page?", $this->textDomain);
    $temp["description"] = __("Any role or user chosen here will be able to edit the overview page. If none are chosen it will fall back to administrators only", $this->textDomain);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disable-edit-for";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Custom welcome message", $this->textDomain);
    $temp["description"] = __("Add a custom welcome message here to displayed on the overview page", $this->textDomain);
    $temp["type"] = "code-block";
    $temp["language"] = "HTML";
    $temp["optionName"] = "custom-welcome";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"], false);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    return $settings;
  }

  /**
   * Returns recent posts
   * @since 2.2
   */
  public function uipress_get_posts()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $page = $this->utils->clean_ajax_input($_POST["currentPage"]);

      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $args = [
        "post_type" => "any",
        "post_status" => "publish",
        "posts_per_page" => 5,
        "paged" => $page,
        "date_query" => [
          [
            "after" => $startDate,
            "before" => $endDate,
            "inclusive" => true,
          ],
        ],
      ];

      wp_reset_query();
      $theposts = new WP_Query($args);
      $foundPosts = $theposts->get_posts();

      $formatted = [];

      foreach ($foundPosts as $apost) {
        $postdate = human_time_diff(get_the_date("U", $apost), current_time("timestamp")) . " " . __("ago", $this->textDomain);
        $author_id = $apost->post_author;
        $author_meta = get_the_author_meta("user_nicename", $author_id);

        $temp = [];
        $temp["title"] = get_the_title($apost);
        $temp["href"] = get_the_permalink($apost);
        $temp["author"] = $author_meta;
        $temp["date"] = $postdate;
        $temp["type"] = get_post_type($apost);

        $formatted[] = $temp;
      }

      $returndata = [];

      $returndata["message"] = __("Posts fetched", $this->textDomain);
      $returndata["posts"] = $formatted;
      $returndata["totalFound"] = $theposts->found_posts;
      $returndata["maxPages"] = $theposts->max_num_pages;
      $returndata["testdate"] = $startDate;

      $returndata["nocontent"] = "false";
      if ($theposts->found_posts < 1) {
        $returndata["nocontent"] = __("No posts posted during the date range.", $this->textDomain);
      }

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_save_dash()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $cards = $this->utils->clean_ajax_input_html($_POST["cards"]);
      $network = $this->utils->clean_ajax_input_html($_POST["network"]);

      if (!$cards && !is_array($cards)) {
        $message = __("Unable to save dash at this time", $this->textDomain);
        $returndata["error"] = $message;
        echo json_encode($returndata);
        die();
      }

      $settings["cards"] = $cards;
      update_option("uip-overview", $settings);

      $returndata = [];
      $returndata["message"] = __("Dashboard settings saved", $this->textDomain);
      echo json_encode($returndata);
    }

    die();
  }

  public function uipress_reset_overview()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $settings = get_option("uip-overview");
      $settings["cards"] = false;
      update_option("uip-overview", $settings);

      $returndata = [];
      $returndata["message"] = __("Dashboard settings reset", $this->textDomain);
      echo json_encode($returndata);
    }

    die();
  }

  public function uipress_get_shortcode()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $shortcode = $this->utils->clean_ajax_input($_POST["shortCode"]);

      if (!$shortcode) {
        $message = __("Unable to load shortcode at this time", $this->textDomain);
        echo $this->utils->ajax_error_message($message);
        die();
      }

      $data = do_shortcode(stripslashes($shortcode));

      if (!$data) {
        $message = __("Unable to load shortcode at this time", $this->textDomain);
        echo $this->utils->ajax_error_message($message);
        die();
      }

      $returndata = [];
      $returndata["shortCode"] = $data;
      $returndata["message"] = __("Shortcode loaded", $this->textDomain);
      $returndata["test"] = stripslashes($shortcode);
      echo json_encode($returndata);
    }

    die();
  }

  public function uipress_get_comments()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $page = $this->utils->clean_ajax_input($_POST["currentPage"]);

      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $args = [
        "type" => "comment",
        "status" => "approve",
        "number" => 1000,
        "date_query" => [
          [
            "after" => $startDate,
            "before" => $endDate,
            "inclusive" => true,
          ],
        ],
      ];

      $maxperpage = 5;
      $currentStart = $page * $maxperpage - $maxperpage;
      $currentEnd = $currentStart + $maxperpage + 1;

      $comments_query = new WP_Comment_Query();
      $comments = $comments_query->query($args);

      $formatted = [];
      $count = 0;

      foreach (array_slice($comments, $currentStart) as $acomment) {
        if ($count == 5) {
          break;
        }

        $comment_date = get_comment_date("Y-m-y", $acomment->comment_ID);
        $string = "";

        if ($comment_date != date("Y-m-d")) {
          $string = __("ago", $this->textDomain);
        }

        $commentdate = human_time_diff(get_comment_date("U", $acomment->comment_ID), current_time("timestamp")) . " " . $string;
        $author = $acomment->comment_author;
        $user = get_user_by("login", $author);
        $thepostid = $acomment->comment_post_ID;
        $commentlink = get_comment_link($acomment);
        $img = false;

        $arg = [
          "default" => "noimage",
          "size" => "200",
        ];

        $img = get_avatar_url(get_current_user_id(), $arg);

        if (strpos($img, "noimage") !== false) {
          $img = false;
        }

        if (!$img) {
          if (strpos($author, " ") !== false) {
            $parts = str_split($author, 1);
            $parts = explode(" ", $author);
            $first = str_split($parts[0]);
            $first = $first[0];

            $name_string = $first;
          } else {
            $parts = str_split($author, 1);
            $name_string = $parts[0];
          }
        }

        $fullcontent = get_comment_text($acomment->comment_ID);

        if (strlen($fullcontent) > 40) {
          $shortcontent = substr(get_comment_text($acomment->comment_ID), 0, 40) . "...";
        } else {
          $shortcontent = $fullcontent;
        }

        $temp = [];
        $temp["title"] = get_the_title($thepostid);
        $temp["href"] = $commentlink;
        $temp["author"] = $author;
        $temp["date"] = $commentdate;
        $temp["text"] = esc_html($shortcontent);

        if ($img) {
          $temp["img"] = $img;
        } else {
          $temp["initials"] = $name_string;
        }

        $formatted[] = $temp;
        $count += 1;
      }

      $returndata = [];
      $totalcomments = count($comments);

      $returndata["message"] = __("Posts fetched", $this->textDomain);
      $returndata["posts"] = $formatted;
      $returndata["totalFound"] = $totalcomments;
      $returndata["maxPages"] = ceil($totalcomments / $maxperpage);

      $returndata["nocontent"] = "false";
      if ($totalcomments < 1) {
        $returndata["nocontent"] = __("No comments during the date range.", $this->textDomain);
      }

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_get_system_info()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $wp_v = get_bloginfo("version");
      $phph_v = phpversion();
      $plugins = get_plugins();
      $activePlugins = get_option("active_plugins");
      $inactive = count($plugins) - count($activePlugins);

      $holder = [];

      $temp = [];
      $temp["name"] = __("Core version", $this->textDomain);
      $temp["version"] = get_bloginfo("version");
      $holder[] = $temp;

      $temp = [];
      $temp["name"] = __("PHP version", $this->textDomain);
      $temp["version"] = $phph_v;
      $holder[] = $temp;

      $temp = [];
      $temp["name"] = __("Active Plugins", $this->textDomain);
      $temp["version"] = count($activePlugins);
      $holder[] = $temp;

      $temp = [];
      $temp["name"] = __("Inactive Plugins", $this->textDomain);
      $temp["version"] = $inactive;
      $holder[] = $temp;

      $temp = [];
      $temp["name"] = __("Installed Themes", $this->textDomain);
      $temp["version"] = count(wp_get_themes());
      $holder[] = $temp;

      $returndata["posts"] = $holder;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_get_system_health()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $sitehealth = get_transient("health-check-site-status-result");

      $issue_counts = [];

      if (false !== $sitehealth) {
        $issue_counts = json_decode($sitehealth, true);
      }

      if (!is_array($issue_counts) || !$issue_counts) {
        $issue_counts = [
          "good" => 0,
          "recommended" => 0,
          "critical" => 0,
        ];
      }

      $issues_total = $issue_counts["recommended"] + $issue_counts["critical"];
      $returndata = [];

      $chartData = [];
      $chartLabels = [];

      $colors = ["rgba(50, 210, 150, 1)", "rgba(250, 160, 90, 1)", "rgba(240, 80, 110,1)"];

      $temp = [];
      $temp["name"] = __("Passed Checks", $this->textDomain);
      $temp["value"] = $issue_counts["good"];
      $temp["color"] = $colors[0];
      array_push($chartData, $issue_counts["good"]);
      array_push($chartLabels, $temp["name"]);
      $returndata["issues"][] = $temp;

      $temp = [];
      $temp["name"] = __("Recommended", $this->textDomain);
      $temp["value"] = $issue_counts["recommended"];
      $temp["color"] = $colors[1];
      array_push($chartData, $issue_counts["recommended"]);
      array_push($chartLabels, $temp["name"]);
      $returndata["issues"][] = $temp;

      $temp = [];
      $temp["name"] = __("Critical", $this->textDomain);
      $temp["value"] = $issue_counts["critical"];
      $temp["color"] = $colors[2];
      array_push($chartData, $issue_counts["critical"]);
      array_push($chartLabels, $temp["name"]);
      $returndata["issues"][] = $temp;

      $returndata["colours"]["bgColors"] = ["#0c5cef", "rgba(250, 160, 90, 0.5)", "rgba(240, 80, 110, 0.5)"];
      $returndata["colours"]["borderColors"] = ["rgba(12, 92, 239, 1)"];

      if ($issue_counts["critical"] + $issue_counts["recommended"] > 0) {
        $returndata["message"] = sprintf(__("Take a look at the %d items on the", $this->textDomain), $issue_counts["critical"] + $issue_counts["recommended"]);
        $returndata["linkMessage"] = __("Site Health screen", $this->textDomain);
        $returndata["healthUrl"] = esc_url(admin_url("site-health.php"));
      }

      $returndata["dataSet"] = [
        "labels" => $chartLabels,
        "datasets" => [
          [
            "label" => __("Device Visits", $this->textDomain),
            "fill" => true,
            "data" => $chartData,
            "backgroundColor" => $colors,
            "borderWidth" => 0,
          ],
        ],
      ];

      $output = [];

      echo json_encode($returndata);
    }
    die();
  }

  public function add_components()
  {
    $modules = $this->get_modules(); ?>
    
    <script>
    const uipOverviewMods = [];
    </script>
    <script type="module" id="mark_test">
      
      <?php foreach ($modules as $key => $value) {
        $importname = str_replace("-", "_", $value["moduleName"]); ?>  
      import * as <?php echo $importname; ?> from '<?php echo $value["componentPath"]; ?>';
      uipOverviewMods['<?php echo $key; ?>'] = <?php echo $importname; ?>;
      <?php
      } ?>
      uip_build_overview();
    </script> 
    <?php
  }

  /**
   * Enqueue Admin Bar 2020 scripts
   * @since 1.4
   */

  public function add_scripts()
  {
    wp_register_style("uip-daterangepicker", $this->pathURL . "admin/apps/overview/css/daterangepicker.css", [], $this->version);
    wp_enqueue_style("uip-daterangepicker");

    wp_register_style("uip-codejar", $this->pathURL . "admin/apps/overview/css/highlight.css", [], $this->version);
    wp_enqueue_style("uip-codejar");

    $modules = $this->get_modules();
    $settings = $this->build_overview_data();
    $translations = $this->build_translations();

    //CODEFLASK
    wp_enqueue_script("uip-codejar-js", $this->pathURL . "admin/apps/overview/js/codejar-alt.js", ["jquery"], $this->version);
    wp_enqueue_script("uip-highlight-js", $this->pathURL . "admin/apps/overview/js/highlight.js", ["jquery"], $this->version);

    //VUE
    wp_enqueue_script("vue-menu-creator-js", $this->pathURL . "admin/apps/overview/js/vue-menu-creator.js", ["jquery"], $this->version, false);
    wp_enqueue_script("sortable-js", $this->pathURL . "admin/apps/overview/js/sortable.js", ["jquery"], $this->version, false);
    wp_enqueue_script("vue-sortable-js", $this->pathURL . "admin/apps/overview/js/vuedraggable.umd.js", ["jquery"], $this->version, false);

    ///CHART JS
    wp_enqueue_script("uip-charts", $this->pathURL . "admin/apps/overview/js/charts-3.js", ["jquery"], $this->version, false);
    wp_enqueue_script("uipress-chart-geo", $this->pathURL . "admin/apps/overview/js/chartjs-geo.min.js", ["uip-charts"], $this->version, false);
    //MOMENT
    wp_enqueue_script("uip-moment", $this->pathURL . "admin/apps/overview/js/moment.min.js", ["jquery"], $this->version);
    //LITE PICKER
    wp_enqueue_script("uipress-date-picker", $this->pathURL . "admin/apps/overview/js/litepicker.js", ["jquery"], $this->version);
    wp_enqueue_script("uipress-date-ranges", $this->pathURL . "admin/apps/overview/js/litepicker-ranges.js", ["jquery"], $this->version);

    ///OVERVIEW SCRIPTS
    wp_enqueue_script("admin-overview-app", $this->pathURL . "admin/apps/overview/js/admin-overview-app.min.js", ["jquery"], $this->version, true);
    wp_localize_script("admin-overview-app", "uipress_overview_ajax", [
      "ajax_url" => admin_url("admin-ajax.php"),
      "security" => wp_create_nonce("uipress-overview-security-nonce"),
      "options" => json_encode($settings),
      "modules" => json_encode($modules),
      "translations" => json_encode($translations),
    ]);
  }

  public function build_translations()
  {
    $translations = [];
    $translations["cardWidth"] = __("Card width", $this->textDomain);
    $translations["columnWidth"] = __("Column width", $this->textDomain);
    $translations["columnSettings"] = __("Column settings", $this->textDomain);
    $translations["remove"] = __("Remove Card", $this->textDomain);
    $translations["deleteCol"] = __("Delete Column", $this->textDomain);
    $translations["inTheLast"] = __("In the", $this->textDomain);
    $translations["days"] = __("day range", $this->textDomain);
    $translations["xxsmall"] = __("xxsmall (1/6)", $this->textDomain);
    $translations["xsmall"] = __("xsmall (1/5)", $this->textDomain);
    $translations["small"] = __("small (1/4)", $this->textDomain);
    $translations["smallmedium"] = __("small medium (1/3)", $this->textDomain);
    $translations["medium"] = __("medium (1/2)", $this->textDomain);
    $translations["mediumlarge"] = __("medium large (2/3)", $this->textDomain);
    $translations["large"] = __("large (3/4)", $this->textDomain);
    $translations["xlarge"] = __("xlarge (1/1)", $this->textDomain);
    $translations["emptycolumn"] = __("I am an empty columnm. Drag cards into me.", $this->textDomain);
    $translations["colAdded"] = __("Column Added", $this->textDomain);
    $translations["addCard"] = __("Add card", $this->textDomain);
    $translations["sectionAdded"] = __("Section added", $this->textDomain);
    $translations["searchCards"] = __("Search Cards", $this->textDomain);
    $translations["premium"] = __("Pro", $this->textDomain);
    $translations["title"] = __("Title", $this->textDomain);
    $translations["shortcode"] = __("Shortcode", $this->textDomain);
    $translations["videourl"] = __("Video URL", $this->textDomain);
    $translations["embedType"] = __("Embed Type", $this->textDomain);
    $translations["upgradMsg"] = __("Premium feature. Upgrade to pro to unlock", $this->textDomain);
    $translations["html"] = __("HTML", $this->textDomain);
    $translations["cardAdded"] = __("Card Added", $this->textDomain);
    $translations["bgcolor"] = __("Card Background colour", $this->textDomain);
    $translations["colorPlace"] = __("# Hex code only (#fff)", $this->textDomain);
    $translations["lightText"] = __("Use light color for text", $this->textDomain);
    $translations["chartType"] = __("Chart Type", $this->textDomain);
    $translations["lineChart"] = __("Line Chart", $this->textDomain);
    $translations["barChart"] = __("Bar Chart", $this->textDomain);
    $translations["vsPrevious"] = __("vs previous", $this->textDomain);
    $translations["vsdays"] = __("days", $this->textDomain);
    $translations["doughnut"] = __("Doughnut", $this->textDomain);
    $translations["polarArea"] = __("Polar Area", $this->textDomain);
    $translations["bar"] = __("Bar", $this->textDomain);
    $translations["horizbar"] = __("Horizontal Bar", $this->textDomain);
    $translations["country"] = __("Country", $this->textDomain);
    $translations["visits"] = __("Visits", $this->textDomain);
    $translations["change"] = __("Change", $this->textDomain);
    $translations["removeBackground"] = __("No Background", $this->textDomain);
    $translations["showmap"] = __("Hide Map", $this->textDomain);
    $translations["noaccount"] = __("No Google Analytics account connected", $this->textDomain);
    $translations["hbar"] = __("Horizontal Bar", $this->textDomain);
    $translations["hidechart"] = __("Hide Chart", $this->textDomain);
    $translations["source"] = __("Source", $this->textDomain);
    $translations["page"] = __("Page", $this->textDomain);
    $translations["product"] = __("Product", $this->textDomain);
    $translations["sold"] = __("Sold", $this->textDomain);
    $translations["value"] = __("Value", $this->textDomain);
    $translations["woocommerce"] = __("WooCommerce is required to use this card", $this->textDomain);
    $translations["validJSON"] = __("Please select a valid JSON file", $this->textDomain);
    $translations["fileBig"] = __("File is to big", $this->textDomain);
    $translations["layoutImported"] = __("Layout Imported", $this->textDomain);
    $translations["layoutExportedProblem"] = __("Unable to import layout", $this->textDomain);
    $translations["confirmReset"] = __("Are you sure you want to reset the overview page to the default layout? There is no undo.", $this->textDomain);
    $translations["availableCards"] = __("Available Cards", $this->textDomain);

    return $translations;
  }

  public function get_modules()
  {
    $cards = [];
    $extended_cards = apply_filters("uipress_register_card", $cards);

    return $extended_cards;
  }

  public function register_default_cards($cards)
  {
    if (!is_array($cards)) {
      $cards = [];
    }

    $scriptPath = plugins_url("modules/general/", __FILE__);

    $temp = [];
    $temp["name"] = __("Recently Published", $this->textDomain);
    $temp["moduleName"] = "recent-posts";
    $temp["description"] = __("Display posts, pages and CPTs published within the date range.", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "recent-posts.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Recent Comments", $this->textDomain);
    $temp["moduleName"] = "recent-comments";
    $temp["description"] = __("Displays total comments and recent comments published within the date range.", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "recent-comments.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("System Info", $this->textDomain);
    $temp["moduleName"] = "system-info";
    $temp["description"] = __("Displays info our about your cms and server setup.", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "system-info.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Site Health", $this->textDomain);
    $temp["moduleName"] = "site-health";
    $temp["description"] = __("Displays info our about your sites health.", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "site-health.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Date", $this->textDomain);
    $temp["moduleName"] = "calendar";
    $temp["description"] = __("Displays current time and date", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "calendar.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Video", $this->textDomain);
    $temp["moduleName"] = "custom-video";
    $temp["description"] = __("Displays a custom video", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "custom-video.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Shortcode", $this->textDomain);
    $temp["moduleName"] = "shortcode";
    $temp["description"] = __("Outputs a WordPress shortcode to the card", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "shortcode.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Custom HTML", $this->textDomain);
    $temp["moduleName"] = "custom-html";
    $temp["description"] = __("Outputs custom HTML to the card", $this->textDomain);
    $temp["category"] = __("General", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "custom-html.min.js";
    $cards[] = $temp;

    return $cards;
  }

  public function check_for_google_account()
  {
    $optionname = "admin2020_google_analytics";
    $a2020_options = get_option("uipress-overview");

    if (isset($a2020_options["analytics"]["view_id"]) && $a2020_options["analytics"]["refresh_token"]) {
      $view = $a2020_options["analytics"]["view_id"];
      $code = $a2020_options["analytics"]["refresh_token"];
    } else {
      return false;
    }

    if (!$view || $view == "" || !$code || $code == "") {
      return false;
    }

    return true;
  }

  public function build_overview_data()
  {
    $settings = [];
    $debug = new uipress_debug();
    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    $first = $current_user->user_firstname;
    $last = $current_user->user_lastname;

    if ($first == "" || $last == "") {
      $name_string = str_split($username, 1);
      $name_string = $name_string[0];
      $displayname = $username;
    } else {
      $name_string = str_split($first, 1)[0] . str_split($last, 1)[0];
      $displayname = $first;
    }
    if ($first == "") {
      $displayname = $username;
    }

    $daterange = [];
    $daterange["endDate"] = date("Y-m-d");
    $daterange["startDate"] = date("Y-m-d", strtotime(date("Y-m-d", strtotime("-6 day"))));

    $settings["user"]["username"] = $displayname;
    $settings["user"]["initial"] = $name_string;
    $settings["user"]["welcomemessage"] = __("Hello", $this->textDomain);
    $settings["user"]["date"] = date(get_option("date_format"));
    $settings["user"]["dateRange"] = $daterange;
    $settings["user"]["dateFormat"] = get_option("date_format");
    $settings["dataConnect"] = $debug->check_network_connection();
    $settings["canEdit"] = $this->can_edit_overview();
    $settings["analyticsAccount"] = $this->check_for_google_account();
    $settings["network"] = is_network_admin();

    $uipDashCards = $this->utils->get_overview_template();

    if (is_array($uipDashCards)) {
      $tempcards = $uipDashCards;

      if (is_array($tempcards) && is_array(json_decode(json_encode($tempcards)))) {
        $cards = $tempcards;
      } else {
        if (ini_get("allow_url_fopen")) {
          $str = file_get_contents($this->pathURL . "admin/apps/overview/template/default-layout.json");
          $cards = json_decode($str);
        } else {
          $cards = [];
        }
      }
    } else {
      if (ini_get("allow_url_fopen")) {
        $str = file_get_contents($this->pathURL . "admin/apps/overview/template/default-layout.json");
        $cards = json_decode($str);
      } else {
        $cards = [];
      }
    }

    if (!is_array($cards)) {
      $cards = [];
    }

    $settings["cards"]["formatted"] = $cards;

    return $settings;
  }

  public function can_edit_overview()
  {
    $enabledFor = $this->utils->get_option("overview", "disable-edit-for");

    if (empty($enabledFor)) {
      if (current_user_can("administrator")) {
        return true;
      } else {
        return false;
      }
    }

    if (!is_array($enabledFor)) {
      if (current_user_can("administrator")) {
        return true;
      } else {
        return false;
      }
    }

    if (!function_exists("wp_get_current_user")) {
      if (current_user_can("administrator")) {
        return true;
      } else {
        return false;
      }
    }

    $current_user = wp_get_current_user();

    $current_name = $current_user->display_name;
    $current_roles = $current_user->roles;
    $formattedroles = [];
    $all_roles = wp_roles()->get_names();

    if (in_array($current_name, $enabledFor)) {
      return true;
    }

    ///MULTISITE SUPER ADMIN
    if (is_super_admin() && is_multisite()) {
      if (in_array("Super Admin", $enabledFor)) {
        return true;
      } else {
        return false;
      }
    }

    ///NORMAL SUPER ADMIN
    if ($current_user->ID === 1) {
      if (in_array("Super Admin", $enabledFor)) {
        return true;
      } else {
        return false;
      }
    }

    foreach ($current_roles as $role) {
      $role_name = $all_roles[$role];
      if (in_array($role_name, $enabledFor)) {
        return true;
      }
    }
  }

  /**
   * Adds overview menu item
   * @since 1.4
   */

  public function add_menu_item()
  {
    if (get_bloginfo("name")) {
      $name = get_bloginfo("name") . " " . __("Overview", $this->textDomain);
    } else {
      $name = __("UiPress Overview", $this->textDomain);
    }

    add_menu_page($name, __("Overview", $this->textDomain), "read", "uip-overview", [$this, "build_overview"], "dashicons-chart-bar", 0);
    return;
  }

  public function build_overview()
  {
    ?>
		
		<style>
			  #wpcontent{
				  padding-left: 0;
			  }
		</style>
		
		<div class="uip-padding-m uip-text-normal uip-body-font uip-fade-in" id="overview-app">
			
			
			<div v-if="!loading" class="uip-w-100p uip-hidden" :class="{'uip-nothidden' : !loading}">
				<?php $this->build_head(); ?>
				<?php $this->build_welcome_message(); ?>
				<?php $this->build_cards(); ?>
			</div>
		</div>
		<?php
  }

  public function build_head()
  {
    ?>
		
		<div v-if="ui.editingMode" 
		class="uip-position-fixed uip-background-default uip-border-bottom uip-padding-s uip-right-0 uip-flex uip-flex-right uip-z-index-99" 
		style="top:var(--uip-toolbar-height);left:var(--uip-menu-width);">
    
        <div class="uip-flex-grow">
          <button @click="resetOverview()" 
          class="uip-button-danger"><?php _e("Reset Layout", $this->textDomain); ?></button>
        </div>
				
				<button  @click="newSection()"
				class="uip-button-default uip-margin-right-xs"><?php _e("New Section", $this->textDomain); ?></button>
				
				
        
        <button  @click="saveDash()"
        class="uip-button-primary uip-margin-right-xs"><?php _e("Save changes", $this->textDomain); ?></button>
        
        <button  @click="ui.editingMode = false;"
        class="uip-button-default material-icons-outlined">close</button>
        
				
		</div>
		
		
		<div v-if="!ui.editingMode" class="uip-flex uip-margin-bottom-m uip-flex-wrap uip-flex-start">
			
			
				
			<div class="uip-flex uip-flex-center uip-flex-grow uip-min-w-200 uip-margin-bottom-s">
				
				<div class="uip-background-dark uip-h-50 uip-w-50 uip-border-circle uip-flex uip-flex-center uip-flex-middle  uip-margin-right-s">
					<span class="uip-text-inverse uip-text-bold uip-text-xl uip-text-lowercase" style="height: 23px;">{{settings.user.initial}}</span>
				</div>
					
				<div >
					<div class="uip-text-bold uip-text-xl uip-text-emphasis uip-margin-bottom-xxs">{{settings.user.welcomemessage}} {{settings.user.username}}</div>
					<div class="uip-text-muted">{{settings.user.date}}</div>
				</div>
				
			</div>
			
			
			
			<div class="" :class="{'uk-margin-top' : isSmallScreen()}">
					
					<date-range-picker :dates="settings.user.dateRange"  @date-change="settings.user.dateRange = getdatafromComp($event)"></date-range-picker>
				
			</div>
			
			<div v-if="ui.editingMode" class="uip-overview-edit-header uk-flex uk-flex-right uk-background-default a2020-border bottom">
				
				<button  @click="newSection()"
				class="uk-button uk-button-small uk-margin-right"><?php _e("New Section", $this->textDomain); ?></button>
				
				<button  @click="saveDash()"
				class="uk-button uk-button-primary uk-button-small uk-margin-right"><?php _e("Save changes", $this->textDomain); ?></button>
				
				<button  @click="ui.editingMode = false;"
				class="uk-button uk-button-secondary uk-button-small"><?php _e("Exit edit mode", $this->textDomain); ?></button>
				
			</div>
			
			<div class="uip-margin-left-xs">
				<uip-dropdown type="icon" icon="tune" pos="botton-left">
					
					<div class="uip-text-bold uip-text-emphasis uip-text-l uip-margin-bottom-s"><?php _e("Settings", $this->textDomain); ?></div>
					
					<div v-if="settings.canEdit" class="uip-margin-bottom-s">
						<div class="uip-margin-bottom-xs"><?php _e("Editing Mode", $this->textDomain); ?></div>
						
						<label class="uip-switch">
						  <input type="checkbox" v-model="ui.editingMode">
						  <span class="uip-slider"></span>
						</label>
					</div>
					
					
					
					<div v-if="settings.analyticsAccount" class="uip-margin-bottom-s">
						<button @click="removeGoogleAccount()" class="uip-button-default">
							<?php _e("Disconnect Analytics", $this->textDomain); ?>
						</button>
					</div>
					
					<template v-if="settings.canEdit && uipdata == true" >
						
					
						<div  class="uip-margin-bottom-s">
							<button @click="exportCards()" class="uip-button-default uip-flex">
								<span class="material-icons-outlined uip-margin-right-xs">file_download</span>
								<span><?php _e("Export Layout", $this->textDomain); ?></span>
								<a href="" id="uip_export_dash"></a>
							</button>
						</div>
						
						<div class="uip-margin-bottom-s">
							<button  class="uip-button-default uip-flex">
								<label class="uip-flex">
									<span class="material-icons-outlined uip-margin-right-xs">file_upload</span>
									<?php _e("Import Layout", $this->textDomain); ?>
									<input hidden accept=".json" type="file" single="" id="uipress_import_cards" @change="importCards()">
								</label>
							</button>
						</div>
					
					</template>
					
          
        <div v-if="uipdata != true">
          
          <a href="https://uipress.co/pricing/" target="_BLANK" class="uip-no-underline uip-border-round uip-background-primary-wash uip-text-bold uip-text-emphasis uip-display-block" style="padding: var(--uip-padding-button)">
            <div class="uip-flex">
              <span class="material-icons-outlined uip-margin-right-xs">redeem</span> 
              <span><?php _e("Unlock Export and Import features with pro", $this->textDomain); ?></span>
            </div> 
          </a>
          
        </div>
					
					
				</uip-dropdown>
			</div>
			
		</div>
		
		
		<?php
  }

  public function build_cards()
  {
    ?>
		<div v-for='(category, index) in cardsWithIndex' class="uip-margin-top-m uip-margin-bottom-m" :class="{'uip-border-dashed uip-padding-s uip-border-round uip-margin-top-l' : ui.editingMode}">
			
			
			
			<div class="uip-border-round"
			:class="{'uip-background-muted uip-padding-s' : !category.open}">
				<div class="uip-flex" >
				
					
					<div class="uip-flex-grow">	
						<!-- CAT TITLE -->
						<div v-if="!ui.editingMode" class="uip-text-bold uip-text-xl uip-text-emphasis uip-margin-bottom-xxs">
							{{category.name}}
						</div>
						
						<!-- EDIT CAT TITLE -->
						<div v-if="ui.editingMode" class="uip-flex uip-flex-center uip-margin-bottom-xxs">
							<span class="material-icons-outlined uip-margin-right-s uip-text-xl">edit</span>
							<input class="uip-blank-input uip-text-xl" v-model="category.name" type="text">
						</div>
						
						<!-- CAT DESC -->
						<div v-if="!ui.editingMode" class="uip-text-muted uip-w-100-p">
							{{category.desc}}
						</div>
						
						<!-- EDIT CAT DESC -->
						<textarea v-if="ui.editingMode" class="uip-w-400" v-model="category.desc" type="text" style="padding:0;background:none;border:none;"></textarea>
					</div>
					
					<div>
						
						<div class="uip-flex">
							
							<span v-if="category.open" @click="category.open = !category.open" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xs material-icons-outlined">
								expand_more
							</span>
							<span v-if="!category.open" @click="category.open = !category.open" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xs material-icons-outlined">
								chevron_left
							</span>
						
							
								
							<button v-if="ui.editingMode" @click="addNewColumn(category.columns)"
							class="uip-button-default uip-margin-left-xs ">
								<?php _e("Add New Column", $this->textDomain); ?></button>
								
							<button v-if="ui.editingMode" @click="deleteSection(index)"
							class="uip-button-danger uip-margin-left-xs">
								<?php _e("Remove Section"); ?></button>
							
							<button v-if="ui.editingMode" @click="moveColumnDown(index)" :disabled="index == settings.cards.formatted.length - 1"
							class="uip-button-default uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xs material-icons-outlined uip-margin-left-xs material-icons-outlined">expand_more</button>
								
							<button v-if="ui.editingMode" @click="moveColumnUp(index)" :disabled="index == 0"
							class="uip-button-default uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xs material-icons-outlined uip-margin-left-xs material-icons-outlined">expand_less</button>
								
						</div>
					</div>
				</div>
			</div>
			
			
			<div v-if="category.open" class="uip-grid uip-flex uip-flex-wrap uip-margin-top-m">
				
				
				<template v-for="(column, index) in category.columns">
					<div 
					:class="['uip-width-' + column.size, { 'uip-edit-col' : ui.editingMode, 'uip-empty-col' : !column.cards || column.cards.length < 1}]" >
						
						<col-editer 
						v-if="ui.editingMode" 
						:modules="modules"
						:premium="settings.dataConnect"
						@remove-col="removeCol(category.columns, index)" 
						:column="column" :translations="translations" @col-change="column = getdatafromComp($event)"></col-editer>
						
						
						<draggable 
						  v-model="column.cards" 
						  :component-data="setDragData()"
						  handle=".drag-handle"
						  group="uip-cards"
						  @start="drag=true" 
						  @end="drag=false" 
						  @change="logDrop()"
						  item-key="id">
						  <template 
						  #item="{element, index, mainDR = settings.user.dateRange, prem = settings.dataConnect, allCards = column.cards}">
							  
							  <div class="top-level-card uip-margin-bottom-m"
							  :class="['uip-width-' + element.size]">
								  <div class="uip-card" 
								  :class="{'uip-no-background uip-no-border' : element.nobg && element.nobg != 'false'}"
								  :style="{'background-color' : element.bgColor}">
									  <div class="uip-padding-s">
										  <div class="uip-flex uip-flex-center">
											  
											  <div :class="{'uip-light-text' : element.lightDark && element.lightDark != 'false'}" class="uip-flex-grow">
												  <div class="uip-text-bold uip-text-normal drag-title uip-text-l uip-flex uip-flex-center ">
													  <span v-if="ui.editingMode" class="material-icons-outlined uip-margin-right-xs drag-handle" style="cursor:pointer">drag_indicator</span>
													  {{element.name}}
												  </div>
											  </div>
											  
											  <uip-dropdown v-if="ui.editingMode" type="icon" icon="more_horiz" pos="botton-left">
												  <card-options  :translations="translations" :card="column.cards[index]" :cardindex="index" 
													 @remove-card="removeCard(column, index)" 
													 @card-change="column.cards[index] = getdatafromComp($event)"></card-options>
											  </uip-dropdown>
										  </div>
									  </div>
									  <div :class="{'uip-light-text' : element.lightDark && element.lightDark != 'false'}">
									  <component :is="element.compName" v-bind="{ cardData: JSON.parse(JSON.stringify(element)), dateRange: mainDR, translations: translations, editingMode: ui.editingMode, premium: prem, analytics: settings.analyticsAccount}"
                    @card-change="column.cards[index] = getdatafromComp($event)"></component>
									  </div>
									  
								  </div>
							  </div>
							  
							  
							  
						  </template>
						  
						  
						</draggable>
						
						<p class="uip-text-muted uip-text-center" v-if="!column.cards || column.cards.length < 1 && ui.editingMode">
							{{translations.emptycolumn}}
						</p>
						
					</div>
						
				</template>
				
			</div>
			
			
			
			
			
		</div>
		
		
		
		<?php
  }

  public function build_welcome_message()
  {
    $code = stripslashes($this->utils->get_option("overview", "custom-welcome"));

    if ($code != "" && $code) { ?>
			<div class="uip-card uip-position-relative uip-text-normal" id="uipress-welcome-message">
				<div class="uip-position-absolute uip-right-0 uip-top-0 uip-padding-xs">
          <span onclick="jQuery('#uipress-welcome-message').remove();" class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">close</span>
				</div>	
				<div class="uip-padding-s">
					<?php echo $code; ?>
				</div>
			</div>
			
			<?php }
  }
}
