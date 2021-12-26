<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_woocommerce
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->utils = new uipress_util();
    $this->orders = "";
    $this->start_date = "";
    $this->end_date = "";
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
    add_action("wp_ajax_uipress_analytics_get_total_sales", [$this, "uipress_analytics_get_total_sales"]);
    add_action("wp_ajax_uipress_analytics_get_total_orders", [$this, "uipress_analytics_get_total_orders"]);
    add_action("wp_ajax_uipress_analytics_get_average_order_value", [$this, "uipress_analytics_get_average_order_value"]);
    add_action("wp_ajax_uipress_get_recent_orders", [$this, "uipress_get_recent_orders"]);
    add_action("wp_ajax_uipress_get_popular_products", [$this, "uipress_get_popular_products"]);
  }

  public function register_analytics_cards($cards)
  {
    if (!is_array($cards)) {
      $cards = [];
    }

    $scriptPath = plugins_url("modules/woocommerce/", __FILE__);

    $temp = [];
    $temp["name"] = __("Total Sales", $this->textDomain);
    $temp["moduleName"] = "total-sales";
    $temp["description"] = __("Display total sales in your store within the date range.", $this->textDomain);
    $temp["category"] = __("Commerce", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "total-sales.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Total Orders", $this->textDomain);
    $temp["moduleName"] = "total-orders";
    $temp["description"] = __("Display total orders in your store within the date range.", $this->textDomain);
    $temp["category"] = __("Commerce", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "total-orders.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Average Order Value", $this->textDomain);
    $temp["moduleName"] = "average-order-value";
    $temp["description"] = __("Display average order value in your store within the date range.", $this->textDomain);
    $temp["category"] = __("Commerce", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "average-order-value.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Recent Orders", $this->textDomain);
    $temp["moduleName"] = "recent-orders";
    $temp["description"] = __("Display recent orders from your store within the date range.", $this->textDomain);
    $temp["category"] = __("Commerce", $this->textDomain);
    $temp["premium"] = true;
    $temp["componentPath"] = $scriptPath . "recent-orders.min.js";
    $cards[] = $temp;

    $temp = [];
    $temp["name"] = __("Popular Products", $this->textDomain);
    $temp["moduleName"] = "popular-products";
    $temp["description"] = __("Display top selling products from your store within the date range.", $this->textDomain);
    $temp["category"] = __("Commerce", $this->textDomain);
    $temp["premium"] = false;
    $temp["componentPath"] = $scriptPath . "popular-products.min.js";
    $cards[] = $temp;

    return $cards;
  }

  public function uipress_get_recent_orders()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      if (!is_plugin_active("woocommerce/woocommerce.php")) {
        $returndata["message"] = __("Woocommerce is required for this widget", $this->textDomain);
        $returndata["error"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $page = $this->utils->clean_ajax_input($_POST["currentPage"]);

      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      $args = [
        "post_type" => "shop_order",
        "post_status" => ["wc-completed", "wc-pending", "wc-processing", "wc-on-hold"],
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

        $order_id = $apost->ID;
        $order = wc_get_order($order_id);

        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $cust_name = $billing_first_name . " " . $billing_last_name;

        $temp = [];
        $temp["title"] = "#" . $order->get_order_number();
        $temp["customer"] = $cust_name;
        $temp["status"] = $order->get_status();
        $temp["value"] = $this->format_woo_currency($order->get_total());
        $temp["date"] = $postdate;
        $temp["editURL"] = htmlspecialchars_decode(get_edit_post_link($apost->ID));
        $temp["userURL"] = get_edit_user_link($author_id);

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

  public function uipress_get_popular_products()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      if (!is_plugin_active("woocommerce/woocommerce.php")) {
        $returndata["message"] = __("Woocommerce is required for this widget", $this->textDomain);
        $returndata["error"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $page = $this->utils->clean_ajax_input($_POST["currentPage"]);

      $startDate = date("Y-m-d", strtotime($dates["startDate"]));
      $endDate = date("Y-m-d", strtotime($dates["endDate"]));

      error_log($startDate);
      error_log($endDate);

      $args = [
        "post_type" => "product",
        "post_status" => "any",
        "posts_per_page" => 5,
        "paged" => $page,
        "orderby" => "meta_value_num",
        "meta_key" => "total_sales",
        "order" => "DESC",
      ];

      wp_reset_query();
      $theposts = new WP_Query($args);
      $foundPosts = $theposts->get_posts();

      $formatted = [];

      foreach ($foundPosts as $prouct) {
        $productID = $prouct->ID;

        $temp = [];
        $temp["title"] = get_the_title($productID);
        $temp["salesCount"] = get_post_meta($productID, "total_sales", true);
        $temp["link"] = htmlspecialchars_decode(get_edit_post_link($productID));
        $img = get_the_post_thumbnail_url($productID);

        if ($img) {
          $temp["img"] = $img;
        }

        $product = wc_get_product($productID);
        $price = $product->get_price();
        $total_price = $price * $temp["salesCount"];

        $temp["totalValue"] = $this->format_woo_currency($total_price);

        $formatted[] = $temp;
      }

      $returndata = [];

      $returndata["message"] = __("Posts fetched", $this->textDomain);
      $returndata["posts"] = $formatted;
      $returndata["totalFound"] = $theposts->found_posts;

      $returndata["nocontent"] = "false";
      if ($theposts->found_posts < 1) {
        $returndata["nocontent"] = __("No products sold during the date range.", $this->textDomain);
      }

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_total_sales()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      if (!is_plugin_active("woocommerce/woocommerce.php")) {
        $returndata["message"] = __("Woocommerce is required for this widget", $this->textDomain);
        $returndata["error"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startdate = date("Y-m-d", strtotime($dates["startDate"]));
      $enddate = date("Y-m-d", strtotime($dates["endDate"]));

      //$analyticsData = $this->get_analytics_data($startDate, $endDate);
      ///GET ARRAY OF DATES
      $dates = $this->utils->date_array($startdate, $enddate);

      global $woocommerce;

      $allorders = $this->get_orders($startdate, $enddate);

      $total = 0;

      $orders = $allorders["now"]->posts;
      $total_orders = $allorders["now"]->post_count;
      $array_orders_totals = [];

      foreach ($dates as $date) {
        $array_orders_totals[$date] = 0;
      }

      $total_sales = 0;

      if ($total_orders > 1) {
        foreach ($orders as $ctr => $value) {
          $order_id = $value->ID;

          $order = wc_get_order($order_id);

          $order_total = $order->get_total();
          $order_date = date("d/m/Y", strtotime($order->get_date_created()));

          $array_orders_totals[$order_date] += $order_total;

          $total_sales += $order_total;
        }
      }

      $temparray = [];
      foreach ($array_orders_totals as $item) {
        array_push($temparray, $item);
      }
      $array_orders_totals = $temparray;

      ////COMPARISON
      $array_orders_totals_comp = [];

      $total_orders_comp = $allorders["comparison"]->post_count;
      $orders_comp = $allorders["comparison"]->posts;

      $earlier = new DateTime($startdate);
      $later = new DateTime($enddate);
      $days = $later->diff($earlier)->format("%a");

      $comparisonSD = date("Y-m-d", strtotime($startdate . " -" . $days . " day"));
      $comparisonED = date("Y-m-d", strtotime($startdate));

      $compdates = $this->utils->date_array($comparisonSD, $comparisonED);
      foreach ($compdates as $date) {
        $array_orders_totals_comp[$date] = 0;
      }

      $total_sales_comp = 0;

      if ($total_orders_comp > 1) {
        foreach ($orders_comp as $ctr => $value) {
          error_log("this far");
          $order_id = $value->ID;

          $order = wc_get_order($order_id);

          $order_total = $order->get_total();
          $order_date = date("d/m/Y", strtotime($order->get_date_created()));

          $array_orders_totals_comp[$order_date] += $order_total;

          $total_sales_comp += $order_total;
        }
      }

      $temparray = [];
      $holder = $array_orders_totals_comp;
      foreach ($array_orders_totals_comp as $item) {
        array_push($temparray, $item);
      }
      $array_orders_totals_comp = $temparray;

      $dataSet = [
        "labels" => $dates,
        "datasets" => [
          [
            "label" => __("Total Sales", $this->textDomain),
            "fill" => true,
            "data" => $array_orders_totals,
            "backgroundColor" => ["rgba(12, 92, 239, 0.05)"],
            "borderColor" => ["rgba(12, 92, 239, 1)"],
            "borderWidth" => 2,
          ],
          [
            "label" => __("Total Sales (Comparison)", $this->textDomain),
            "fill" => true,
            "data" => $array_orders_totals_comp,
            "backgroundColor" => ["rgba(247, 127, 212, 0)"],
            "borderColor" => ["rgb(247, 127, 212)"],
            "borderWidth" => 2,
          ],
        ],
      ];

      $total = $total_sales;
      $totalC = $total_sales_comp;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["dataSet"] = $dataSet;
      $returndata["numbers"]["total"] = $this->format_woo_currency($total_sales);
      $returndata["numbers"]["total_comparison"] = $this->format_woo_currency($totalC);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_total_orders()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      if (!is_plugin_active("woocommerce/woocommerce.php")) {
        $returndata["message"] = __("Woocommerce is required for this widget", $this->textDomain);
        $returndata["error"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startdate = date("Y-m-d", strtotime($dates["startDate"]));
      $enddate = date("Y-m-d", strtotime($dates["endDate"]));

      //$analyticsData = $this->get_analytics_data($startDate, $endDate);
      ///GET ARRAY OF DATES
      $dates = $this->utils->date_array($startdate, $enddate);
      $json_dates = json_encode($dates);

      global $woocommerce;

      $allorders = $this->get_orders($startdate, $enddate);

      $total = 0;

      $orders = $allorders["now"]->posts;
      $total_orders = $allorders["now"]->post_count;
      $array_orders_totals = [];

      foreach ($dates as $date) {
        $array_orders_totals[$date] = 0;
      }

      $total_sales = 0;

      if ($total_orders > 1) {
        foreach ($orders as $ctr => $value) {
          $order_id = $value->ID;

          $order = wc_get_order($order_id);

          $order_date = date("d/m/Y", strtotime($order->get_date_created()));

          $array_orders_totals[$order_date] += 1;

          $total_sales += 1;
        }
      }

      $temparray = [];
      foreach ($array_orders_totals as $item) {
        array_push($temparray, $item);
      }
      $array_orders_totals = $temparray;

      ////COMPARISON
      $array_orders_totals_comp = [];

      $total_orders_comp = $allorders["comparison"]->post_count;
      $orders_comp = $allorders["comparison"]->posts;

      $earlier = new DateTime($startdate);
      $later = new DateTime($enddate);
      $days = $later->diff($earlier)->format("%a");

      $comparisonSD = date("Y-m-d", strtotime($startdate . " -" . $days . " day"));
      $comparisonED = date("Y-m-d", strtotime($startdate));

      $compdates = $this->utils->date_array($comparisonSD, $comparisonED);
      foreach ($compdates as $date) {
        $array_orders_totals_comp[$date] = 0;
      }

      $total_sales_comp = 0;

      if ($total_orders_comp > 1) {
        foreach ($orders_comp as $ctr => $value) {
          error_log("this far");
          $order_id = $value->ID;

          $order = wc_get_order($order_id);

          $order_date = date("d/m/Y", strtotime($order->get_date_created()));

          $array_orders_totals_comp[$order_date] += 1;

          $total_sales_comp += 1;
        }
      }

      $temparray = [];
      foreach ($array_orders_totals_comp as $item) {
        array_push($temparray, $item);
      }
      $comp_data = $temparray;

      $dataSet = [
        "labels" => $dates,
        "datasets" => [
          [
            "label" => __("Total Sales", $this->textDomain),
            "fill" => true,
            "data" => $array_orders_totals,
            "backgroundColor" => ["rgba(12, 92, 239, 0.05)"],
            "borderColor" => ["rgba(12, 92, 239, 1)"],
            "borderWidth" => 2,
          ],
          [
            "label" => __("Total Sales (Comparison)", $this->textDomain),
            "fill" => true,
            "data" => $comp_data,
            "backgroundColor" => ["rgba(247, 127, 212, 0)"],
            "borderColor" => ["rgb(247, 127, 212)"],
            "borderWidth" => 2,
          ],
        ],
      ];

      $total = $total_sales;
      $totalC = $total_sales_comp;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["dataSet"] = $dataSet;
      $returndata["numbers"]["total"] = number_format($total_sales, 0);
      $returndata["numbers"]["total_comparison"] = number_format($totalC, 0);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);
      $returndata["numbers"]["dates"] = $dataSet;

      echo json_encode($returndata);
    }
    die();
  }

  public function uipress_analytics_get_average_order_value()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uipress-overview-security-nonce", "security") > 0) {
      if (!is_plugin_active("woocommerce/woocommerce.php")) {
        $returndata["message"] = __("Woocommerce is required for this widget", $this->textDomain);
        $returndata["error"] = true;
        echo json_encode($returndata);
        die();
      }

      $dates = $this->utils->clean_ajax_input($_POST["dates"]);
      $startdate = date("Y-m-d", strtotime($dates["startDate"]));
      $enddate = date("Y-m-d", strtotime($dates["endDate"]));

      //$analyticsData = $this->get_analytics_data($startDate, $endDate);
      ///GET ARRAY OF DATES
      $dates = $this->utils->date_array($startdate, $enddate);
      $json_dates = json_encode($dates);

      global $woocommerce;

      $allorders = $this->get_orders($startdate, $enddate);

      $total = 0;

      $orders = $allorders["now"]->posts;
      $total_orders = $allorders["now"]->post_count;
      $array_orders_totals = [];
      $orders_count = 0;
      $total_sales = 0;

      if ($total_orders > 1) {
        foreach ($orders as $ctr => $value) {
          $order_id = $value->ID;
          $order = wc_get_order($order_id);

          $order_total = $order->get_total();

          $total_sales += $order_total;
          $orders_count += 1;
        }
      }

      if ($total_sales == 0 || $orders_count == 0) {
        $averageOrder = 0;
      } else {
        $averageOrder = $total_sales / $orders_count;
      }

      ////COMPARISON
      $array_orders_totals_comp = [];

      $total_orders_comp = $allorders["comparison"]->post_count;
      $orders_comp = $allorders["comparison"]->posts;

      $total_sales_comp = 0;
      $orders_count_comp = 0;

      if ($total_orders_comp > 1) {
        foreach ($orders_comp as $ctr => $value) {
          error_log("this far");
          $order_id = $value->ID;

          $order = wc_get_order($order_id);
          $order_total = $order->get_total();

          $total_sales_comp += $order_total;
          $orders_count_comp += 1;
        }
      }

      if ($total_sales_comp == 0 || $orders_count_comp == 0) {
        $averageOrderComp = 0;
      } else {
        $averageOrderComp = $total_sales_comp / $orders_count_comp;
      }

      $total = $averageOrder;
      $totalC = $averageOrderComp;

      if ($total == 0 || $totalC == 0) {
        $percentChange = 0;
      } else {
        $percentChange = (($total - $totalC) / $totalC) * 100;
      }

      $returndata["numbers"]["total"] = $this->format_woo_currency($averageOrder);
      $returndata["numbers"]["total_comparison"] = $this->format_woo_currency($averageOrderComp);
      $returndata["numbers"]["change"] = number_format($percentChange, 2);

      echo json_encode($returndata);
    }
    die();
  }

  public function format_woo_currency($number)
  {
    $curreny_symbol = get_woocommerce_currency_symbol();
    $currency_pos = get_option("woocommerce_currency_pos");

    if ($currency_pos == "left") {
      return html_entity_decode($curreny_symbol . number_format($number, 2));
    }

    if ($currency_pos == "right") {
      return html_entity_decode(number_format($number, 2) . $curreny_symbol);
    }

    if ($currency_pos == "left_space") {
      return html_entity_decode($curreny_symbol . " " . number_format($number, 2));
    }

    if ($currency_pos == "right_space") {
      return html_entity_decode(number_format($number, 2) . " " . $curreny_symbol);
    }
  }

  /**
   * Fetches orders  / returns current query
   * @since 1.4
   */

  public function get_orders($startdate = null, $enddate = null)
  {
    if (is_object($this->orders) && $this->start_date == $startdate && $this->end_date == $enddate) {
      return $this->orders;
    } else {
      $this->start_date = $startdate;
      $this->end_date = $enddate;

      $earlier = new DateTime($startdate);
      $later = new DateTime($enddate);
      $days = $later->diff($earlier)->format("%a");

      $comparisonSD = date("Y-m-d", strtotime($startdate . " -" . $days . " day"));
      $comparisonED = date("Y-m-d", strtotime($startdate));

      $args = [
        "post_type" => "shop_order",
        "posts_per_page" => "-1",
        "post_status" => "any",
        "date_query" => [
          [
            "after" => $startdate,
            "before" => $enddate,
            "inclusive" => true,
          ],
        ],
      ];

      wp_reset_query();
      $currentOrders = new WP_Query($args);

      $args = [
        "post_type" => "shop_order",
        "posts_per_page" => "-1",
        "post_status" => "any",
        "date_query" => [
          [
            "after" => $comparisonSD,
            "before" => $comparisonED,
            "inclusive" => true,
          ],
        ],
      ];

      wp_reset_query();
      $comparisonOrders = new WP_Query($args);

      $allOrders = [];
      $allOrders["now"] = $currentOrders;
      $allOrders["comparison"] = $comparisonOrders;

      $this->orders = $allOrders;
      return $allOrders;
    }
  }
}
