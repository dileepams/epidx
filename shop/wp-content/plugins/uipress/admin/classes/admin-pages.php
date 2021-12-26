<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_admin_page
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
    $this->blackStyles = [];
  }

  public function run()
  {
    add_action("init", [$this, "uip_create_admin_page_cpt"]);
    add_action("admin_menu", [$this, "add_custom_menu_items"]);
    add_action("enqueue_block_assets", [$this, "capture_block_styles"], 999);
    add_action("wp_enqueue_style", [$this, "capture_front_styles"], 999);
  }

  public function capture_block_styles()
  {
    global $wp_styles;
    foreach ($wp_styles->queue as $style) {
      $url = $wp_styles->registered[$style]->src;
      echo '<link id="uip-front-styles" rel="stylesheet" href="' . $url . '" media="all">';
    }
  }

  public function capture_front_styles()
  {
    global $wp_styles;
    foreach ($wp_styles->queue as $style) {
      $url = $wp_styles->registered[$style]->src;
      echo '<link id="uip-front-styles" rel="stylesheet" href="' . $url . '" media="all">';
    }
  }

  /**
   * Created admin pages custom post type
   * @since 2.2
   */

  public function uip_create_admin_page_cpt()
  {
    $uri = $_SERVER["REQUEST_URI"];
    $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") || $_SERVER["SERVER_PORT"] == 443 ? "https://" : "http://";
    $url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    $currentURL = $url . $uri;
    //Don't load if we are not admin or on editor page or doing a rest request
    //CHECKS WE ARE NOT ON STANDARD ADMIN PAGE, LOGIN PAGE AND THE URL DOESN'T CONTAIN ADMIN URL (/WP-ADMIN/)
    if (!defined("REST_REQUEST") && !isset($_GET["elementor-preview"])) {
      if (!is_admin() && stripos($currentURL, admin_url()) === false) {
        return;
      }
    }

    ///Don't load if we are not logged in
    if (!is_user_logged_in()) {
      return;
    }

    $labels = [
      "name" => _x("Admin Page", "post type general name", $this->textDomain),
      "singular_name" => _x("Admin Page", "post type singular name", $this->textDomain),
      "menu_name" => _x("Admin Pages", "admin menu", $this->textDomain),
      "name_admin_bar" => _x("Admin Page", "add new on admin bar", $this->textDomain),
      "add_new" => _x("Add New", "folder", $this->textDomain),
      "add_new_item" => __("Add New Admin Page", $this->textDomain),
      "new_item" => __("New Admin Page", $this->textDomain),
      "edit_item" => __("Edit Admin Page", $this->textDomain),
      "view_item" => __("View Admin Page", $this->textDomain),
      "all_items" => __("All Admin Pages", $this->textDomain),
      "search_items" => __("Search Admin Pages", $this->textDomain),
      "not_found" => __("No Admin Pages found.", $this->textDomain),
      "not_found_in_trash" => __("No Admin Pages found in Trash.", $this->textDomain),
    ];
    $args = [
      "labels" => $labels,
      "description" => __("Description.", "Add New Admin Page"),
      "public" => true,
      "publicly_queryable" => true,
      "show_ui" => true,
      "show_in_menu" => true,
      "query_var" => false,
      "has_archive" => false,
      "hierarchical" => false,
      "supports" => ["editor", "title"],
      "show_in_rest" => true,
      "rewrite" => ["slug" => "admin-page"],
    ];
    register_post_type("uip-admin-page", $args);
  }

  /**
   * Adds custom admin pages to the menu
   * @since 2.2
   */

  public function add_custom_menu_items()
  {
    $args = [
      "numberposts" => -1,
      "post_status" => "publish",
      "post_type" => "uip-admin-page",
    ];

    $adminppages = get_posts($args);

    if (!$adminppages || count($adminppages) < 1) {
      return;
    }

    foreach ($adminppages as $page) {
      $title = get_the_title($page);
      $lc_title = strtolower($title);
      $slug = str_replace(" ", "-", $lc_title);
      $theid = $page->ID;

      add_menu_page("uip-" . $slug, $title, "read", "uip-" . urlencode($slug), function () use ($theid) {
        $this->handle_custom_page_content($theid);
      });
    }
    return;
  }

  /**
   *Outputs the content
   * @since 2.2
   */
  public function handle_custom_page_content($theid)
  {
    do_action("enqueue_block_assets");
    do_action("wp_enqueue_style");
    do_action("wp_print_styles");
    ?>
    
    <link rel="stylesheet" href="<?php echo includes_url(); ?>css/dist/block-library/style.min.css" media="all">
    
    <div class="wrap uip-custom-page-content">
          <?php echo get_the_content(null, false, $theid); ?>
    </div>
    <?php wp_footer();
  }
}
