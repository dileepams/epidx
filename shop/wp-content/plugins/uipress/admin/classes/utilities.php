<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_util
{
  /**
   * Gets user prefs object
   * @since 1.4
   */
  public function get_user_preferences()
  {
    $userid = get_current_user_id();

    $current = get_user_meta($userid, "uip-prefs", true);

    $foredark = $this->get_option("general", "dark-default");
    $foreCollapse = $this->get_option("menu", "shrunk-default");

    if (!$current) {
      $current = [];
    }
    $tempArray = [];

    if (is_array($current)) {
      foreach ($current as $key => $value) {
        $temp = $value;
        if ($value == "true") {
          $temp = true;
        }
        if ($value == "false") {
          $temp = false;
        }
        $tempArray[$key] = $temp;
      }
    }
    if ($foredark == true) {
      if (!array_key_exists("darkmode", $tempArray) || $current["darkmode"] == "") {
        $tempArray["darkmode"] = true;
      }
    }

    if ($foreCollapse == true) {
      if (!array_key_exists("menuShrunk", $tempArray) || $current["menuShrunk"] == "") {
        $tempArray["menuShrunk"] = true;
      }
    }

    return $tempArray;
  }

  /**
   * Sanitises and strips tags of input from ajax
   * @since 1.4
   * @variables $values = item to clean (array or string)
   */
  public function clean_ajax_input($values)
  {
    if (is_array($values)) {
      foreach ($values as $index => $in) {
        if (is_array($in)) {
          $values[$index] = $this->clean_ajax_input($in);
        } else {
          $values[$index] = strip_tags($in);
        }
      }
    } else {
      $values = strip_tags($values);
    }

    return $values;
  }

  /**
   * Sanitises and strips tags of input from ajax without losing code
   * @since 1.4
   * @variables $values = item to clean (array or string)
   */
  public function clean_ajax_input_html($values)
  {
    global $allowedposttags;
    $allowed_atts = [
      "align" => [],
      "class" => [],
      "type" => [],
      "id" => [],
      "dir" => [],
      "lang" => [],
      "style" => [],
      "xml:lang" => [],
      "src" => [],
      "alt" => [],
      "href" => [],
      "rel" => [],
      "rev" => [],
      "target" => [],
      "novalidate" => [],
      "type" => [],
      "value" => [],
      "name" => [],
      "tabindex" => [],
      "action" => [],
      "method" => [],
      "for" => [],
      "width" => [],
      "height" => [],
      "data" => [],
      "title" => [],
      "script" => [],
    ];
    $allowedposttags["form"] = $allowed_atts;
    $allowedposttags["label"] = $allowed_atts;
    $allowedposttags["input"] = $allowed_atts;
    $allowedposttags["textarea"] = $allowed_atts;
    $allowedposttags["iframe"] = $allowed_atts;
    $allowedposttags["script"] = $allowed_atts;
    $allowedposttags["style"] = $allowed_atts;
    $allowedposttags["strong"] = $allowed_atts;
    $allowedposttags["small"] = $allowed_atts;
    $allowedposttags["table"] = $allowed_atts;
    $allowedposttags["span"] = $allowed_atts;
    $allowedposttags["abbr"] = $allowed_atts;
    $allowedposttags["code"] = $allowed_atts;
    $allowedposttags["pre"] = $allowed_atts;
    $allowedposttags["div"] = $allowed_atts;
    $allowedposttags["img"] = $allowed_atts;
    $allowedposttags["h1"] = $allowed_atts;
    $allowedposttags["h2"] = $allowed_atts;
    $allowedposttags["h3"] = $allowed_atts;
    $allowedposttags["h4"] = $allowed_atts;
    $allowedposttags["h5"] = $allowed_atts;
    $allowedposttags["h6"] = $allowed_atts;
    $allowedposttags["ol"] = $allowed_atts;
    $allowedposttags["ul"] = $allowed_atts;
    $allowedposttags["li"] = $allowed_atts;
    $allowedposttags["em"] = $allowed_atts;
    $allowedposttags["hr"] = $allowed_atts;
    $allowedposttags["br"] = $allowed_atts;
    $allowedposttags["tr"] = $allowed_atts;
    $allowedposttags["td"] = $allowed_atts;
    $allowedposttags["p"] = $allowed_atts;
    $allowedposttags["a"] = $allowed_atts;
    $allowedposttags["b"] = $allowed_atts;
    $allowedposttags["i"] = $allowed_atts;

    if (is_array($values)) {
      foreach ($values as $index => $in) {
        if (is_array($in)) {
          $values[$index] = $this->clean_ajax_input_html($in);
        } else {
          $values[$index] = wp_kses($in, $allowedposttags);
        }
      }
    } else {
      $values = wp_kses($values, $allowedposttags);
    }

    return $values;
  }

  /**
   * Sanitises and strips tags of input from ajax without losing code
   * @since 1.4
   * @variables $values = item to clean (array or string)
   */
  public function clean_ajax_input_menu_editor($values)
  {
    $allowed_html = [
      "span" => [
        "class" => [],
      ],
    ];
    if (is_object($values)) {
      foreach ($values as $index => $in) {
        if (is_object($in)) {
          $values->$index = $this->clean_ajax_input_menu_editor($in);
        } elseif (is_array($in)) {
          $values->$index = $this->clean_ajax_input_menu_editor($in);
        } else {
          $values->$index = wp_kses($in, $allowed_html);
        }
      }
    } elseif (is_array($values)) {
      foreach ($values as $index => $in) {
        if (is_object($in)) {
          $values[$index] = $this->clean_ajax_input_menu_editor($in);
        } elseif (is_array($in)) {
          $values[$index] = $this->clean_ajax_input_menu_editor($in);
        } else {
          $values[$index] = wp_kses($in, $allowed_html);
        }
      }
    } else {
      $values = wp_kses($values, $allowed_html);
    }

    return $values;
  }

  /**
   * Gets user options
   * @since 2.2
   */

  public function get_style_value($option_name, $returnArray = false)
  {
    $data["light"] = "";
    $data["dark"] = "";

    if ($returnArray) {
      $data["light"] = [];
      $data["dark"] = [];
    }

    if ($option_name == false) {
      return $data;
    }

    $uipOptions = get_option("uip-styles");
    $option = "";

    if (is_multisite() && $this->is_site_wide("uipress/uipress.php")) {
      $uipOptionsNetwork = get_blog_option(get_main_network_id(), "uip-settings");
      $uipStylesNetwork = get_blog_option(get_main_network_id(), "uip-styles");

      if (!isset($uipOptionsNetwork["general"]["options"]) || !is_array($uipOptionsNetwork["general"]["options"])) {
        $uipOptionsNetwork["general"]["options"] = [];
      }

      if (array_key_exists("network_override", $uipOptionsNetwork["general"]["options"])) {
        $enabled = $uipOptionsNetwork["general"]["options"]["network_override"]["value"];

        if ($enabled == "true") {
          $uipOptions = $uipStylesNetwork;
        }
      }
    }

    if (is_network_admin()) {
      $uipOptions = get_option("uip-styles");
    }

    if (isset($uipOptions[$option_name]["light"])) {
      $value = $uipOptions[$option_name]["light"];
      if ($value != "") {
        $data["light"] = $value;
      }
    }

    if (isset($uipOptions[$option_name]["dark"])) {
      $value = $uipOptions[$option_name]["dark"];
      if ($value != "") {
        $data["dark"] = $value;
      }
    }

    return $data;
  }
  /**
   * Gets user overview template
   * @since 1.2
   */

  public function get_overview_template()
  {
    $uipOptions = get_option("uip-overview");
    $option = "";

    if (is_multisite() && $this->is_site_wide("uipress/uipress.php")) {
      $uipoverview = get_blog_option(get_main_network_id(), "uip-overview");
      $uipOptionsNetwork = get_blog_option(get_main_network_id(), "uip-settings");

      if (!isset($uipOptionsNetwork["general"]["options"]) || !is_array($uipOptionsNetwork["general"]["options"])) {
        $uipOptionsNetwork["general"]["options"] = [];
      }

      if (array_key_exists("network_override", $uipOptionsNetwork["general"]["options"])) {
        $enabled = $uipOptionsNetwork["general"]["options"]["network_override"]["value"];

        if ($enabled == "true") {
          $uipOptions = $uipoverview;
        }
      }
    }

    if (is_network_admin()) {
      $uipOptions = get_option("uip-overview");
      if (!is_array($uipOptions)) {
        $uipOptions = false;
      }
    }

    $cards = false;
    if (isset($uipOptions["cards"]) && is_array($uipOptions["cards"])) {
      $cards = $uipOptions["cards"];
    }

    if ($cards == "") {
      $cards = false;
    }

    if (is_array($cards) && count($cards) < 1) {
      $cards = false;
    }

    return $cards;
  }

  /**
   * Gets user options
   * @since 1.2
   */

  public function get_option($module_name = false, $option_name = false, $returnarray = false)
  {
    if ($module_name == false || $option_name == false) {
      return "";
    }
    $uipOptions = get_option("uip-settings");
    $option = "";

    if (is_multisite() && $this->is_site_wide("uipress/uipress.php")) {
      $uipOptionsNetwork = get_blog_option(get_main_network_id(), "uip-settings");

      if (!is_array($uipOptionsNetwork["general"]["options"])) {
        $uipOptionsNetwork["general"]["options"] = [];
      }

      if (array_key_exists("network_override", $uipOptionsNetwork["general"]["options"])) {
        $enabled = $uipOptionsNetwork["general"]["options"]["network_override"]["value"];

        if ($enabled == "true") {
          $uipOptions = $uipOptionsNetwork;
        }
      }
    }

    if (is_network_admin()) {
      $uipOptions = get_option("uip-settings");
      if (!is_array($uipOptions)) {
        $uipOptions = [];
      }
    }

    if (isset($uipOptions[$module_name]["options"][$option_name]["value"])) {
      $value = $uipOptions[$module_name]["options"][$option_name]["value"];
      if ($value != "") {
        $option = $value;
      }
    }

    if ($returnarray == true) {
      if ($option == "") {
        $option = [];
      }
    }

    if ($option == "false") {
      $option = false;
    }

    if ($option == "true") {
      $option = true;
    }

    return $option;
  }

  /**
   * Checks if the current plugin is activated sitewide
   * @since 1.2
   */
  public function is_site_wide($plugin)
  {
    if (!is_multisite()) {
      return false;
    }

    $plugins = get_site_option("active_sitewide_plugins");
    if (isset($plugins[$plugin])) {
      return true;
    }

    return false;
  }

  /**
   * Checks if an option is disabled for current user
   * @since 2.1.6
   */

  public function valid_for_user($rolesandusernames)
  {
    if (empty($rolesandusernames)) {
      return false;
    }

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

    return false;
  }

  /**
   * Gets user preferences
   * @since 1.2
   * @variable $pref name of pref to fetch (string)
   */
  public function get_user_preference($pref)
  {
    $userid = get_current_user_id();
    $current = get_user_meta($userid, "uip-prefs", true);
    $value = false;

    if (is_array($current)) {
      if (isset($current[$pref])) {
        $value = $current[$pref];
      }
    }

    return $value;
  }

  /**
   * Returns an araay of dates betwen two dates
   * @since 1.2
   */

  public function date_array($startdate, $enddate)
  {
    $enddate = date("Y-m-d", strtotime($enddate . " + 1 day"));

    $period = new DatePeriod(new DateTime($startdate), new DateInterval("P1D"), new DateTime($enddate));

    $date_array = [];

    foreach ($period as $key => $value) {
      $the_date = $value->format("d/m/Y");
      array_push($date_array, $the_date);
    }

    return $date_array;
  }

  /**
   * Checks for absolute URLS
   * @since 1.2
   * @variable $pref name of pref to fetch (string)
   */
  public function isAbsoluteUrl($url)
  {
    $pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
    (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
    (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
    (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

    return (bool) preg_match($pattern, $url);
  }

  /**
   * Formats file sizes
   * @since 1.2
   */
  public function formatBytes($size, $precision = 0)
  {
    $base = log($size, 1024);
    $suffixes = ["", "KB", "MB", "GB", "TB"];

    return round(pow(1024, $base - floor($base)), $precision) . " " . $suffixes[floor($base)];
  }
}
