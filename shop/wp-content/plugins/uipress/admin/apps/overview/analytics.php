<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_analytics
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->utils = new uipress_util();
    $this->ga_data = "";
    $this->start_date = "";
    $this->end_date = "";
    $this->fetching = false;
  }

  /**
   * Loads menu actions
   * @since 1.0
   */

  public function run()
  {
    ///REGISTER THIS COMPONENT
    add_filter("uipress_register_card", [$this, "register_analytics_cards"]);

    //AJAX
    add_action("wp_ajax_uipress_analytics_get_page_views", [$this, "uipress_analytics_get_page_views"]);
    add_action("wp_ajax_uipress_analytics_get_bounce_rate", [$this, "uipress_analytics_get_bounce_rate"]);
    add_action("wp_ajax_uipress_analytics_get_session_duration", [$this, "uipress_analytics_get_session_duration"]);
    add_action("wp_ajax_uipress_analytics_get_page_speed", [$this, "uipress_analytics_get_page_speed"]);
    add_action("wp_ajax_uipress_analytics_get_site_users", [$this, "uipress_analytics_get_site_users"]);
    add_action("wp_ajax_uipress_analytics_get_site_devices", [$this, "uipress_analytics_get_site_devices"]);
    add_action("wp_ajax_uipress_analytics_get_country_visits", [$this, "uipress_analytics_get_country_visits"]);
    add_action("wp_ajax_uipress_analytics_get_sources", [$this, "uipress_analytics_get_sources"]);
    add_action("wp_ajax_uipress_analytics_get_page_traffic", [$this, "uipress_analytics_get_page_traffic"]);
    add_action("wp_ajax_uipress_get_google_images", [$this, "uipress_get_google_images"]);
    add_action("wp_ajax_uipress_save_analytics_account", [$this, "uipress_save_analytics_account"]);
    add_action("wp_ajax_uipress_remove_google_account", [$this, "uipress_remove_google_account"]);
  }

  public function uipress_remove_google_account()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $modulename = "admin2020_google_analytics";

      $a2020_options = get_option("uipress-overview");

      $a2020_options["analytics"]["view_id"] = "";
      $a2020_options["analytics"]["refresh_token"] = "";

      update_option("uipress-overview", $a2020_options);

      $returndata = [];
      $returndata["success"] = true;
      $returndata["message"] = __("Analytics account removed", "admin2020");
      echo json_encode($returndata);
    }

    die();
  }

  public function uipress_save_analytics_account()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $view = $this->utils->clean_ajax_input($_POST["view"]);
      $code = $this->utils->clean_ajax_input($_POST["code"]);
      $modulename = "admin2020_google_analytics";

      $a2020_options = get_option("uipress-overview");

      if ($view == "" || $code == "" || $modulename == "") {
        $message = __("Unable to connect account", "admin2020");
        echo $this->utils->ajax_error_message($message);
        die();
      }

      $a2020_options["analytics"]["view_id"] = $view;
      $a2020_options["analytics"]["refresh_token"] = $code;

      update_option("uipress-overview", $a2020_options);

      $returndata = [];
      $returndata["success"] = true;
      $returndata["message"] = __("Analytics account connected", "admin2020");
      echo json_encode($returndata);
    }

    die();
  }

  public function uipress_get_google_images()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $google_icon = esc_url($this->pathURL . "/assets/img/ga_btn_light.png");
      $google_icon_hover = esc_url($this->pathURL . "/assets/img/ga_btn_dark.png");

      $returndata["googliconNoHover"] = $google_icon;
      $returndata["googliconHover"] = $google_icon_hover;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_session_duration()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $total = $analyticsData->generic->totals->avgSessionDuration;
      $totalC = $analyticsData->generic->totals_comparison->avgSessionDuration;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $minutes = gmdate("i", $total);
      $seconds = gmdate("s", $total);
      $string = $minutes . "m " . $seconds . "s";

      $minutes = gmdate("i", $totalC);
      $seconds = gmdate("s", $totalC);
      $string_comparison = $minutes . "m " . $seconds . "s";

      $returndata["numbers"]["total"] = $string;
      $returndata["numbers"]["total_comparison"] = $string_comparison;
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_bounce_rate()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $total = $analyticsData->generic->totals->bounceRate;
      $totalC = $analyticsData->generic->totals_comparison->bounceRate;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["numbers"]["total"] = number_format($total, 2);
      $returndata["numbers"]["total_comparison"] = number_format($totalC, 2);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_site_devices()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $analyticsData->generic->timeline->dates;
      $formattedDates = [];
      $format = get_option("date_format");

      foreach ($dates as $date) {
        $tempdate = DateTime::createFromFormat("d/m/Y", $date);
        $output = $tempdate->format($format);
        array_push($formattedDates, $output);
      }

      $deviceData = $analyticsData->device->totals;
      $formattedData = [];
      $formattedLabels = [];
      $tempdataset = [];

      $bordercolors = ["rgba(12, 92, 239, 1)", "rgba(50, 210, 150, 1)", "rgba(250, 160, 90, 1)"];
      $backgroundcolors = ["rgba(12, 92, 239, 0.5)", "rgba(50, 210, 150, 0.5)", "rgba(250, 160, 90, 0.5)"];
      $output = [];

      if ($deviceData && is_object($deviceData)) {
        $count = 0;

        foreach ($deviceData as $key => $value) {
          $total = " (" . number_format($value) . ")";
          array_push($formattedLabels, ucfirst($key));
          array_push($formattedData, $value);

          $temp["name"] = ucfirst($key);
          $temp["value"] = $value;
          $temp["color"] = $backgroundcolors[$count];
          array_push($output, $temp);

          $count += 1;
        }
      }

      $dataSet = [
        "labels" => $formattedLabels,
        "datasets" => [
          [
            "label" => "visits",
            "fill" => true,
            "data" => $formattedData,
            "backgroundColor" => $backgroundcolors,
            "borderColor" => $bordercolors,
          ],
        ],
      ];

      $returndata["dataSet"] = $dataSet;
      $returndata["output"] = $output;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_site_users()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $analyticsData->generic->timeline->dates;
      $formattedDates = [];
      $format = get_option("date_format");

      foreach ($dates as $date) {
        $tempdate = DateTime::createFromFormat("d/m/Y", $date);
        $output = $tempdate->format($format);
        array_push($formattedDates, $output);
      }

      $views = $analyticsData->generic->timeline->data->users;

      $views_comparison = $analyticsData->generic->timeline_comparison->data->users;

      $dataSet = [
        "labels" => $formattedDates,
        "datasets" => [
          [
            "label" => __("Site Users", "admin2020"),
            "fill" => true,
            "data" => $views,
            "backgroundColor" => ["rgba(12, 92, 239, 0.05)"],
            "borderColor" => ["rgba(12, 92, 239, 1)"],
            "borderWidth" => 2,
          ],
          [
            "label" => __("Site Users (comparison period)", "admin2020"),
            "fill" => true,
            "data" => $views_comparison,
            "backgroundColor" => ["rgba(247, 127, 212, 0)"],
            "borderColor" => ["rgb(247, 127, 212)"],
            "borderWidth" => 2,
          ],
        ],
      ];

      $total = $analyticsData->generic->totals->users;
      $totalC = $analyticsData->generic->totals_comparison->users;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["dataSet"] = $dataSet;
      $returndata["numbers"]["total"] = number_format($total);
      $returndata["numbers"]["total_comparison"] = number_format($totalC);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_page_views()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $analyticsData->generic->timeline->dates;
      $formattedDates = [];
      $format = get_option("date_format");

      if (is_array($dates) && count($dates) > 0) {
        foreach ($dates as $date) {
          $tempdate = DateTime::createFromFormat("d/m/Y", $date);
          $output = $tempdate->format($format);
          array_push($formattedDates, $output);
        }
      }

      $views = $analyticsData->generic->timeline->data->pageviews;

      $views_comparison = $analyticsData->generic->timeline_comparison->data->pageviews;

      $dataSet = [
        "labels" => $formattedDates,
        "datasets" => [
          [
            "label" => __("Page Views", "admin2020"),
            "fill" => true,
            "data" => $views,
            "backgroundColor" => ["rgba(12, 92, 239, 0.05)"],
            "borderColor" => ["rgba(12, 92, 239, 1)"],
            "borderWidth" => 2,
          ],
          [
            "label" => __("Page Views (comparison period)", "admin2020"),
            "fill" => true,
            "data" => $views_comparison,
            "backgroundColor" => ["rgba(247, 127, 212, 0)"],
            "borderColor" => ["rgb(247, 127, 212)"],
            "borderWidth" => 2,
          ],
        ],
      ];

      $total = $analyticsData->generic->totals->pageviews;
      $totalC = $analyticsData->generic->totals_comparison->pageviews;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["dataSet"] = $dataSet;
      $returndata["numbers"]["total"] = number_format($total);
      $returndata["numbers"]["total_comparison"] = number_format($totalC);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_country_visits()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $country_data = $analyticsData->country;

      $countryformatted = [];

      if (isset($country_data->totals) && is_object($country_data->totals) && count((array) $country_data->totals) > 0) {
        foreach ($country_data->totals as $key => $value) {
          $countryname = $key;
          $visits = $value;
          $comparison_visits = $country_data->totals_comparison->$key;

          if ($comparison_visits == 0 || $visits == 0) {
            $change = 0;
          } else {
            $change = (($visits - $comparison_visits) / $comparison_visits) * 100;
          }

          $cc = $this->get_country_code($countryname);
          $flagurl = "https://flagcdn.com/16x12/" . $cc . ".png";

          $temp = [];
          $temp["name"] = $key;
          $temp["flag"] = $flagurl;
          $temp["visits"] = $visits;
          $temp["change"] = number_format($change, 2);

          array_push($countryformatted, $temp);
        }
      }

      $returndata["dataSet"] = $countryformatted;

      $returndata["countrieData"] = $country_data->totals;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_sources()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $country_data = $analyticsData->source;

      $countryformatted = [];

      foreach ($country_data->totals as $key => $value) {
        $countryname = $key;
        $visits = $value;
        $comparison_visits = $country_data->totals_comparison->$key;

        if ($comparison_visits == 0 || $visits == 0) {
          $change = 0;
        } else {
          $change = (($visits - $comparison_visits) / $comparison_visits) * 100;
        }

        // /$cc = $this->get_country_code($countryname);
        $flagurl = "https://s2.googleusercontent.com/s2/favicons?domain=" . $key;

        $temp = [];
        $temp["name"] = $key;
        $temp["flag"] = $flagurl;
        $temp["visits"] = $visits;
        $temp["change"] = number_format($change, 2);

        array_push($countryformatted, $temp);
      }

      $returndata["dataSet"] = $countryformatted;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_page_traffic()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $country_data = $analyticsData->path;

      $countryformatted = [];

      foreach ($country_data->totals as $key => $value) {
        $countryname = $key;
        $visits = $value;
        $comparison_visits = $country_data->totals_comparison->$key;

        if ($comparison_visits == 0 || $visits == 0) {
          $change = 0;
        } else {
          $change = (($visits - $comparison_visits) / $comparison_visits) * 100;
        }

        // /$cc = $this->get_country_code($countryname);
        $flagurl = "https://s2.googleusercontent.com/s2/favicons?domain=" . $key;

        $temp = [];
        $temp["name"] = $key;
        $temp["flag"] = $flagurl;
        $temp["visits"] = $visits;
        $temp["change"] = number_format($change, 2);

        array_push($countryformatted, $temp);
      }

      $returndata["dataSet"] = $countryformatted;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_page_speed()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $analyticsData = $this->get_analytics_data($startDate, $endDate);

      if ($analyticsData == "no_account") {
        $returndata = [];
        $returndata["noaccount"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $analyticsData->generic->timeline->dates;
      $views = $analyticsData->generic->timeline->data->pageLoadTime;

      $views_comparison = $analyticsData->generic->timeline_comparison->data->pageLoadTime;

      $formattedDates = [];
      $format = get_option("date_format");

      foreach ($dates as $date) {
        $tempdate = DateTime::createFromFormat("d/m/Y", $date);
        $output = $tempdate->format($format);
        array_push($formattedDates, $output);
      }

      $dataSet = [
        "labels" => $formattedDates,
        "datasets" => [
          [
            "label" => __("Page Speed", "admin2020"),
            "fill" => true,
            "data" => $views,
            "backgroundColor" => ["rgba(12, 92, 239, 0.05)"],
            "borderColor" => ["rgba(12, 92, 239, 1)"],
            "borderWidth" => 2,
          ],
          [
            "label" => __("Page Speed (comparison period)", "admin2020"),
            "fill" => true,
            "data" => $views_comparison,
            "backgroundColor" => ["rgba(247, 127, 212, 0)"],
            "borderColor" => ["rgb(247, 127, 212)"],
            "borderWidth" => 2,
          ],
        ],
      ];

      $total = $analyticsData->generic->totals->pageLoadTime;
      $totalC = $analyticsData->generic->totals_comparison->pageLoadTime;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["dataSet"] = $dataSet;
      $returndata["numbers"]["total"] = number_format($total, 2);
      $returndata["numbers"]["total_comparison"] = number_format($totalC, 2);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Register admin bar component
   * @since 1.4
   * @variable $components (array) array of registered admin 2020 components
   */
  public function register($components)
  {
    array_push($components, $this);
    return $components;
  }

  /**
   * Returns component info for settings page
   * @since 1.4
   */
  public function component_info()
  {
    $data = [];
    $data["title"] = __("Analytics", "admin2020");
    $data["option_name"] = "admin2020_google_analytics";
    $data["description"] = __("Creates the google analytics cards for the overview page.", "admin2020");
    return $data;
  }

  /**
   * Returns settings options for settings page
   * @since 2.1
   */
  public function get_settings_options()
  {
    $info = $this->component_info();
    $optionname = $info["option_name"];

    $settings = [];

    $temp = [];
    $temp["name"] = __("Google Analytics disabled for", "admin2020");
    $temp["description"] = __("Analytics will be disabled on the overview page for any users or roles you select", "admin2020");
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["value"] = $this->utils->get_option($optionname, $temp["optionName"], true);
    $settings[] = $temp;

    return $settings;
  }

  public function register_analytics_cards($cards)
  {
    if (!is_array($cards)) {
      $cards = [];
    }

    $scriptPath = plugins_url("modules/analytics/", __FILE__);

    $temp = [];
    $temp["name"] = __("Page Views", "admin2020");
    $temp["moduleName"] = "page-views";
    $temp["description"] = __("Display website page views within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "page-views.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Page Speed", "admin2020");
    $temp["moduleName"] = "page-speed";
    $temp["description"] = __("Display website page speed within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "page-speed.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Site Users", "admin2020");
    $temp["moduleName"] = "site-users";
    $temp["description"] = __("Display website users within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "site-users.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Site visits by device", "admin2020");
    $temp["moduleName"] = "site-devices";
    $temp["description"] = __("Display site usage by device within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "site-devices.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Bounce Rate", "admin2020");
    $temp["moduleName"] = "bounce-rate";
    $temp["description"] = __("Display site bounce rate within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "bounce-rate.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Session Duration", "admin2020");
    $temp["moduleName"] = "session-duration";
    $temp["description"] = __("Display site session duration within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "session-duration.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Visits by Country", "admin2020");
    $temp["moduleName"] = "country-visits";
    $temp["description"] = __("Displays country user data within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "country-visits.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Traffic Sources", "admin2020");
    $temp["moduleName"] = "traffic-sources";
    $temp["description"] = __("Displays traffic source info within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "traffic-sources.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Visits By Page", "admin2020");
    $temp["moduleName"] = "page-traffic";
    $temp["description"] = __("Displays traffic for most popular site pages within the date range.", "admin2020");
    $temp["category"] = __("Analytics", "admin2020");
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "page-traffic.min.js";
    $cards[] = $temp;

    return $cards;
  }

  /**
   * Fetches Analytics data
   * @since 1.4
   */

  public function admin2020_get_analytics_request($startdate = null, $enddate = null)
  {
    if ($startdate == null && $enddate == null) {
      $enddate = date("Y-m-d");
      $startdate = date("Y-m-d", strtotime($enddate . " - 7 days"));
    }

    $this->fetching = true;

    $info = $this->component_info();
    $optionname = $info["option_name"];

    $a2020_options = get_option("uipress-overview");

    if (!isset($a2020_options["analytics"]["view_id"]) || !isset($a2020_options["analytics"]["refresh_token"])) {
      $returndata = false;
      return $returndata;
    }

    $view = $a2020_options["analytics"]["view_id"];
    $code = $a2020_options["analytics"]["refresh_token"];

    if ($view == "" || $code == "") {
      $returndata = false;
      return $returndata;
    }

    $remote = wp_remote_get("https://admintwentytwenty.com/analytics/fetch.php?code=" . $code . "&view=" . $view . "&sd=" . $startdate . "&ed=" . $enddate, [
      "timeout" => 10,
      "headers" => [
        "Accept" => "application/json",
      ],
    ]);

    if (!is_wp_error($remote) && isset($remote["response"]["code"]) && $remote["response"]["code"] == 200 && !empty($remote["body"])) {
      $remote = json_decode($remote["body"]);
      $this->fetching = false;
      return $remote;
    } else {
      $returndata = false;
      $this->fetching = false;
      return $returndata;
    }
  }

  public function check_for_google_account()
  {
    $info = $this->component_info();
    $optionname = $info["option_name"];
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

  /**
   * Gets analytics data if it doesn't exist, returns it if it does exist
   * @since 1.4
   */
  public function get_analytics_data($startdate, $enddate)
  {
    $info = $this->component_info();
    $optionname = $info["option_name"];
    $a2020_options = get_option("uipress-overview");

    if (isset($a2020_options["analytics"]["view_id"]) && $a2020_options["analytics"]["refresh_token"]) {
      $view = $a2020_options["analytics"]["view_id"];
      $code = $a2020_options["analytics"]["refresh_token"];
    } else {
      return "no_account";
    }

    if (!$view || $view == "" || !$code || $code == "") {
      return "no_account";
    }

    $view = $a2020_options["analytics"]["view_id"];
    $code = $a2020_options["analytics"]["refresh_token"];

    $gadata = get_transient("uip-ga-data" . $startdate . $enddate);

    if (is_object($gadata) && $gadata) {
      return $gadata;
    } else {
      $this->ga_data = $this->admin2020_get_analytics_request($startdate, $enddate);
      $this->start_date = $startdate;
      $this->end_date = $enddate;

      set_transient("uip-ga-data" . $this->start_date . $this->end_date, $this->ga_data, 0.2 * MINUTE_IN_SECONDS);

      return $this->ga_data;
    }
  }

  public function get_country_code($countryname)
  {
    $countries = [
      "AF" => "Afghanistan",
      "AX" => "&Aring;land Islands",
      "AL" => "Albania",
      "DZ" => "Algeria",
      "AS" => "American Samoa",
      "AD" => "Andorra",
      "AO" => "Angola",
      "AI" => "Anguilla",
      "AG" => "Antigua and Barbuda",
      "AR" => "Argentina",
      "AM" => "Armenia",
      "AW" => "Aruba",
      "AU" => "Australia",
      "AT" => "Austria",
      "AZ" => "Azerbaijan",
      "BS" => "Bahamas (the)",
      "BH" => "Bahrain",
      "BD" => "Bangladesh",
      "BB" => "Barbados",
      "BY" => "Belarus",
      "BE" => "Belgium",
      "BZ" => "Belize",
      "BJ" => "Benin",
      "BM" => "Bermuda",
      "BT" => "Bhutan",
      "BO" => "Bolivia (Plurinational State of)",
      "BA" => "Bosnia and Herzegovina",
      "BW" => "Botswana",
      "BV" => "Bouvet Island",
      "BR" => "Brazil",
      "IO" => "British Indian Ocean Territory (the)",
      "BN" => "Brunei Darussalam",
      "BG" => "Bulgaria",
      "BF" => "Burkina Faso",
      "BI" => "Burundi",
      "KH" => "Cambodia",
      "CV" => "Cabo Verde",
      "CM" => "Cameroon",
      "CA" => "Canada",
      "CT" => "Catalonia",
      "KY" => "Cayman Islands (the)",
      "CF" => "Central African Republic (the)",
      "TD" => "Chad",
      "CL" => "Chile",
      "CN" => "China",
      "CX" => "Christmas Island",
      "CC" => "Cocos (Keeling) Islands (the)",
      "CO" => "Colombia",
      "KM" => "Comoros",
      "CD" => "Congo (the Democratic Republic of the)",
      "CG" => "Congo (the)",
      "CK" => "Cook Islands (the)",
      "CR" => "Costa Rica",
      "HR" => "Croatia",
      "CU" => "Cuba",
      "CY" => "Cyprus",
      "CZ" => "Czech Republic (the)",
      "DK" => "Denmark",
      "DJ" => "Djibouti",
      "DM" => "Dominica",
      "DO" => "Dominican Republic (the)",
      "EC" => "Ecuador",
      "EG" => "Egypt",
      "SV" => "El Salvador",
      "EN" => "England",
      "GQ" => "Equatorial Guinea",
      "ER" => "Eritrea",
      "EE" => "Estonia",
      "ET" => "Ethiopia",
      "EU" => "European Union",
      "FK" => "Falkland Islands (the) [Malvinas]",
      "FO" => "Faroe Islands (the)",
      "FJ" => "Fiji",
      "FI" => "Finland",
      "FR" => "France",
      "GF" => "French Guiana",
      "PF" => "French Polynesia",
      "TF" => "French Southern Territories (the)",
      "GA" => "Gabon",
      "GM" => "Gambia (the)",
      "GE" => "Georgia",
      "DE" => "Germany",
      "GH" => "Ghana",
      "GI" => "Gibraltar",
      "GR" => "Greece",
      "GL" => "Greenland",
      "GD" => "Grenada",
      "GP" => "Guadeloupe",
      "GU" => "Guam",
      "GT" => "Guatemala",
      "GN" => "Guinea",
      "GW" => "Guinea-Bissau",
      "GY" => "Guyana",
      "HT" => "Haiti",
      "HM" => "Heard Island and McDonald Islands",
      "VA" => "Holy See (the)",
      "HN" => "Honduras",
      "HK" => "Hong Kong",
      "HU" => "Hungary",
      "IS" => "Iceland",
      "IN" => "India",
      "ID" => "Indonesia",
      "IR" => "Iran (Islamic Republic of)",
      "IQ" => "Iraq",
      "IE" => "Ireland",
      "IL" => "Israel",
      "IT" => "Italy",
      "JM" => "Jamaica",
      "JP" => "Japan",
      "JO" => "Jordan",
      "KZ" => "Kazakhstan",
      "KE" => "Kenya",
      "KI" => "Kiribati",
      "KP" => 'Korea (the Democratic People\'s Republic of)',
      "KR" => "Korea (the Republic of)",
      "KW" => "Kuwait",
      "KG" => "Kyrgyzstan",
      "LA" => 'Lao People\'s Democratic Republic (the)',
      "LV" => "Latvia",
      "LB" => "Lebanon",
      "LS" => "Lesotho",
      "LR" => "Liberia",
      "LY" => "Libya",
      "LI" => "Liechtenstein",
      "LT" => "Lithuania",
      "LU" => "Luxembourg",
      "MO" => "Macao",
      "MK" => "Macedonia (the former Yugoslav Republic of)",
      "MG" => "Madagascar",
      "MW" => "Malawi",
      "MY" => "Malaysia",
      "MV" => "Maldives",
      "ML" => "Mali",
      "MT" => "Malta",
      "MH" => "Marshall Islands (the)",
      "MQ" => "Martinique",
      "MR" => "Mauritania",
      "MU" => "Mauritius",
      "YT" => "Mayotte",
      "MX" => "Mexico",
      "FM" => "Micronesia (Federated States of)",
      "MD" => "Moldova (the Republic of)",
      "MC" => "Monaco",
      "MN" => "Mongolia",
      "ME" => "Montenegro",
      "MS" => "Montserrat",
      "MA" => "Morocco",
      "MZ" => "Mozambique",
      "MM" => "Myanmar",
      "NA" => "Namibia",
      "NR" => "Nauru",
      "NP" => "Nepal",
      "NL" => "Netherlands",
      "AN" => "Netherlands Antilles",
      "NC" => "New Caledonia",
      "NZ" => "New Zealand",
      "NI" => "Nicaragua",
      "NE" => "Niger (the)",
      "NG" => "Nigeria",
      "NU" => "Niue",
      "NF" => "Norfolk Island",
      "MP" => "Northern Mariana Islands (the)",
      "NO" => "Norway",
      "OM" => "Oman",
      "PK" => "Pakistan",
      "PW" => "Palau",
      "PS" => "Palestine, State of",
      "PA" => "Panama",
      "PG" => "Papua New Guinea",
      "PY" => "Paraguay",
      "PE" => "Peru",
      "PH" => "Philippines (the)",
      "PN" => "Pitcairn",
      "PL" => "Poland",
      "PT" => "Portugal",
      "PR" => "Puerto Rico",
      "QA" => "Qatar",
      "RE" => "R&eacute;union",
      "RO" => "Romania",
      "RU" => "Russian Federation (the)",
      "RW" => "Rwanda",
      "SH" => "Saint Helena, Ascension and Tristan da Cunha",
      "KN" => "Saint Kitts and Nevis",
      "LC" => "Saint Lucia",
      "PM" => "Saint Pierre and Miquelon",
      "VC" => "Saint Vincent and the Grenadines",
      "WS" => "Samoa",
      "SM" => "San Marino",
      "ST" => "Sao Tome and Principe",
      "SA" => "Saudi Arabia",
      "AB" => "Scotland",
      "SN" => "Senegal",
      "RS" => "Serbia",
      "CS" => "Serbia and Montenegro",
      "SC" => "Seychelles",
      "SL" => "Sierra Leone",
      "SG" => "Singapore",
      "SK" => "Slovakia",
      "SI" => "Slovenia",
      "SB" => "Solomon Islands",
      "SO" => "Somalia",
      "ZA" => "South Africa",
      "GS" => "South Georgia and the South Sandwich Islands",
      "ES" => "Spain",
      "LK" => "Sri Lanka",
      "SD" => "Sudan (the)",
      "SR" => "Suriname",
      "SJ" => "Svalbard and Jan Mayen",
      "SZ" => "Swaziland",
      "SE" => "Sweden",
      "CH" => "Switzerland",
      "SY" => "Syrian Arab Republic",
      "TW" => "Taiwan (Province of China)",
      "TJ" => "Tajikistan",
      "TZ" => "Tanzania, United Republic of",
      "TH" => "Thailand",
      "TL" => "Timor-Leste",
      "TG" => "Togo",
      "TK" => "Tokelau",
      "TO" => "Tonga",
      "TT" => "Trinidad and Tobago",
      "TN" => "Tunisia",
      "TR" => "Turkey",
      "TM" => "Turkmenistan",
      "TC" => "Turks and Caicos Islands (the)",
      "TV" => "Tuvalu",
      "UG" => "Uganda",
      "UA" => "Ukraine",
      "AE" => "United Arab Emirates (the)",
      "GB" => "United Kingdom",
      "UM" => "United States Minor Outlying Islands (the)",
      "US" => "United States of America (the)",
      "US" => "United States",
      "UY" => "Uruguay",
      "UZ" => "Uzbekistan",
      "VU" => "Vanuatu",
      "VE" => "Venezuela (Bolivarian Republic of)",
      "VN" => "Viet Nam",
      "VG" => "Virgin Islands (British)",
      "VI" => "Virgin Islands (U.S.)",
      "WA" => "Wales",
      "WF" => "Wallis and Futuna",
      "EH" => "Western Sahara",
      "YE" => "Yemen",
      "ZM" => "Zambia",
      "ZW" => "Zimbabwe",
    ];

    $result = array_search($countryname, $countries);

    if ($result) {
      return strtolower($result);
    } else {
      return false;
    }
  }
}
