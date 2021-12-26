<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_content
{
  public function __construct($version, $pluginName, $pluginPath, $textDomain, $pluginURL)
  {
    $this->version = $version;
    $this->pluginName = $pluginName;
    $this->textDomain = $textDomain;
    $this->path = $pluginPath;
    $this->pathURL = $pluginURL;
    $this->utils = new uipress_util();
    $this->media_date = "";
    $this->attachment_size = "";
    $this->legacyitems = [];
  }

  /**
   * Loads menu actions
   * @since 1.0
   */

  public function run()
  {
    ///REGISTER THIS COMPONENT
    add_filter("uipress_register_settings", [$this, "content_settings_options"], 1, 2);

    ///ADD ACTIOS FOR CONTENT PAGE
    add_action("plugins_loaded", [$this, "add_content_functions"]);

    ////FOLDER AJAX
    require_once $this->path . "admin/classes/folders.php";
    $uipressFolders = new uipress_folders($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressFolders->ajax();
    ///AJAX
    add_action("wp_ajax_a2020_get_content", [$this, "a2020_get_content"]);
    add_action("wp_ajax_a2020_save_view", [$this, "a2020_save_view"]);
    add_action("wp_ajax_a2020_delete_selected", [$this, "a2020_delete_selected"]);
    add_action("wp_ajax_a2020_duplicate_selected", [$this, "a2020_duplicate_selected"]);
    add_action("wp_ajax_a2020_move_content_to_folder", [$this, "a2020_move_content_to_folder"]);
    add_action("wp_ajax_a2020_process_upload", [$this, "a2020_process_upload"]);
    add_action("wp_ajax_a2020_open_quick_edit", [$this, "a2020_open_quick_edit"]);
    add_action("wp_ajax_a2020_update_item", [$this, "a2020_update_item"]);
    add_action("wp_ajax_a2020_batch_tags_cats", [$this, "a2020_batch_tags_cats"]);
    add_action("wp_ajax_a2020_batch_rename_preview", [$this, "a2020_batch_rename_preview"]);
    add_action("wp_ajax_a2020_process_batch_rename", [$this, "a2020_process_batch_rename"]);
    add_action("wp_ajax_a2020_save_edited_image", [$this, "a2020_save_edited_image"]);
    add_action("wp_ajax_uip_save_pref_single", [$this, "uip_save_pref_single"]);
  }

  /**
   * Adds actions for content page
   * @since 1.4
   */

  public function add_content_functions()
  {
    if (!is_admin()) {
      return;
    }

    $utils = new uipress_util();
    $contentDisabled = $utils->get_option("content", "status");
    $contentDisabledForUser = $utils->valid_for_user($utils->get_option("content", "disabled-for", true));

    if ($contentDisabled == "true" || $contentDisabledForUser) {
      return;
    }

    add_action("admin_menu", [$this, "add_menu_item"]);

    if (isset($_GET["page"])) {
      if ($_GET["page"] == "uip-content") {
        add_action("admin_enqueue_scripts", [$this, "add_scripts"], 0);
      }
    }
  }

  /**
   * Returns settings options for settings page
   * @since 2.2
   */
  public function content_settings_options($settings, $network)
  {
    $utils = new uipress_util();

    ///////FOLDER OPTIONS
    $moduleName = "content";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Content Page", $this->textDomain);
    $category["description"] = __("Creates content page", $this->textDomain);
    $category["icon"] = "perm_media";

    $temp = [];
    $temp["name"] = __("Disable Content Page?", $this->textDomain);
    $temp["description"] = __("If disabled, the content page will not be available to any users.", $this->textDomain);
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Content Page Disabled for", $this->textDomain);
    $temp["description"] = __("When the content page module is disabled, the content page will not be accesible for the users / roles", $this->textDomain);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Post types available in content page", $this->textDomain);
    $temp["description"] = __("Only the selected post types will be available in the content page.", $this->textDomain);
    $temp["type"] = "post-type-select";
    $temp["optionName"] = "post-types-content";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Enable private library mode", $this->textDomain);
    $temp["description"] = __("When enabled, the content page will only show content created by or uploaded by the currently logged in user. This also includes folders", $this->textDomain);
    $temp["type"] = "switch";
    $temp["optionName"] = "private-mode";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    return $settings;
  }

  /**
   * Starts uipress folder system
   * @since 2.9
   */

  public function start_folders()
  {
    $uipressFolders = new uipress_folders($this->version, $this->pluginName, $this->path, $this->textDomain, $this->pathURL);
    $uipressFolders->output_for_content();
  }
  /**
   * Enqueue Admin Bar 2020 scripts
   * @since 2.9
   */

  public function add_scripts()
  {
    $types = get_allowed_mime_types();
    $temparay = [];
    foreach ($types as $type) {
      array_push($temparay, $type);
    }

    $folderViews = $this->utils->get_user_preference("content_folder_view");
    $perpage = $this->utils->get_user_preference("content_per_page");
    $gridsize = $this->utils->get_user_preference("content_grid_size");
    $viewmode = $this->utils->get_user_preference("content_view_mode");

    if (!is_numeric($perpage)) {
      $perpage = 20;
    }

    if (!is_numeric($gridsize)) {
      $gridsize = 5;
    }

    if (!$viewmode) {
      $viewmode = "list";
    }

    $renameOptions = [
      [
        "name" => "Original Filename",
        "label" => __("Original Title / Value", $this->textDomain),
      ],
      [
        "name" => "Text",
        "label" => __("Text", $this->textDomain),
      ],
      [
        "name" => "Date Created",
        "label" => __("Date Created", $this->textDomain),
      ],
      [
        "name" => "File Extension",
        "label" => __("File Extension (attachments only)", $this->textDomain),
      ],
      [
        "name" => "Sequence Number",
        "label" => __("Sequence Number", $this->textDomain),
      ],
      [
        "name" => "Meta Value",
        "label" => __("Meta Value", $this->textDomain),
      ],
      [
        "name" => "Find and Replace",
        "label" => __("Find and Replace", $this->textDomain),
      ],
    ];

    $preferences["perPage"] = $perpage;
    $preferences["folderView"] = $folderViews;
    $preferences["gridSize"] = $gridsize;
    $preferences["viewMode"] = $viewmode;
    $preferences["renameOptions"] = $renameOptions;

    wp_enqueue_media();

    ////FILEPOND PLUGINS
    wp_enqueue_script("a2020_filepond_encode", $this->pathURL . "admin/apps/content/js/filepond-file-encode.min.js", ["jquery"], $this->version);
    wp_enqueue_script("a2020_filepond_preview", $this->pathURL . "admin/apps/content/js/filepond-image-preview.min.js", ["jquery"], $this->version);
    wp_enqueue_script("a2020_filepond_orientation", $this->pathURL . "admin/apps/content/js/filepond-orientation.min.js", ["jquery"], $this->version);
    wp_enqueue_script("a2020_filepond_validate", $this->pathURL . "admin/apps/content/js/filepond-validate-size.min.js", ["jquery"], $this->version);
    wp_enqueue_script("a2020_filepond_file_types", $this->pathURL . "admin/apps/content/js/filepond-file-types.min.js", ["jquery"], $this->version);
    ////FILEPOND
    wp_enqueue_script("a2020_filepond", $this->pathURL . "admin/apps/content/js/filepond.min.js", ["jquery"], $this->version);
    wp_enqueue_script("a2020_filepond_jquery", $this->pathURL . "admin/apps/content/js/filepond-jquery.min.js", ["jquery"], $this->version);
    ////DOKA
    wp_enqueue_script("a2020_doka", $this->pathURL . "admin/apps/content/js/doka.js", ["jquery"], $this->version);

    ///LOAD CONTENT APP IN FOOTER
    wp_enqueue_script("admin-content-app", $this->pathURL . "admin/apps/content/js/admin-content-app.min.js", ["uip-app"], $this->version, true);
    wp_localize_script("admin-content-app", "a2020_content_ajax", [
      "ajax_url" => admin_url("admin-ajax.php"),
      "security" => wp_create_nonce("a2020-content-security-nonce"),
      "a2020_allowed_types" => json_encode($temparay),
      "a2020_content_prefs" => json_encode($preferences),
      "contentPage" => true,
    ]);

    ///STYLES
    ///FILEPOND IMAGE PREVIEW
    wp_register_style("admin2020_filepond_preview", $this->pathURL . "admin/apps/content/css/filepond-image-preview.css", [], $this->version);
    wp_enqueue_style("admin2020_filepond_preview");
    ///FILEPOND
    wp_register_style("admin2020_filepond", $this->pathURL . "admin/apps/content/css/filepond.css", [], $this->version);
    wp_enqueue_style("admin2020_filepond");

    wp_register_style("a2020-doka", $this->pathURL . "admin/apps/content/css/doka.css", [], $this->version);
    wp_enqueue_style("a2020-doka");
  }

  /**
   * Saves a singular pref
   * @since 1.4
   */
  public function uip_save_pref_single()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $pref = $utils->clean_ajax_input($_POST["optionName"]);
      $value = $utils->clean_ajax_input($_POST["optionValue"]);

      if ($pref == "" || $value == "'") {
        $message = __("No preferences supplied to save", $this->textDomain);
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }

      $userid = get_current_user_id();
      $current = get_user_meta($userid, "uip-prefs", true);

      if (is_array($current)) {
        $current[$pref] = $value;
      } else {
        $current = [];
        $current[$pref] = $value;
      }

      $state = update_user_meta($userid, "uip-prefs", $current);

      if ($state) {
        $returndata = [];
        $returndata["success"] = true;
        $returndata["message"] = __("Preferences saved", $this->textDomain);
        echo json_encode($returndata);
      } else {
        $message = __("Unable to save user preferences", $this->textDomain);
        $returndata["error"] = true;
        $returndata["message"] = $message;
        echo json_encode($returndata);
        die();
      }
    }
    die();
  }
  /**
   * Processes file upload from image editor
   * @since 1.4
   */
  public function a2020_save_edited_image()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      require_once ABSPATH . "wp-admin/includes/image.php";
      require_once ABSPATH . "wp-admin/includes/file.php";

      $current_imageid = $this->utils->clean_ajax_input($_POST["attachmentid"]);
      $new_file = $_FILES["ammended_image"];

      $upload_overrides = [
        "test_form" => false,
      ];

      $movefile = wp_handle_upload($new_file, $upload_overrides);
      ////ADD Attachment
      if (is_wp_error($movefile)) {
        $message = __("Unable to save attachment", $this->textDomain);
        echo $this->utils->ajax_error_message($message);
        die();
      }

      $status = update_attached_file($current_imageid, $movefile["file"]);
      ////ADD Attachment
      if (!$status) {
        $message = __("Unable to save attachment", $this->textDomain);
        echo $this->utils->ajax_error_message($message);
        die();
      }

      $attach_data = wp_generate_attachment_metadata($current_imageid, $movefile["file"]);
      $status = wp_update_attachment_metadata($current_imageid, $attach_data);

      $returndata = [];
      $returndata["message"] = __("Image Saved", $this->textDomain);
      $returndata["src"] = wp_get_attachment_url($current_imageid);

      ////END ATTACHMENT
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Processes file upload
   * @since 1.4
   */

  public function a2020_process_upload()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      require_once ABSPATH . "wp-admin/includes/image.php";
      require_once ABSPATH . "wp-admin/includes/file.php";

      $folder = $this->utils->clean_ajax_input($_POST["folder"]);

      foreach ($_FILES as $file) {
        $uploadedfile = $file;
        $upload_overrides = [
          "test_form" => false,
        ];

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        // IF ERROR
        if (is_wp_error($movefile)) {
          http_response_code(400);
          $returndata["error"] = __("Failed to upload file", $this->textDomain);
          echo json_encode($returndata);
          die();
        }
        ////ADD Attachment

        $wp_upload_dir = wp_upload_dir();
        $withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', "", $uploadedfile["name"]);

        $attachment = [
          "guid" => $movefile["url"],
          "post_mime_type" => $movefile["type"],
          "post_title" => $withoutExt,
          "post_content" => "",
          "post_status" => "published",
        ];

        $id = wp_insert_attachment($attachment, $movefile["file"], 0);

        $attach_data = wp_generate_attachment_metadata($id, $movefile["file"]);
        wp_update_attachment_metadata($id, $attach_data);

        if (is_numeric($folder) && $folder > 0) {
          update_post_meta($id, "admin2020_folder", $folder);
        }

        ////END ATTACHMENT
      }
      //echo $this->build_media();
      http_response_code(200);
      $returndata["message"] = __("Items uploaded", $this->textDomain);
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Saves new view
   * @since 2.9
   */

  public function a2020_save_view()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $views = $this->utils->clean_ajax_input($_POST["allViews"]);

      $a2020_options = get_option("uip-settings");

      $a2020_options["content"]["views"] = $views;

      update_option("uip-settings", $a2020_options);

      $returndata = [
        "message" => __("Views updated", $this->textDomain),
      ];

      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Deletes selected items
   * @since 2.9
   */

  public function a2020_duplicate_selected()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $itemIDs = $this->utils->clean_ajax_input($_POST["selected"]);
      $returndata = [];
      $returndata["totalduplicated"] = 0;
      $returndata["totalfailed"] = 0;

      if ($itemIDs && is_array($itemIDs)) {
        foreach ($itemIDs as $item) {
          $status = $this->a2020_duplicate_post($item);

          if ($status) {
            $returndata["totalduplicated"] += 1;
          } else {
            $returndata["totalfailed"] += 1;
          }
        }
      } else {
        $returndata["error"] = __("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $returndata["deleted_message"] = __("Items duplicated succesffuly", $this->textDomain);
      $returndata["deleted_total"] = $returndata["totalduplicated"];

      $returndata["failed_message"] = __("Itms couldn't be duplicated", $this->textDomain);
      $returndata["failed_total"] = $returndata["totalfailed"];
      echo json_encode($returndata);
      die();
    }

    die();
  }

  /**
   * Batch rename preview
   * @since 2.9
   */

  public function a2020_batch_rename_preview()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $itemIDs = $this->utils->clean_ajax_input($_POST["selected"]);
      $batchOptions = $this->utils->clean_ajax_input($_POST["batchoptions"]);
      $fieldtorename = $this->utils->clean_ajax_input($_POST["fieldToRename"]);
      $metaKey = $this->utils->clean_ajax_input($_POST["metaKey"]);

      $returndata = [];
      $returndata["newnames"] = [];
      $returndata["options"] = $batchOptions;

      if ($itemIDs && is_array($itemIDs)) {
        $sequence = 0;

        foreach ($itemIDs as $item) {
          $temp = [];

          if ($fieldtorename == "name") {
            $temp["current"] = get_the_title($item);
          }

          if ($fieldtorename == "meta") {
            if (!$metaKey || $metaKey == "") {
              $temp["current"] = __("No Meta Key provided", $this->textDomain);
            } else {
              $temp["current"] = get_post_meta($item, $metaKey, true);
            }
          }

          if ($fieldtorename == "alt") {
            $temp["current"] = get_post_meta($item, "_wp_attachment_image_alt", true);
          }

          $temp["new"] = $this->generate_new_name($item, $batchOptions, $sequence, $fieldtorename, $metaKey);
          $sequence += 1;

          array_push($returndata["newnames"], $temp);
        }
      } else {
        $returndata["error"] = __("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      echo json_encode($returndata);
      die();
    }

    die();
  }

  public function a2020_process_batch_rename()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $itemIDs = $this->utils->clean_ajax_input($_POST["selected"]);
      $batchOptions = $this->utils->clean_ajax_input($_POST["batchoptions"]);
      $fieldtorename = $this->utils->clean_ajax_input($_POST["fieldToRename"]);
      $metaKey = $this->utils->clean_ajax_input($_POST["metaKey"]);

      $returndata = [];
      $returndata["newnames"] = [];
      $returndata["options"] = $batchOptions;

      if ($fieldtorename == "meta") {
        if (!$metaKey || $metaKey == "") {
          $returndata["error"] = __("No Meta Key provided", $this->textDomain);
          echo json_encode($returndata);
          die();
        }
      }

      if ($itemIDs && is_array($itemIDs)) {
        $sequence = 0;

        foreach ($itemIDs as $item) {
          $temp = [];
          $postType = get_post_type($item);

          $newvalue = $this->generate_new_name($item, $batchOptions, $sequence, $fieldtorename, $metaKey);
          $sequence += 1;

          if ($fieldtorename == "name") {
            $update = [
              "ID" => $item,
              "post_title" => $newvalue,
            ];

            wp_update_post($update);
          }

          if ($fieldtorename == "meta") {
            update_post_meta($item, $metaKey, $newvalue);
          }

          if ($fieldtorename == "alt") {
            if ($postType == "attachment") {
              update_post_meta($item, "_wp_attachment_image_alt", $newvalue);
            }
          }
        }
      } else {
        $returndata["error"] = __("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }
      $returndata["message"] = __("Attributes Updated", $this->textDomain);
      echo json_encode($returndata);
      die();
    }

    die();
  }

  public function generate_new_name($item, $options, $sequence, $fieldtorename, $metaKey)
  {
    if ($fieldtorename == "name") {
      $name = get_the_title($item);
    }

    if ($fieldtorename == "meta") {
      if (!$metaKey || $metaKey == "") {
        $name = "";
      } else {
        $name = get_post_meta($item, $metaKey, true);
      }
    }

    if ($fieldtorename == "alt") {
      $name = get_post_meta($item, "_wp_attachment_image_alt", true);
    }

    $postType = get_post_type($item);
    $newname = "";

    foreach ($options as $option) {
      $type = $option["name"];

      if ($type == "Text") {
        $textValue = $option["primaryValue"];
        $newname = $newname . $textValue;
      }

      if ($type == "Original Filename") {
        $newname = $newname . $name;
      }

      if ($type == "Date Created") {
        $format = $option["primaryValue"];
        $thedate = get_the_date($format, $item);
        $newname = $newname . $thedate;
      }

      if ($type == "File Extension") {
        if ($postType != "attachment") {
          continue;
        }
        $attachment_url = wp_get_attachment_url($item);
        $filetype = wp_check_filetype($attachment_url);
        $extension = $filetype["ext"];
        $newname = $newname . $extension;
      }

      if ($type == "Sequence Number") {
        $start_number = $option["primaryValue"];
        if (!is_numeric($start_number)) {
          $start_number = 0;
        }
        $thenum = $start_number + $sequence;

        $newname = $newname . $thenum;
      }

      if ($type == "Meta Value") {
        $metakey = $option["primaryValue"];
        if (!$metakey || $metakey == "") {
          continue;
        }
        $value = get_post_meta($item, $metakey, true);

        if (!$value || $value == "") {
          continue;
        }
        $newname = $newname . $value;
      }

      if ($type == "Find and Replace") {
        $find = $option["primaryValue"];
        $replace = $option["secondaryValue"];
        $output = str_replace($find, $replace, $name);
        $newname = $newname . $output;
      }
    }

    return $newname;
  }

  /**
   * Duplicates a single post
   * @since 2.9
   */
  public function a2020_duplicate_post($post_id)
  {
    global $wpdb;
    $post = get_post($post_id);

    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    $args = [
      "comment_status" => $post->comment_status,
      "ping_status" => $post->ping_status,
      "post_author" => $new_post_author,
      "post_content" => $post->post_content,
      "post_excerpt" => $post->post_excerpt,
      "post_name" => $post->post_name,
      "post_parent" => $post->post_parent,
      "post_password" => $post->post_password,
      "post_status" => "draft",
      "post_title" => $post->post_title . " (copy)",
      "post_type" => $post->post_type,
      "to_ping" => $post->to_ping,
      "menu_order" => $post->menu_order,
    ];

    $new_post_id = wp_insert_post($args);

    if (!$new_post_id) {
      return false;
    }

    $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
    foreach ($taxonomies as $taxonomy) {
      $post_terms = wp_get_object_terms($post_id, $taxonomy, ["fields" => "slugs"]);
      wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
    }

    $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
    if (count($post_meta_infos) != 0) {
      $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
      foreach ($post_meta_infos as $meta_info) {
        $meta_key = $meta_info->meta_key;
        if ($meta_key == "_wp_old_slug") {
          continue;
        }
        $meta_value = addslashes($meta_info->meta_value);
        $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
      }

      $sql_query .= implode(" UNION ALL ", $sql_query_sel);
      $wpdb->query($sql_query);
    }

    $postobject = get_post($new_post_id);

    return true;
  }

  /**
   * Deletes selected items
   * @since 2.9
   */

  public function a2020_delete_selected()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $itemIDs = $this->utils->clean_ajax_input($_POST["selected"]);
      $returndata = [];
      $returndata["totaldeleted"] = 0;
      $returndata["totalfailed"] = 0;

      if ($itemIDs && is_array($itemIDs)) {
        foreach ($itemIDs as $item) {
          $currentID = get_current_user_id();

          if (!current_user_can("delete_post", $item)) {
            $returndata["totalfailed"] += 1;
          } else {
            if (get_post_type($item) == "attachment") {
              $status = wp_delete_attachment($item);
            } else {
              $status = wp_delete_post($item);
            }

            if ($status) {
              $returndata["totaldeleted"] += 1;
            } else {
              $returndata["totalfailed"] += 1;
            }
          }
        }
      } else {
        $returndata["error"] = __("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $returndata["deleted_message"] = __("Items deleted succesffuly", $this->textDomain);
      $returndata["deleted_total"] = $returndata["totaldeleted"];

      $returndata["failed_message"] = __("Itms couldn't be deleted", $this->textDomain);
      $returndata["failed_total"] = $returndata["totalfailed"];
      echo json_encode($returndata);
      die();
    }

    die();
  }

  /**
   * Moves content to Folder
   * @since 2.9
   */
  public function a2020_move_content_to_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $contentIds = $this->utils->clean_ajax_input($_POST["contentID"]);
      $destination = $this->utils->clean_ajax_input($_POST["destinationId"]);

      $contentIds = json_decode($contentIds);

      if (!is_array($contentIds)) {
        $returndata["error"] = __("No content to move", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      foreach ($contentIds as $contentId) {
        if ($destination == "toplevel") {
          $status = delete_post_meta($contentId, "admin2020_folder");
        } else {
          $status = update_post_meta($contentId, "admin2020_folder", $destination);
        }
      }

      $returndata["message"] = __("Content moved", $this->textDomain);
      echo json_encode($returndata);
      die();
    }
    die();
  }

  /**
   * Builds posts object for quick edits.
   * @since 2.9
   */

  public function a2020_open_quick_edit()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $itemId = $this->utils->clean_ajax_input($_POST["itemid"]);
      $object = get_post($itemId);
      $author = $object->post_author;
      $authorData = get_userdata($author);
      $posttype = get_post_type($itemId);

      $statusobject = get_post_status_object($object->post_status);
      $niceStatus = $statusobject->label;

      $alltags = wp_get_post_tags($itemId);
      $selectedTags = [];

      foreach ($alltags as $tag) {
        $selectedTags[] = $tag->term_id;
      }

      $quickedit["id"] = $itemId;
      $quickedit["title"] = $object->post_title;
      $quickedit["status"] = $niceStatus;
      $quickedit["author"] = $authorData->user_login;
      $quickedit["created"] = get_the_date(get_option("date_format"), $itemId);
      $quickedit["modified"] = get_the_modified_date(get_option("date_format"), $itemId);
      $quickedit["postType"] = $posttype;
      $quickedit["url"] = get_post_permalink($itemId);

      if ($posttype == "attachment") {
        $meta = wp_get_attachment_metadata($itemId);
        $mime = get_post_mime_type($itemId);
        $actualMime = explode("/", $mime);
        $actualMime = $actualMime[0];

        $quickedit["fileSize"] = $this->utils->formatBytes(filesize(get_attached_file($itemId)));

        if ($meta == true) {
          $quickedit["dimensions"] = $meta["width"] . "px " . $meta["height"] . "px";
          $quickedit["serverName"] = $meta["file"];
          $quickedit["photoMeta"] = $meta["image_meta"];
        }
        $quickedit["shortMime"] = $actualMime;
        $quickedit["src"] = wp_get_attachment_url($itemId);
        $quickedit["alt"] = get_post_meta($itemId, "_wp_attachment_image_alt", true);
        $quickedit["description"] = $object->post_content;
        $quickedit["caption"] = $object->post_excerpt;

        if (strpos($mime, "/zip") !== false) {
          $quickedit["icontype"] = "icon";
          $quickedit["icon"] = "inventory_2";
        }

        if (strpos($mime, "/pdf") !== false) {
          $quickedit["icontype"] = "icon";
          $quickedit["icon"] = "picture_as_pdf";
          $quickedit["pdf"] = true;
        }

        if (strpos($mime, "text") !== false) {
          $quickedit["icontype"] = "icon";
          $quickedit["icon"] = "description";
        }

        if (strpos($mime, "/csv") !== false) {
          $quickedit["icontype"] = "icon";
          $quickedit["icon"] = "view_list";
        }
      } else {
        $quickedit["selectedStatus"] = [$object->post_status];
        $quickedit["selectedCategories"] = wp_get_post_categories($itemId);
        $quickedit["selectedTags"] = $selectedTags;
      }

      echo json_encode($quickedit);
    }
    die();
  }

  /**
   * Update item from quick edit
   * @since 2.9
   */

  public function a2020_update_item()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $itemObject = $this->utils->clean_ajax_input($_POST["options"]);
      $itemId = $itemObject["id"];
      $posttype = get_post_type($itemId);

      if ($posttype != "attachment") {
        $updatePost = [
          "ID" => $itemId,
          "post_title" => $itemObject["title"],
        ];

        if (isset($itemObject["selectedStatus"])) {
          $updatePost["post_status"] = $itemObject["selectedStatus"][0];
        }

        $status = wp_update_post($updatePost);

        if (isset($itemObject["selectedCategories"])) {
          wp_set_post_categories($itemId, $itemObject["selectedCategories"]);
        }

        if (isset($itemObject["selectedTags"])) {
          foreach ($itemObject["selectedTags"] as $tag) {
            $alltags[] = (int) $tag;
          }
          wp_set_post_tags($itemId, $alltags, false);
        }

        if ($status == 0) {
          $returndata["error"] = __("Unable to update item", $this->textDomain);
          echo json_encode($returndata);
          die();
        }

        $statusobject = get_post_status_object($itemObject["selectedStatus"][0]);
        $niceStatus = $statusobject->label;
      } else {
        $attachment = [
          "ID" => strip_tags($itemId),
          "post_title" => strip_tags($itemObject["title"]),
          "post_content" => strip_tags($itemObject["description"]),
          "post_excerpt" => strip_tags($itemObject["caption"]),
        ];

        update_post_meta($itemId, "_wp_attachment_image_alt", strip_tags($itemObject["alt"]));
        $status = wp_update_post($attachment);

        if (!$status) {
          $message = __("Unable to save attachment", $this->textDomain);
          echo $this->utils->ajax_error_message($message);
          die();
        }

        $postObj = get_post($itemId);
        $status = $postObj->post_status;
        $statusobject = get_post_status_object($status);
        $niceStatus = $statusobject->label;
      }

      $returndata["message"] = __("Item updated", $this->textDomain);
      $returndata["status"] = $niceStatus;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Update item from quick edit
   * @since 2.9
   */

  public function a2020_batch_tags_cats()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      $selected = $this->utils->clean_ajax_input($_POST["selected"]);
      $data = $this->utils->clean_ajax_input($_POST["theTags"]);
      $replaceTags = $data["replaceTags"] == "true";
      $replaceCats = $data["replaceCats"] == "true";
      $alltags = [];

      foreach ($selected as $itemId) {
        $posttype = get_post_type($itemId);

        if ($posttype != "attachment") {
          if (isset($data["tags"])) {
            foreach ($data["tags"] as $tag) {
              $alltags[] = (int) $tag;
            }

            wp_set_post_tags($itemId, $alltags, $replaceTags);
          }

          if (isset($data["categories"])) {
            wp_set_post_categories($itemId, $data["categories"], $replaceCats);
          }
        }
      }

      $returndata["message"] = __("Items updated", $this->textDomain);
      $returndata["status"] = $niceStatus;
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Build content for front end app
   * @since 2.9
   */

  public function a2020_get_content()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("a2020-content-security-nonce", "security") > 0) {
      ////CATEGORIES
      $args = [
        "hide_empty" => false,
      ];

      $allCategories = get_categories($args);
      $categories = [];

      foreach ($allCategories as $category) {
        $temp = [];
        $temp["name"] = strval($category->term_id);
        $temp["label"] = $category->name;
        $categories[] = $temp;
      }

      //TAGS
      $alltags = get_tags();
      $tags = [];

      foreach ($alltags as $tag) {
        $temp = [];
        $temp["name"] = strval($tag->term_id);
        $temp["label"] = $tag->name;
        $tags[] = $temp;
      }

      $searchString = $this->utils->clean_ajax_input($_POST["searchString"]);
      $page = $this->utils->clean_ajax_input($_POST["page"]);
      $filters = $this->utils->clean_ajax_input($_POST["filters"]);

      $date = $filters["date"];
      $dateComparison = $filters["dateComparison"];

      $queryStatus = "any";

      $postStatuses = get_post_statuses();
      $statuses = [];

      $temp = [];
      $temp["label"] = "Inherit";
      $temp["name"] = "inherit";
      $statuses[] = $temp;

      foreach ($postStatuses as $key => $value) {
        $temp = [];
        $temp["name"] = $key;
        $temp["label"] = $value;
        $statuses[] = $temp;
      }

      if (isset($_POST["statuses"])) {
        $queryStatus = $this->utils->clean_ajax_input($_POST["statuses"]);
      }

      ////QUERY POSTS
      $types = [];
      $args = ["public" => true];
      $output = "objects";
      $post_types = get_post_types($args, $output);
      $filterPostTypes = [];

      $temp = [];

      foreach ($post_types as $posttype) {
        array_push($types, $posttype->name);
        $temp = [];
        $temp["label"] = $posttype->label;
        $temp["name"] = $posttype->name;
        $filterPostTypes[] = $temp;
      }

      $post_types_enabled = $this->utils->get_option("content", "post-types-content");
      $privatemode = $this->utils->get_option("content", "private-mode");

      if ($post_types_enabled && is_array($post_types_enabled)) {
        $types = $post_types_enabled;
        $filterPostTypes = [];

        foreach ($types as $atype) {
          $typeObject = get_post_type_object($atype);
          $temp = [];
          $temp["label"] = $typeObject->label;
          $temp["name"] = $typeObject->name;
          $filterPostTypes[] = $temp;
        }
      }

      if (isset($_POST["types"])) {
        $types = $this->utils->clean_ajax_input($_POST["types"]);
      }

      $args = [
        "post_type" => $types,
        "post_status" => $queryStatus,
        "posts_per_page" => $filters["perPage"],
        "paged" => $page,
        "s" => $searchString,
      ];

      if ($privatemode == "true") {
        $args["author"] = get_current_user_id();
      }

      if (isset($filters["selectedFileTypes"])) {
        $args["post_mime_type"] = $filters["selectedFileTypes"];
      }

      if (isset($filters["selectedCategories"])) {
        $args["category__in"] = $filters["selectedCategories"];
      }

      if (isset($filters["selectedTags"])) {
        $args["tag__in"] = $filters["selectedTags"];
      }

      if ($date && $dateComparison) {
        if ($dateComparison == "on") {
          $year = date("Y", strtotime($date));
          $month = date("m", strtotime($date));
          $day = date("d", strtotime($date));

          $args["date_query"] = [
            "year" => $year,
            "month" => $month,
            "day" => $day,
          ];
        } else {
          if ($dateComparison == "before") {
            $args["date_query"] = [
              [
                "before" => date("Y-m-d", strtotime($date)),
                "inclusive" => true,
              ],
            ];
          } elseif ($dateComparison == "after") {
            $args["date_query"] = [
              [
                "after" => date("Y-m-d", strtotime($date)),
                "inclusive" => true,
              ],
            ];
          }
        }
      }

      if (isset($filters["activeFolder"]) && $filters["activeFolder"] != "" && $filters["activeFolder"] != "all") {
        if ($filters["activeFolder"] == "nofolder") {
          $args["meta_query"] = [
            [
              "key" => "admin2020_folder",
              "compare" => "NOT EXISTS",
            ],
          ];
        } else {
          $args["meta_query"] = [
            [
              "key" => "admin2020_folder",
              "value" => $filters["activeFolder"],
              "compare" => "=",
            ],
          ];
        }
      }

      wp_reset_query();
      $attachments = new WP_Query($args);
      $totalFound = $attachments->found_posts;
      $foundPosts = $attachments->get_posts();
      $totalPages = $attachments->max_num_pages;

      ///BUILD RETURN DATA
      $postData = [];

      foreach ($foundPosts as $item) {
        $postAuthorId = $item->post_author;
        $authorData = get_userdata($postAuthorId);
        $authorLink = get_edit_profile_url($postAuthorId);
        $postType = get_post_type($item->ID);

        $statusObj = get_post_status_object($item->post_status);
        $postStatus = $statusObj->label;

        $temp = [];
        $temp["name"] = $item->post_title;
        $temp["type"] = $postType;
        $temp["date"] = get_the_date(get_option("date_format"), $item);
        $temp["author"] = $authorData->user_login;
        $temp["authorLink"] = $authorLink;
        $temp["status"] = $postStatus;
        $temp["id"] = $item->ID;
        $temp["url"] = get_the_permalink($item->ID);
        $temp["editurl"] = get_edit_post_link($item->ID, "&");

        if ($postType == "attachment") {
          $mime = get_post_mime_type($item);
          $actualMime = explode("/", $mime);
          $actualMime = $actualMime[0];

          $attachment_info = wp_get_attachment_image_src($item->ID, "thumbnail", true);
          $imageMed = wp_get_attachment_image_src($item->ID, "medium", true);
          $small_src = $attachment_info[0];
          $temp["icontype"] = "image";
          $temp["icon"] = $small_src;
          $temp["iconLarge"] = $imageMed[0];
          $temp["mime"] = $mime;
          $temp["fileUrl"] = wp_get_attachment_url($item->ID);

          if ($actualMime == "audio") {
            $temp["icontype"] = "icon";
            $temp["icon"] = "audiotrack";
          }

          if ($actualMime == "video") {
            $temp["icontype"] = "icon";
            $temp["icon"] = "smart_display";
          }

          if (strpos($mime, "/zip") !== false) {
            $temp["icontype"] = "icon";
            $temp["icon"] = "inventory_2";
          }

          if (strpos($mime, "/pdf") !== false) {
            $temp["icontype"] = "icon";
            $temp["icon"] = "picture_as_pdf";
            $temp["pdf"] = true;
          }

          if (strpos($mime, "text") !== false) {
            $temp["icontype"] = "icon";
            $temp["icon"] = "description";
          }
        } else {
          $image = get_the_post_thumbnail_url($item->ID, "thumbnail");
          $imageMed = wp_get_attachment_url(get_post_thumbnail_id($item->ID));

          if ($image) {
            $temp["icontype"] = "image";
            $temp["icon"] = $image;
            $temp["iconLarge"] = $imageMed;
          } else {
            $temp["icontype"] = "icon";
            $temp["icon"] = "library_books";
          }
        }

        $postData[] = $temp;
      }

      /////VIEWS
      $currentviews = [];
      $a2020_options = get_option("uip-settings");

      if (isset($a2020_options["content"]["views"])) {
        $currentviews = $a2020_options["content"]["views"];
      }

      $count = 0;
      $allViews = [];

      if ($currentviews && is_array($currentviews)) {
        foreach ($currentviews as $view) {
          $view["id"] = $count;
          $count += 1;
          $allViews[] = $view;
        }
      }

      $filetypes[] = ["name" => "image", "label" => "Image"];
      $filetypes[] = ["name" => "video", "label" => "Video"];
      $filetypes[] = ["name" => "application", "label" => "Zip"];
      $filetypes[] = ["name" => "text", "label" => "Text File"];
      $filetypes[] = ["name" => "audio", "label" => "Audio"];

      $returndata = [];
      $returndata["content"] = $postData;
      $returndata["total"] = $totalFound;
      $returndata["totalPages"] = $totalPages;
      $returndata["postTypes"] = $filterPostTypes;
      $returndata["postStatuses"] = $statuses;
      $returndata["fileTypes"] = $filetypes;
      $returndata["categories"] = $categories;
      $returndata["views"] = $allViews;
      $returndata["tags"] = $tags;

      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Adds media menu item
   * @since 2.9
   */

  public function add_menu_item()
  {
    add_menu_page("uip-content", __("Content", $this->textDomain), "read", "uip-content", [$this, "build_content_page"], "dashicons-database", 4);
    return;
  }

  /**
   * Build content page
   * @since 2.9
   */

  public function build_content_page()
  {
    $utils = new uipress_util();
    $foldersOn = $utils->get_option("folders", "status");
    $foldersDisabledForUser = $utils->valid_for_user($utils->get_option("folders", "disabled-for", true));
    $dontShowfolders = false;
    $previewImage = $this->pathURL . "assets/img/content-preview.png";

    if ($foldersOn == "true" || $foldersDisabledForUser) {
      $dontShowfolders = true;
    }
    ?>
	<style>
		  #wpcontent{
			  padding-left: 0;
		  }
	</style>
  
		
		<div id="a2020-content-app" class="uip-text-normal uip-background-default" >
      <div class=" uip-fade-in uip-hidden" :class="{'uip-nothidden' : masterLoader}" v-if="masterLoader">
        <div  v-if="masterLoader && !dataConnect" class="uip-width-100p uip-position-relative">
          <img class="uip-w-100p " src="<?php echo $previewImage; ?>">
          
          
          <div class="uip-position-absolute uip-top-0 uip-bottom-0 uip-left-0 uip-right-0" 
          style="background: linear-gradient(0deg, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);"></div>
          
          <div class="uip-position-absolute uip-top-0 uip-bottom-0 uip-left-0 uip-right-0 uip-flex uip-flex-center uip-flex-middle">
            
            
            <div class="uip-background-default uip-border-round uip-padding-m uip-shadow uip-flex uip-flex-center uip-flex-column">
              <div class="uip-flex uip-text-l uip-text-bold uip-margin-bottom-s">
                <span class="material-icons-outlined uip-margin-right-xs">redeem</span>
                <span><?php _e("Pro Feature", $this->textDomain); ?></span>
              </div> 
              
              <p class="uip-text-normal uip-margin-bottom-m"><?php _e("Upgrade to UiPress Pro to unlock the content page and content folders", $this->textDomain); ?></p>
              
              <a href="https://uipress.co/pricing/" target="_BLANK" class="uip-button-primary uip-no-underline"><?php _e("See UiPress Pro Plans", $this->textDomain); ?></a>
            </div>
            
          </div>
        </div>
        
  			<div class="uip-padding-m" v-if="masterLoader && dataConnect">
  				<?php $this->build_header(); ?>
          <?php $this->build_toolbar(); ?>
          <?php $this->active_filters(); ?>
      
  				
  				<div class="uip-flex uip-flex-wrap" >
            <?php if (!$dontShowfolders) { ?>
  					<div class="uip-width-xxsmall uip-margin-right-m uip-hidden uip-flex-no-shrink uip-margin-bottom-s" :class="{'uip-nothidden-block' : contentTable.folderPanel}">
  				    <?php $this->start_folders(); ?>
  					</div>
            <?php } ?>
  					
  					<div class="uip-width-auto">	
  					<?php $this->build_table(); ?>
  					</div>
  				</div>
  				<?php $this->build_batch_options(); ?>
          <?php $this->build_quick_edit_modal(); ?>
          <?php $this->save_view_options(); ?>
          <?php $this->build_batch_tags_and_categories(); ?>
          <?php $this->build_batch_rename(); ?>
  			</div>
        
      </div>
		</div>
		<?php
  }

  /**
   * Build batch tags and cats modal
   * @since 2.9
   */

  public function build_batch_tags_and_categories()
  {
    ?>
		<uip-offcanvas-nb v-if="ui.catsTags" @state-change="ui.catsTags = getdatafromComp($event)"
		:toggle="ui.catsTags" title="<?php _e("Update Tags and Categories", $this->textDomain); ?>" type="icon" icon="filter_list" pos="botton-left">
			
		 
			<div class="uip-margin-top-m">
			
				<div class="uip-margin-bottom-s">
					<multi-select :options="contentTable.categories" :selected="batchUpdate.categories"
					:name="'<?php _e("Categories", $this->textDomain); ?>'"
					:placeholder="'<?php _e("Search categories...", $this->textDomain); ?>'"></multi-select>
					
					<div class="uk-margin-small-top">
						<label class="uk-text-meta uk-margin-small-top">
							<input type="checkbox"  v-model="batchUpdate.replaceCats"> 
							<?php _e("Keep existing categories", $this->textDomain); ?>
						</label>
					</div>
				</div>	
				
				
				<div class="uip-margin-bottom-s">
				  <!--CONTAINER -->
					<multi-select :options="contentTable.tags" :selected="batchUpdate.tags"
					:name="'<?php _e("Tags", $this->textDomain); ?>'"
					:placeholder="'<?php _e("Search tags...", $this->textDomain); ?>'"></multi-select>
					
					<div class="uk-margin-small-top">
						<label class="uk-text-meta ">
						  <input type="checkbox" class="uk-checkbox uk-margin-small-right" v-model="batchUpdate.replaceTags"> 
						  <?php _e("Keep existing tags", $this->textDomain); ?>
						</label>
					</div>
				</div>
			  
			  
			</div>
			<div class="uip-position-fixed uip-bottom-0  uip-right-0 uip-w-500 uip-padding-m uip-flex uip-flex-between uip-background-muted">
				<div class="uip-flex uip-flex-right uip-w-100p">
					<button class="uip-button-primary" @click="batchUpdateTagsCats()"> <?php _e("Update", $this->textDomain); ?> </button>
				</div>
			</div>
			
		</uip-offcanvas-nb>
		<?php
  }

  /**
   * Build batch rename modal
   * @since 2.9
   */

  public function build_batch_rename()
  {
    ?>
		<uip-offcanvas-nb v-if="ui.batchRename" @state-change="ui.batchRename = getdatafromComp($event)"
		:toggle="ui.batchRename" title="<?php _e("Batch Rename", $this->textDomain); ?>" type="icon" icon="filter_list" pos="botton-left">
		
		  <div class="">
				
				
				<div>
					
					<div class="uip-margin-bottom-xs">
						<div class="uip-text-bold"><?php _e("Attribute to rename", $this->textDomain); ?></div>
					</div>
					
					<div class="uip-flex uip-margin-bottom-s">
					
						<div class=" uip-margin-right-xs">
							<select class="" v-model="batchRename.selectedAttribute">
									<option value="name"><?php _e("Name", $this->textDomain); ?></option>
									<option value="meta"><?php _e("Meta Key", $this->textDomain); ?></option>
									<option value="alt"><?php _e("Alt Tag (Attachments only)", $this->textDomain); ?></option>
							</select>
						</div>
						
						<div  v-if="batchRename.selectedAttribute == 'meta'">
							<input  class="uk-input" v-model="batchRename.metaKey" type="text" placeholder="<?php _e("Meta Key Name"); ?>">
						</div>
					
					</div>
					
					
				</div>
				
				<div class="uip-margin-bottom-m">
					
						<div class="uip-margin-bottom-xs">
							<div class="uip-text-bold"><?php _e("Filename Structure", $this->textDomain); ?></div>
						</div>
						
						
						<div class="uip-flex uip-margin-bottom-s">
							
							<div class="uip-margin-right-xs">
								<select  v-model="batchRename.selectedOption">
									<option disabled selected value="0"><?php _e("Rename Item", $this->textDomain); ?></option>
									<template v-for="item in batchRename.renameTypes">
										<option :value="item.name">{{item.label}}</option>
									</template>
								</select>
							</div>
							
							<div >
								<button v-if="batchRename.selectedOption" @click="addBatchNameOption()" class="uip-button-default"><?php _e("Add", $this->textDomain); ?></button>
							</div>
						
						</div>
						
				</div>
			
				<div class="uip-margin-bottom-s">
					
					
					
					<div v-for="(item, index) in batchRename.selectedTypes">
						
						<div class="uip-flex uip-margin-bottom-s uip-flex-between">
							
							<div class="uip-flex">
							
								<div class="uip-margin-right-xs">
									<select class="uip-w-100" v-model="item.name">
										<template v-for="type in batchRename.renameTypes">
											<option :value="type.name">{{type.label}}</option>
										</template>
									</select>
								</div>
								
								<div class="uip-margin-right-xs">
									<input v-if="item.name == 'Text'" v-model="item.primaryValue" class="uip-w-100" type="text" placeholder="<?php _e("New Text", $this->textDomain); ?>">
									
									<input v-if="item.name == 'Date Created'" v-model="item.primaryValue" class="uip-w-100" type="text" placeholder="<?php _e("Date Format", $this->textDomain); ?>">
									
									<input v-if="item.name == 'Sequence Number'" v-model="item.primaryValue" class="uip-w-100" type="number" placeholder="<?php _e("Start Number", $this->textDomain); ?>">
									
									<input v-if="item.name == 'Meta Value'" v-model="item.primaryValue" class="uip-w-100" type="text" placeholder="<?php _e("Meta key Name", $this->textDomain); ?>">
									
									<input v-if="item.name == 'Find and Replace'" v-model="item.primaryValue" class="uip-w-100" type="text" placeholder="<?php _e("Find", $this->textDomain); ?>">
								</div>
								
								<div class="uip-margin-right-xs" >
									<input v-if="item.name == 'Find and Replace'" v-model="item.secondaryValue" class="uip-w-100" type="text" placeholder="<?php _e("Replace", $this->textDomain); ?>">	
								</div>
							
							</div>
							
							<div class="uip-flex">
								<div v-if="batchRename.selectedTypes.length > 1" class="uip-margin-right-xs uip-flex">
									<span class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer" @click="moveBatchOptionDown(index)">expand_more
										</span>
										
									<span class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-margin-left-xs" @click="moveBatchOptionUp(index)">expand_less
										</span>
								</div>
								<span class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer" @click="removeBatchOption(index)">remove_circle_outline
								</span>
							</div>
							
						</div>
					</div>
					
					
				</div>	
				
				<div v-if="batchRename.preview.length > 0" class="uip-margin-top-m">
					<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Preview", $this->textDomain); ?></div>
					<div class="uip-margin-bottom-s uip-background-muted uip-padding-xs uip-border-round uip-overflow-auto uip-h-150">
						<div class="uip-grid">
							<div class="uip-width-medium uip-text-bold uip-margin-bottom-xs"><?php _e("Current", $this->textDomain); ?> {{batchRename.selectedAttribute}}</div>
							<div class="uip-width-medium uip-text-bold uip-margin-bottom-xs"><?php _e("New", $this->textDomain); ?> {{batchRename.selectedAttribute}}</div>
							
							<template v-for="preview in batchRename.preview">
								<div class="uip-width-medium uip-margin-bottom-xs uip-overflow-hidden uip-text-ellipsis uip-no-wrap">{{preview.current}}</div>
								<div class="uip-width-medium uip-margin-bottom-xs uip-overflow-hidden uip-text-ellipsis uip-no-wrap">{{preview.new}}</div>
							</template>
							
						</div>
					</div>
				</div>
			  
			<div class="uip-position-fixed uip-bottom-0  uip-right-0 uip-w-500 uip-padding-m uip-flex uip-flex-between uip-background-muted">
				<div class="uip-flex uip-flex-between uip-w-100p">
					<button class="uip-button-secondary" @click="batchRenamePreview()"> <?php _e("Preview", $this->textDomain); ?> </button>
					<button class="uip-button-primary" @click="batchRenameProcess()"> <?php _e("Rename", $this->textDomain); ?> </button>
				</div>
			</div>
		  </div>
		</uip-offcanvas-nb>
		<?php
  }

  /**
   * Build content page header
   * @since 2.9
   */

  public function build_header()
  {
    ?>
		<div class="uip-margin-bottom-m" style="margin-bottom: 30px;">
			<div class="uip-text-bold uip-text-xxl uip-text-emphasis"><?php _e("Content", $this->textDomain); ?></div>
		</div>
		<div class="uip-margin-bottom-m" v-if="contentTable.views.allViews.length > 0">
				<div class="uip-flex">
					<span :class="{'uip-background-primary uip-text-inverse' : contentTable.views.currentView.length < 1 }"
          class="uip-padding-xs uip-border-round uip-background-muted uip-margin-right-xs hover:uip-background-grey uip-cursor-pointer" 
          @click="resetFilters()">
          <?php _e("All Content", $this->textDomain); ?>
					</span>
          
					<template v-for="view in contentTable.views.allViews">
            <span :class="{'uip-background-primary uip-text-inverse' : contentTable.views.currentView.name == view.name}"
            class="uip-padding-xs uip-border-round uip-background-muted uip-margin-right-xs hover:uip-background-grey uip-cursor-pointer " @click="setView(view)">
              {{view.name}}
              <span class="uip-margin-left-xs">
                <a href="#" class="uip-link-muted uip-no-underline" @click="removeView(view)">x</a>
              </span>
            </span>
					</template>
          
          
				</div>
			
		</div>
		<?php
  }

  /**
   * Build upload modal
   * @since 2.9
   */

  public function build_upload_modal()
  {
    $maxupload = $this->utils->formatBytes(wp_max_upload_size());
    $maxupload = str_replace(" ", "", $maxupload);
    ?>
				
				<div class="uk-padding-medium">
          
          <uip-file-upload :activeFolder="folders.activeFolder[0]" maxUpload="<?php echo $maxupload; ?>"></uip-file-upload>
          
				</div>
				
				
		<?php
  }

  /**
   * Builds quick edit overlay
   * @since 2.9
   */

  public function build_quick_edit_modal()
  {
    ?>
		
		
		<div id="a2020-quick-edit-modal" v-if="ui.quickEdit" style="z-index:99999"
		class="uip-position-fixed uip-right-0 uip-left-0 uip-h-viewport uip-top-0 uip-background-default"> 
			
				
				<div class="uip-flex uip-flex-wrap uip-h-viewport uip-max-h-viewport uip-overflow-auto" style="">
					
					<div class="uip-width-small-medium uip-border-right uip-h-viewport uip-position-relative">
						
						<div class="uip-position-fixed uip-bottom-0  uip-width-small-medium uip-padding-m uip-flex uip-flex-between uip-background-muted">
							<div>
								<button class="uip-button-danger" type="button" @click="deleteItem(quickEdit.id)"><?php _e("Delete", $this->textDomain); ?></button> 
							</div>
							
							<div>
								<button v-if="quickEdit.shortMime == 'image'"
								class="uip-button-secondary uip-margin-right-xs" @click="openImageEdit()"><?php _e("Edit Image", $this->textDomain); ?></button>
								
								<button class="uip-button-primary" type="button" @click="updateItem()"><?php _e("Update", $this->textDomain); ?></button> 
							</div>
									
						</div>
						
						<div class="uip-position-absolute uip-top-0 uip-right-0 uip-padding-m uip-flex uip-flex-right">
							<span @click="ui.quickEdit = false" 
							class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer"
							>close</span>
						</div>
						
						<div class="uip-padding-m uip-h-viewport  uip-overflow-auto" style="max-height:100vh">
							
							<div class="" style="margin-bottom: 40px;">
								<div class="uip-text-bold uip-text-xl uip-text-emphasis uip-margin-bottom-s">{{quickEdit.title}}</div>
								
								<div>
									<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-margin-right-xs">
										{{quickEdit.postType}}
									</span>
									<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold" :class="quickEdit.status">
										{{quickEdit.status}}
									</span>
								</div>
							</div>
							
							
							
							
							<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Details", $this->textDomain); ?></div>
							
							<div class="uip-margin-bottom-s uip-background-muted uip-padding-xs uip-border-round uip-overflow-auto uip-h-150">
								
								<div class="uip-flex uip-margin-bottom-xs">
									<span class="material-icons-outlined uip-margin-right-xxs">person</span>
									<span>{{quickEdit.author}}</span>
								</div>
								
								<div class="uip-flex uip-margin-bottom-xs">
									<span class="material-icons-outlined uip-margin-right-xxs">calendar_today</span>
									<span>{{quickEdit.created}}</span>
								</div>
								
								<div class="uip-flex uip-margin-bottom-xs">
									<span class="material-icons-outlined uip-margin-right-xxs">edit</span>
									<span>{{quickEdit.modified}}</span>
								</div>
								
								<div class="uip-flex uip-margin-bottom-xs">
									<span class="material-icons-outlined uip-margin-right-xxs">link</span>
									<a class="uip-link-default uip-no-underline" :href="quickEdit.url">{{quickEdit.url}}</a>
								</div>
								
								
								
								<template v-if="quickEdit.postType == 'attachment'">
								
									
									<div v-if="quickEdit.shortMime == 'image' || quickEdit.shortMime == 'video'" 
									class="uip-flex uip-margin-bottom-xs">
										<span class="material-icons-outlined uip-margin-right-xxs">photo_size_select_large</span>
										<span>{{quickEdit.dimensions}}</span>
									</div>
									
									<div v-if="quickEdit.shortMime == 'image' || quickEdit.shortMime == 'video'" 
									class="uip-flex uip-margin-bottom-xs">
										<span class="material-icons-outlined uip-margin-right-xxs">description</span>
										<span>{{quickEdit.fileSize}}</span>
									</div>
									
									<div v-if="quickEdit.shortMime == 'image' || quickEdit.shortMime == 'video'" 
									class="uip-flex uip-margin-bottom-xs">
										<span class="material-icons-outlined uip-margin-right-xxs">dns</span>
										<span>{{quickEdit.serverName}}</span>
									</div>
								
								</template>
								
							</div>
							
							
							
							
							
							<div class="uip-margin-bottom-s">
								<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Title", $this->textDomain); ?></div>
								<input type="text" v-model="quickEdit.title"  placeholder="<?php _e("Title", $this->textDomain); ?>">
							</div>
							
							<template v-if="quickEdit.postType == 'attachment'">
									
									<div class="uip-margin-bottom-s">
										<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e("Alt Text", $this->textDomain); ?></div>
										<input class="uk-input" type="text" v-model="quickEdit.alt"  placeholder="<?php _e("Alt", $this->textDomain); ?>">
									</div>
									
									<div class="uip-margin-bottom-s">
										<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Caption", $this->textDomain); ?></div>
										<textarea cols="5" style="height: 75px" v-model="quickEdit.caption"  class="uip-w-100p" placeholder="<?php _e("Caption", $this->textDomain); ?>"></textarea>
									</div>
									
									<div class="uip-margin-bottom-s">
										<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Description", $this->textDomain); ?></div>
										<textarea style="height: 75px" class="uip-w-100p" v-model="quickEdit.description"  placeholder="<?php _e("Description", $this->textDomain); ?>"></textarea>
									</div>
									
									
							</template>
							
							<!-- IMAGE META -->
							<template v-if="quickEdit.shortMime == 'image' || quickEdit.shortMime == 'video' || quickEdit.shortMime == 'audio'" >
								
								<div v-if="quickEdit.photoMeta" class="uip-margin-bottom-s">
									<div class="uip-text-bold uip-margin-bottom-xs"><?php _e("Meta Data", $this->textDomain); ?></div>
								
									<div class="uip-margin-bottom-s uip-background-muted uip-padding-xs uip-border-round uip-overflow-auto uip-h-150">
										
										<template  v-for="(value, name) in quickEdit.photoMeta">
											  <div class="uk-text-meta uk-margin-small-bottom">
												  <span class="uip-margin-right-xxs uk-text-bold">{{ name }}:</span>
												  <span> {{ value }}</span>
											  </div>
										</template>
										
									</div>
								</div>
							
							</template>
							
							<template v-if="quickEdit.postType != 'attachment'">
								<div class="uip-margin-bottom-s">
									<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
										<span class="material-icons-outlined uip-margin-right-xs">check_circle</span>
										<span><?php _e("Status", $this->textDomain); ?></span>
									</div>
									
									<multi-select :options="contentTable.postStatuses" :selected="quickEdit.selectedStatus"
									:single="true"
									:name="'<?php _e("Status", $this->textDomain); ?>'"
									:placeholder="'<?php _e("Search status...", $this->textDomain); ?>'"></multi-select>
								</div>
								
								<div class="uip-margin-bottom-s">
									<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
										<span class="material-icons-outlined uip-margin-right-xs">category</span>
										<span><?php _e("Categories", $this->textDomain); ?></span>
									</div>
									
									
									<multi-select :options="contentTable.categories" :selected="quickEdit.selectedCategories"
									:name="'<?php _e("Categories", $this->textDomain); ?>'"
									:placeholder="'<?php _e("Search categories...", $this->textDomain); ?>'"></multi-select>
								</div>
								
								<div class="uip-margin-bottom-s">
									<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
										<span class="material-icons-outlined uip-margin-right-xs">label</span>
										<span><?php _e("Tags", $this->textDomain); ?></span>
									</div>
									
									<multi-select :options="contentTable.tags" :selected="quickEdit.selectedTags"
									:name="'<?php _e("Tags", $this->textDomain); ?>'"
									:placeholder="'<?php _e("Search tags...", $this->textDomain); ?>'"></multi-select>
								</div>
							</template>
							
							
						</div>
						
					</div>
					<div class="uip-flex-grow">
						
						
						
						<iframe v-if="quickEdit.postType != 'attachment'" :src="quickEdit.url" style="width:100%;height: 100%;min-height:700px;"></iframe>
						
						<div v-if="quickEdit.shortMime == 'image'" class="uip-flex uip-flex-middle uip-flex-center uip-h-viewport" style="max-height: 100vh">
							
							<img class="uk-border-rounded" :src="quickEdit.src" style="max-width:90%;max-height: auto;" >
							
						</div>
						
						<div v-if="quickEdit.shortMime == 'video' || quickEdit.shortMime == 'audio'" 
						class="uk-flex uk-flex-middle uk-flex-center uk-height-viewport uk-overflow-auto" style="max-height: 100vh">
							<video :src="quickEdit.src" controls uk-video="autoplay: false" style="width: 90%"></video>
						</div>
						
						<iframe v-if="quickEdit.pdf" :src="quickEdit.src" style="width:100%;height: 100%;"></iframe>
						
						<div v-if="quickEdit.shortMime != 'video' && quickEdit.shortMime != 'audio' && quickEdit.shortMime != 'image' && quickEdit.postType == 'attachment' && !quickEdit.pdf" 
						class="uk-flex uk-flex-middle uk-flex-center uk-height-viewport uk-overflow-auto" style="max-height: 100vh">
							
							<!-- IS ICON -->
							<span v-if="quickEdit.icontype == 'icon'" class="material-icons-outlined" style="font-size: 135px;">{{quickEdit.icon}}</span>
							
						</div>
					</div>
					
			</div>
			
		</div>
		
		<?php
  }

  /**
   * Build content page header
   * @since 2.9
   */

  public function build_toolbar()
  {
    $utils = new uipress_util();
    $foldersOn = $utils->get_option("folders", "status");
    $foldersDisabledForUser = $utils->valid_for_user($utils->get_option("folders", "disabled-for", true));
    $dontShowfolders = false;

    if ($foldersOn == "true" || $foldersDisabledForUser) {
      $dontShowfolders = true;
    }
    ?>
		
		
		<div class="uip-margin-bottom-m uip-flex uip-flex-wrap uip-flex-start">
			<div class="uip-flex uip-flex-row uip-flex-grow uip-flex-wrap uip-margin-bottom-xs">
				
        <?php if (!$dontShowfolders) { ?>
				<div class="uip-margin-right-xs">
					<span :class="{'uip-text-primary' : contentTable.folderPanel == true}"
					@click="switchFolderPanel()"
					class="material-icons-outlined material-icons-outlined uip-background-muted uip-padding-xs uip-border-round hover:uip-background-grey uip-cursor-pointer">folder</span>
				</div>
        <?php } ?>
				
				<div class="uip-margin-right-xs uip-margin-bottom-xs">
          
            <span 
            @click="ui.filters = true"
            class="material-icons-outlined material-icons-outlined uip-background-muted uip-padding-xs uip-border-round hover:uip-background-grey uip-cursor-pointer">filter_list</span>
          
            <uip-offcanvas-nb v-if="ui.filters" @state-change="ui.filters = getdatafromComp($event)"
            :toggle="ui.filters" title="<?php _e("Filters", $this->textDomain); ?>" type="icon" icon="filter_list" pos="botton-left">
    						<?php $this->build_filters(); ?>
    				</uip-offcanvas-nb>
				</div>
				<div class="uip-margin-right-xs uip-margin-bottom-xs">
					<uip-offcanvas title="<?php _e("Upload", $this->textDomain); ?>" type="icon" icon="file_upload" pos="botton-left">
						<?php $this->build_upload_modal(); ?>
					</uip-offcanvas>
				</div>
				<div class="uip-margin-right-xs uip-margin-bottom-xs">
					<uip-dropdown type="icon" icon="tune" pos="botton-left">
						
						<div class="uip-background-muted uip-border-round uip-padding-xxs uip-margin-bottom-xs uip-flex uip-margin-bottom-s">
							  <button class="uip-button-default uip-w-50p material-icons-outlined"
							  :class="{ 'uip-background-default' : this.contentTable.mode == 'list'}" 
							  @click="this.contentTable.mode = 'list'"><span class="material-icons-outlined">list</span>
							  </button>
							  
							  <button class="uip-button-default uip-w-50p "
							  :class="{ 'uip-background-default' :  this.contentTable.mode == 'grid'}" 
							  @click="this.contentTable.mode = 'grid'"><span class="material-icons-outlined">auto_awesome_mosaic</span>
							  </button>
						</div>
						
						<div class="uip-margin-bottom-s">
							<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
								<span class="material-icons-outlined uip-margin-right-xxs">article</span>
								<span><?php _e("Items Per Page", $this->textDomain); ?></span>
							</div>
							<div >
								<input type="number" min="1" :max="contentTable.total" v-model="contentTable.filters.perPage">
							</div>
						</div>
						
						<div class="uip-margin-bottom-s">
							<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
								<span class="material-icons-outlined uip-margin-right-xxs">auto_awesome_mosaic</span>
								<span><?php _e("Columns", $this->textDomain); ?></span>
							</div>
							<div >
								<input type="range"  min="1" max="6" step="1" v-model="contentTable.gridSize">
							</div>
						</div>
            
          </uip-dropdown>
				</div>
				<div>
					<div class="uip-background-muted uip-padding-xs uip-border-round hover:uip-background-grey uip-flex uip-flex-center">
						<span class="material-icons-outlined uk-form-icon uip-margin-right-xs">search</span>
						<input class="uip-blank-input " placeholder="<?php _e("Search...", $this->textDomain); ?>" v-model="contentTable.filters.search">
					</div>
				</div>
			</div>
			<div >
				<div class="uip-background-primary-wash uip-border-round uip-padding-xs uip-text-bold uip-flex" >
					<span class="uip-margin-right-xxs">{{fileList.length}} </span>
					<span class="uip-margin-right-xxs" v-if="contentTable.total > fileList.length">
						<?php _e("of", $this->textDomain); ?> 
						{{contentTable.total}}
					</span>
					<span><?php _e("items", $this->textDomain); ?> </span>
				</div>
			</div>
			<div v-if="fileList.length < contentTable.total">
						
						<?php $this->build_pagination(); ?>
					
			</div>
		</div>
		
		<?php
  }

  /**
   * Build table filters
   * @since 2.9
   */
  public function build_filters()
  {
    ?>
		<!--POST TYPE FILTERS -->
		<div class="uip-margin-bottom-s">
			<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
				<span class="material-icons-outlined uip-margin-right-xs">library_books</span>
				<span><?php _e("Post Types", $this->textDomain); ?></span>
			</div>
			<multi-select :options="contentTable.postTypes" :selected="contentTable.filters.selectedPostTypes"
			:name="'<?php _e("Post Types", $this->textDomain); ?>'"
			:placeholder="'<?php _e("Search Post Types...", $this->textDomain); ?>'"></multi-select>
		</div>
		
		<!--POST TYPE FILTERS -->
		<div class="uip-margin-bottom-s">
			<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
				<span class="material-icons-outlined uip-margin-right-xs">check_circle</span>
				<span><?php _e("Post Status", $this->textDomain); ?></span>
			</div>
			<multi-select :options="contentTable.postStatuses" :selected="contentTable.filters.selectedPostStatuses"
			:name="'<?php _e("Post Status", $this->textDomain); ?>'"
			:placeholder="'<?php _e("Search Post Statuses...", $this->textDomain); ?>'"></multi-select>
		</div>
		<!--DATE FILTERS -->
		<div class="uip-margin-bottom-s">
			
			<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
				<span class="material-icons-outlined uip-margin-right-xs">date_range</span>
				<span><?php _e("Date Created", $this->textDomain); ?></span>
			</div>
			
			<div class="uip-flex">
				<div class="uip-margin-right-s">
					<select  v-model="contentTable.filters.dateComparison">
						<option value="on" selected><?php _e("Posted On", $this->textDomain); ?>:</option>
						<option value="after"><?php _e("Posted After", $this->textDomain); ?>:</option>
						<option value="before"><?php _e("Posted Before", $this->textDomain); ?>:</option>
					</select>
				</div>
				<div >
					<input  type="date" v-model="contentTable.filters.date"> 
				</div>
			</div>
			
		</div>
		
		<!--MEDIA FILTERS -->
		<div class="uip-margin-bottom-s">
			
			<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
				<span class="material-icons-outlined uip-margin-right-xs">perm_media</span>
				<span><?php _e("Media Types", $this->textDomain); ?></span>
			</div>
			<multi-select :options="contentTable.fileTypes" :selected="contentTable.filters.selectedFileTypes"
			:name="'<?php _e("Media Types", $this->textDomain); ?>'"
			:placeholder="'<?php _e("Search Media Types...", $this->textDomain); ?>'"></multi-select>
		</div>
		
		<!--CATEGORIES -->
		<div class="uip-margin-bottom-s">
			
			<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
				<span class="material-icons-outlined uip-margin-right-xs">category</span>
				<span><?php _e("Categories", $this->textDomain); ?></span>
			</div>
			<multi-select :options="contentTable.categories" :selected="contentTable.filters.selectedCategories"
			:name="'<?php _e("Categories", $this->textDomain); ?>'"
			:placeholder="'<?php _e("Search categories...", $this->textDomain); ?>'"></multi-select>
		</div>
		
		<!--TAGS -->
		<div class="uip-margin-bottom-s">
			
			<div class="uip-text-bold uip-margin-bottom-xs uip-flex">
				<span class="material-icons-outlined uip-margin-right-xs">label</span>
				<span><?php _e("Tags", $this->textDomain); ?></span>
			</div>
			<multi-select :options="contentTable.tags" :selected="contentTable.filters.selectedTags"
			:name="'<?php _e("Tags", $this->textDomain); ?>'"
			:placeholder="'<?php _e("Search tags...", $this->textDomain); ?>'"></multi-select>
		</div>
		
		<div class="uip-position-fixed uip-bottom-0  uip-right-0 uip-w-500 uip-padding-m uip-flex uip-flex-between uip-background-muted">
			<div class="uip-flex uip-flex-between uip-w-100p">
				<button class="uip-button-danger" @click="resetFilters()"><?php _e("Clear Filters", $this->textDomain); ?></button>
				<button class="uip-button-primary" @click="nameNewView()"><?php _e("Save as view", $this->textDomain); ?></button>
			</div>
		</div>
		
		<?php
  }

  /**
   * Outputs active filters
   * @since 2.9
   */
  public function active_filters()
  {
    ?>
		<!--- ACTIVE FILTERS -->
		<div class="uip-margin-bottom-s" uk-grid v-if="totalFilters()">
			
				<div class="uip-flex uip-flex-center">
					
					
						<div class="uip-margin-right-s">
							<span class="material-icons uip-text-xl uip-text-muted">sell</span>
						</div>
					
					
						<div class="uip-margin-right-xs" v-for="status in contentTable.filters.selectedPostStatuses">
							
							<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
								{{status}}
								<a class="uip-link-muted uip-no-underline" @click="removeFromList(status,contentTable.filters.selectedPostStatuses)" href="#">x</a>
							</div>
							
						</div>
						
						<div class="uip-margin-right-xs" v-for="status in contentTable.filters.selectedPostTypes">
							
							<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
								{{status}}
								<a class="uip-link-muted uip-no-underline" @click="removeFromList(status,contentTable.filters.selectedPostTypes)" href="#">x</a>
							</div>
							
						</div>
						
						<div class="uip-margin-right-xs"  v-if="contentTable.filters.date != ''">
							
							<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
								<?php _e("Posted", $this->textDomain); ?>
								{{contentTable.filters.dateComparison}}
								{{contentTable.filters.date}}
								<a class="uip-link-muted uip-no-underline" @click="contentTable.filters.date = ''" href="#">x</a>
							</div>
							
						</div>
						
						<div class="uip-margin-right-xs"  v-for="status in contentTable.filters.selectedFileTypes">
							
							<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
								{{status}}
								<a class="uip-link-muted uip-no-underline" @click="removeFromList(status,contentTable.filters.selectedFileTypes)" href="#">x</a>
							</div>
							
						</div>
						
						<div  class="uip-margin-right-xs"  v-for="cat in contentTable.filters.selectedCategories">
							
							<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
								<template v-for="fullCat in contentTable.categories">
								
								<span v-if="fullCat.name == cat">
									{{fullCat.label}}
									<a class="uip-link-muted uip-no-underline" @click="removeFromList(status,contentTable.filters.selectedCategories)" href="#">x</a>
								</span>
								
								</template>
							</div>
							
						</div>
						
						<div class="uip-margin-right-xs"  v-for="cat in contentTable.filters.selectedCategories">
							
							<div >
								<template v-for="fullCat in contentTable.tags">
								
								<span v-if="fullCat.name == cat">
									
									<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
									{{fullCat.label}}
									<a class="uip-link-muted uip-no-underline" @click="removeFromList(status,contentTable.filters.selectedCategories)" href="#">x</a>
									</div>
									
								</span>
								
								</template>
							</div>
							
						</div>
						
						<div class="uip-margin-right-xs"  v-for="cat in contentTable.filters.selectedTags">
							
							<div >
								<template v-for="fullCat in contentTable.tags">
								
								<span v-if="fullCat.name == cat">
									
									<div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">
									{{fullCat.label}}
									<a class="uip-link-muted uip-no-underline" @click="removeFromList(status,contentTable.filters.selectedTags)" href="#">x</a>
									</div>
									
								</span>
								
								</template>
							</div>
							
						</div>
					
				</div>
				
		</div>
		<?php
  }

  /**
   * Build table pagination
   * @since 2.9
   */
  public function build_pagination()
  {
    ?>
		<!--- PAGINATION  -->
		<div class="uip-padding-xxs uip-background-muted uip-border-round uip-flex uip-margin-left-s uip-text-normal" >
			
			<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs material-icons-outlined"
			:class="{'uk-disabled' : contentTable.currentPage == 1}" @click="contentTable.currentPage = contentTable.currentPage - 1">
				<span class="material-icons-outlined">chevron_left</span>
			</div>
			<!-- IF PAGES LESS THAN 6 THEN SHOW ALL -->
			<div v-if="contentTable.totalPages < 6" v-for="n in contentTable.totalPages" >
				<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold"
				:class="{'uip-text-primary' : contentTable.currentPage == n}" @click="contentTable.currentPage = n">{{n}}</div>
			</div>
			
			
			<!-- IF PAGES MORE THAN 5 THEN SHOW FIRST PAGE -->
			<div v-if="contentTable.totalPages > 5" >
				<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold"
				:class="{'uip-text-primary' : contentTable.currentPage == 1}" @click="contentTable.currentPage = 1">1</div>
			</div>
			
			
			<!-- IF CURRENT PAGE IS MORE THAN 4 THEN PAGES BETWEEN 1 AND CURRENT PAGE -->
			<div v-if="contentTable.totalPages > 5 && (contentTable.currentPage - 2) > 1" >
				
				<uip-dropdown type="icon" icon="more_horiz" pos="botton-left" size="small">
					
					<div class="">
						<div v-for="n in (contentTable.currentPage - 3)"
						class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold uip-margin-right-xs uip-margin-bottom-xs uip-display-inline-block"
						:class="{'uip-text-primary' : contentTable.currentPage == n}" @click="contentTable.currentPage = n + 1">{{n + 1}}</div>
					</div>
					
				</uip-dropdown>
				
			</div>
			
			<!-- MIDDLE: CURRENT PAGE, ONE BEFORE AND ONE AFTER -->
			<div v-if="contentTable.totalPages > 5 && contentTable.currentPage - 1 > 1">
				<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold"
				@click="contentTable.currentPage = contentTable.currentPage - 1">{{contentTable.currentPage - 1}}</div>
			</div>
			<div v-if="contentTable.totalPages > 5 && contentTable.currentPage != 1 && contentTable.currentPage != contentTable.totalPages">
				<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold uip-text-primary">
					{{contentTable.currentPage}}
				</div>
			</div>
			<div v-if="contentTable.totalPages > 5 && contentTable.currentPage + 1 != contentTable.totalPages">
				<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold"
				@click="contentTable.currentPage = contentTable.currentPage + 1">{{contentTable.currentPage + 1}}</div>
			</div>
			
			<!-- IF CURRENT PAGE IS MORE THAN TOTAL PAGES MINUS  THEN PAGES BETWEEN 1 AND CURRENT PAGE -->
			<div v-if="contentTable.totalPages > 5 && (contentTable.currentPage + 2) < contentTable.totalPages" >
				
				<uip-dropdown type="icon" icon="more_horiz" pos="botton-left" size="small">
					
					<div class="">
						<div v-for="n in (contentTable.totalPages - contentTable.currentPage - 2) "
						class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold uip-margin-right-xs uip-margin-bottom-xs uip-display-inline-block"
						:class="{'uip-text-primary' : contentTable.currentPage == n}" @click="contentTable.currentPage = n + 1 + contentTable.currentPage">
						{{n + 1 + contentTable.currentPage}}</div>
					</div>
					
				</uip-dropdown>
				
			</div>
			
			<!-- IF PAGES MORE THAN 5 THEN SHOW LAST PAGE -->
			<div v-if="contentTable.totalPages > 5" >
				<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs uip-text-bold"
				:class="{'uip-text-primary' : contentTable.currentPage == contentTable.totalPages}" 
				@click="contentTable.currentPage = 1">{{contentTable.totalPages}}</div>
			</div>
			
			
			<div class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xxs material-icons-outlined"
			:class="{'uk-disabled' : contentTable.currentPage == contentTable.totalPages}" @click="contentTable.currentPage = contentTable.currentPage + 1">
				<span class="material-icons-outlined">chevron_right</span>
			</div>
		</div>
		
		<?php
  }

  /**
   * Build content page header
   * @since 2.9
   */

  public function build_table()
  {
    ?>
		<!-- TABLE HEAD -->
		<div v-if="contentTable.mode == 'list'" class="uip-flex uip-flex-column uip-flex-wrap uip-max-w-100p">
			<div class="uip-padding-s uip-border-round uip-background-muted uip-margin-bottom-s">
				
				<div class="uip-flex uip-flex-center">
					
					<div class="uip-w-50 uip-flex-no-shrink" >
						<input type="checkbox" v-model="contentTable.selectAll" @click="selectAllTable">
					</div>
					
					
					<div  class="uip-flex-grow uip-text-bold uip-margin-left-xs" >
						<?php _e("Name", $this->textDomain); ?>
					</div>
					
					<div class="uip-w-10p uip-flex-no-shrink uip-text-bold uip-margin-left-xs" >
						<?php _e("Type", $this->textDomain); ?>
					</div>
					
					<div  class="uip-w-10p uip-flex-no-shrink uip-text-bold uip-margin-left-xs" >
						<?php _e("Author", $this->textDomain); ?>
					</div>
					
					<div  class="uip-w-10p uip-flex-no-shrink uip-text-bold uip-margin-left-xs" >
						<?php _e("Date", $this->textDomain); ?>
					</div>
					
					<div  class="uip-w-10p uip-flex-no-shrink uip-text-bold uip-margin-left-xs" >
						<?php _e("Status", $this->textDomain); ?>
					</div>
					
					<div class="uip-w-50 uip-flex-no-shrink" >
					</div>
					
					
				</div>
				
			</div>
			
			
			<!-- TABLE CONTENT -->
			<template  v-for="item in fileList">
				
				<div class="a2020-table-item uk-padding-small a2020-border bottom " draggable="true"  
				@dragstart="startContentDrag($event,item)"
				@dragend="endContentDrag($event,item)"
				@dblclick="openQuickEdit(item.id)"
				>
					
						<div class="uip-flex uip-padding-s hover:uip-background-primary-wwash uip-border-round">
							
							<div class="uip-w-50 uip-flex-no-shrink uip-flex uip-flex-center" >
								<input type="checkbox" v-model="contentTable.selected" :value="item.id">
							</div>
							
							<div class="uip-flex-grow uip-text-bold uip-margin-left-xs" >
								<div class="uip-flex">
									<!-- IS ICON -->
									<div v-if="item.icontype == 'icon'" class="uip-margin-right-s">
										<div class="uip-background-muted uip-border-round uip-h-32 uip-w-32 uip-padding-xxs uip-flex uip-flex-middle uip-flex-center">
											<span class="material-icons-outlined uip-text-l">{{item.icon}}</span>
										</div>
									</div>
									<!-- HAS IMAGE -->
									<div v-if="item.icontype == 'image'" class="uip-margin-right-s">
										<img class="uip-background-muted uip-border-round uip-h-40 uip-w-40" style="height:40px"
										:src="item.icon">
									</div>
									<div class="uip-flex-grow">
										<div class="uip-text-bold uip-overflow-hidden uip-text-ellipsis uip-no-wrap">{{item.name}}</div>
									</div>
								</div>
							</div>
							
							<div class="uip-w-10p uip-flex-no-shrink uip-text-bold uip-margin-left-xs" >
								<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">{{item.type}}</span>
							</div>
							
							<div class="uip-w-10p  uip-margin-left-xs" >
								<a :href="item.authorLink" class="uip-link-default uip-no-underline">
								{{item.author}}
								</a>
							</div>
							
							<div class="uip-w-10p uip-flex-no-shrink uip-margin-left-xs uip-text-muted" >
								{{item.date}}
							</div>
							
							<div class="uip-w-10p uip-flex-no-shrink uip-margin-left-xs" >
								<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold" :class="item.status">{{item.status}}</span>
							</div>
							
							<div class="uip-w-50 uip-flex-no-shrink uip-flex uip-flex-right" >
								
								<uip-dropdown type="icon" icon="more_horiz" pos="botton-left">
									
									<?php $this->get_item_options(); ?>
									
								</uip-dropdown>
								
							</div>
							
						</div>
					
				</div>
				
			</template>
		
		</div>
		
		
		<!-- TABLE CONTENT -->
		<template v-if="contentTable.mode == 'grid'">
			
			<div class="uip-masonry" :style="{'column-count' : contentTable.gridSize}">
				<template  v-for="item in fileList">
					 
					<div  class="uip-margin-bottom-m">
						
						<div class="uip-card uip-position-relative" draggable="true"  
						@dragstart="startContentDrag($event,item)"
						@dragend="endContentDrag($event,item)"
						@dblclick="openQuickEdit(item.id)">
							
							
							<!-- IS ICON -->	
							<div v-if="item.icontype == 'icon'" >
								<div class="uip-flex uip-flex-center uip-flex-middle uip-h-200">
									<span class="material-icons-outlined" style="font-size: 50px;">{{item.icon}}</span>
								</div>
							</div>
							<!-- HAS IMAGE -->
							<div v-if="item.icontype == 'image'">
								<img 
								:src="item.iconLarge" style="width:100%">
							</div>
							
							<div class="uip-position-absolute uip-right-0 uip-top-0 uip-padding-s">
								<uip-dropdown type="icon" icon="more_horiz" pos="botton-left" size="small">
								<?php $this->get_item_options(); ?>
								</uip-dropdown>
							</div>
							
							
							<div class="uip-padding-s">
								<div class="uip-margin-bottom-xxs">
									<span class="uip-margin-right-xs"><input type="checkbox" v-model="contentTable.selected" :value="item.id"></span>
									<span class="uip-text-bold">{{item.name}}</span>
								</div>
								<div class="uip-text-muted uip-margin-bottom-m">{{item.author}} | {{item.date}}</div>
								<div >
									<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-margin-right-xxs" 
									v-if="item.type == 'attachment'">{{item.mime}}</span>
									<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-margin-right-xxs" 
									v-if="item.type != 'attachment'">{{item.type}}</span>
									<span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold" 
									:class="item.status">{{item.status}}</span>
								</div>
							</div>
						</div>
						
					</div>
					
				</template>
				
				
			</div>
			
			<div v-if="contentTable.totalPages > contentTable.currentPage && contentTable.content.length < contentTable.total">
		
				<div class="uk-width-1-1 uk-flex uk-flex-center uk-margin-top">
					<button class="uip-button-secondary" 
					@click="contentTable.filters.perPage = Math.round(contentTable.filters.perPage * 1.5)"><?php _e("Load More", $this->textDomain); ?></button>
				</div>
			
			</div>
			
		</template>
		
		<div v-if="fileList.length == 0" class="uip-text-center uip-padding-l">
			<div class=""><span class="material-icons-outlined">sentiment_dissatisfied</span></div>
			<p class="uk-h4 uk-text-meta uk-margin-remove-top"><?php _e("No content found", $this->textDomain); ?></p>
		</div>
		
		<div class="admin2020loaderwrap" id="admincontentloader" v-if="loading === true">
			<div class="admin2020loader"></div>
		</div>
		
		<?php
  }

  public function get_item_options()
  {
    ?>
	  
	  <div class="uk-nav uk-dropdown-nav">
		  <div >
			  <a :href="item.url" target="_BLANK" 
			  class="uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-no-underline uip-flex">
				  <span class="material-icons-outlined uip-margin-right-xxs">pageview</span>
				  <span class="uip-text-bold"><?php _e("View", $this->textDomain); ?></span>
			  </a>
		  </div>
		  <div >
			  <a :href="item.editurl" target="_BLANK" 
			  class="uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-no-underline uip-flex">
				  <span class="material-icons-outlined uip-margin-right-xxs">edit</span>
				  <span class="uip-text-bold"><?php _e("Edit", $this->textDomain); ?></span>
			  </a>
		  </div>
		  <div >
			  <a href="#" @click="openQuickEdit(item.id)" 
			  class="uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-no-underline uip-flex">
				  <span class="material-icons-outlined uip-margin-right-xxs">preview</span>
				  <span class="uip-text-bold"><?php _e("Quick Edit", $this->textDomain); ?></span>
			  </a>
		  </div>
		  <div v-if="item.type != 'attachment'" >
			  <a href="#" @click="duplicateItem(item.id)" 
			  class="uip-link-default uip-padding-xs hover:uip-background-muted uip-border-round uip-no-underline uip-flex">
				  <span class="material-icons-outlined uip-margin-right-xxs">copy</span>
				  <span class="uip-text-bold"><?php _e("Duplicate", $this->textDomain); ?></span>
			  </a>
		  </div>
		  <div class="uip-margin-bottom-s">
		  </div>
		  <div >
			  <div @click="deleteItem(item.id)" class="uip-button-danger uip-flex">
				  <span class="material-icons-outlined uip-margin-right-xxs">delete</span>
				  <span class="uip-text-bold"><?php _e("Delete Item", $this->textDomain); ?></span>
			  </div>
		  </div>
	  </div>
	  
	  <?php
  }

  /**
   * Builds batch options for items
   * @since 2.9
   */

  public function build_batch_options()
  {
    ?>
		
		<div class="uip-position-fixed uip-bottom-0 uip-right-0 uip-padding-m" v-if="contentTable.selected.length > 0">
			
			
			<uip-dropdown :translation="contentTable.selected.length + ' ' + '<?php _e("items selected", $this->textDomain); ?>'" 
				type="button" icon="tune" pos="top-left" size="large" :primary='true'>
				
				<div>
					<div >
						<div class="uip-padding-xs hover:uip-background-muted uip-border-round uip-flex uip-cursor-pointer" @click="openBatchRename()">
							<span class="material-icons-outlined uip-margin-right-xs">drive_file_rename_outline</span>
							<span><?php _e("Batch Rename", $this->textDomain); ?></span>
						</div>
					</div>
					<div >
						<div class="uip-padding-xs hover:uip-background-muted uip-border-round uip-flex uip-cursor-pointer" @click="openCatsTags()">
							<span class="material-icons-outlined uip-margin-right-xs">category</span>
							<span><?php _e("Categories & Tags", $this->textDomain); ?></span>
						</div>
					</div>
					<div>
						<div class="uip-padding-xs hover:uip-background-muted uip-border-round uip-flex uip-cursor-pointer" @click="deleteMultiple()">
							<span class="material-icons-outlined uip-margin-right-xs">delete</span>
							<span><?php _e("Delete Selected", $this->textDomain); ?></span>
						</div>
					</div>
					<div class="uip-margin-top-s">
						<div class="uip-button-danger uip-flex" @click="contentTable.selected = []">
							<span class="material-icons-outlined uip-margin-right-xs">backspace</span>
							<span><?php _e("Clear Selection", $this->textDomain); ?></span>
						</div>
					</div>
				</div>
				
			</uip-dropdown>
			
			
		</div>
		
		<?php
  }

  /**
   * Saves New View
   * @since 2.9
   */
  public function save_view_options()
  {
    ?>
    
    <uip-offcanvas-nb v-if="ui.newView" @state-change="ui.newView = getdatafromComp($event)"
    :toggle="ui.newView" title="<?php _e("Create New View", $this->textDomain); ?>" type="icon" icon="filter_list" pos="botton-left">

		
				<div class="uip-margin-bottom-s">
					<div class="uip-text-bold uip-margin-bottom-xs">
						 <?php _e("View name", $this->textDomain); ?>
					 </div>
					<div>
						<input  v-model="newView.name" type="text" placeholder="<?php _e("View Name", $this->textDomain); ?>"> 
					</div>
					
				</div>
				
        
        <div class="uip-position-fixed uip-bottom-0  uip-right-0 uip-w-500 uip-padding-m uip-flex uip-flex-between uip-background-muted">
          <div class="uip-flex uip-flex-right uip-w-100p">
            <button class="uip-button-primary" @click="saveView()"><?php _e("Save", $this->textDomain); ?></button>
          </div>
        </div>
			
		</uip-offcanvas-nb>
		
		<?php
  }
}
