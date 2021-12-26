<?php
if (!defined("ABSPATH")) {
  exit();
}

class uipress_folders
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

  public function ajax()
  {
    add_filter("wp_prepare_attachment_for_js", [$this, "pull_meta_to_attachments"], 10, 3);
    add_filter("ajax_query_attachments_args", [$this, "legacy_media_filter"]);
    add_action("wp_ajax_uip_get_folders", [$this, "uip_get_folders"]);
    add_action("wp_ajax_uip_create_folder", [$this, "uip_create_folder"]);
    add_action("wp_ajax_uip_delete_folder", [$this, "uip_delete_folder"]);
    add_action("wp_ajax_uip_update_folder", [$this, "uip_update_folder"]);
    add_action("wp_ajax_uip_move_folder", [$this, "uip_move_folder"]);
    add_action("wp_ajax_uip_move_content_to_folder", [$this, "uip_move_content_to_folder"]);
    add_action("wp_ajax_uip_remove_from_folder", [$this, "uip_remove_from_folder"]);
    add_filter("uipress_register_settings", [$this, "folder_settings_options"], 1, 2);
  }

  /**
   * Adds folder id to default wp media views
   * @since 1.4
   */
  public function pull_meta_to_attachments($response, $attachment, $meta)
  {
    $response["imageID"] = $attachment->ID;
    $response["properties"]["imageID"] = $attachment->ID;

    $folderid = get_post_meta($attachment->ID, "admin2020_folder", true);

    $response["folder"] = $folderid;
    $response["properties"]["folder"] = $folderid;
    return $response;
  }

  /**
   * Returns settings options for settings page
   * @since 2.2
   */
  public function folder_settings_options($settings, $network)
  {
    $utils = new uipress_util();

    ///////FOLDER OPTIONS
    $moduleName = "folders";
    $category = [];
    $options = [];
    //
    $category["module_name"] = $moduleName;
    $category["label"] = __("Folders", $this->textDomain);
    $category["description"] = __("Creates media folder system.", $this->textDomain);
    $category["icon"] = "folder";

    $temp = [];
    $temp["name"] = __("Disable Folders?", $this->textDomain);
    $temp["description"] = __("No media folders will be disaplyed when this option is activated.", $this->textDomain);
    $temp["type"] = "switch";
    $temp["optionName"] = "status";
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"]);
    $options[$temp["optionName"]] = $temp;

    $temp = [];
    $temp["name"] = __("Folders Disabled For", $this->textDomain);
    $temp["description"] = __("UiPress folders will be disabled for any users or roles you select", $this->textDomain);
    $temp["type"] = "user-role-select";
    $temp["optionName"] = "disabled-for";
    $temp["premium"] = true;
    $temp["value"] = $utils->get_option($moduleName, $temp["optionName"], true);
    $options[$temp["optionName"]] = $temp;

    $category["options"] = $options;
    $settings[$moduleName] = $category;

    return $settings;
  }
  /**
   * Builds media template
   * @since 1.4
   */
  public function build_media_template()
  {
    $debug = new uipress_debug();
    $status = $debug->check_network_connection();

    if (!$status) {
      return;
    }
    ?>
    
    <!-- BUILD FOLDERS IN MODAL -->
      <script type="text/html" id="tmpl-media-frame_custom"> 
        
        <div class="uip-flex uip-flex-wrap uip-h-100p uip-text-normal">
          <div class="uip-w-300 uip-body-font uip-position-relative uip-padding-s uip-margin-top-s" id="uip-folder-app" style="font-size:14px;">
          </div>
        
          <div class="uip-flex-grow uip-position-relative">
        
            <div class="media-frame-title" id="media-frame-title"></div>
            <h2 class="media-frame-menu-heading"><?php _ex("Actions", "media modal menu actions"); ?></h2>
            <button type="button" class="button button-link media-frame-menu-toggle" aria-expanded="false">
              <?php _ex("Menu", "media modal menu"); ?>
              <span class="dashicons dashicons-arrow-down" aria-hidden="true"></span>
            </button>
            <div class="media-frame-menu"></div>
        
            <div class="media-frame-tab-panel">
              <div class="media-frame-router"></div>
              <div class="media-frame-content"></div>
            </div>
          </div>
        
        </div>
        
        <div class="media-frame-toolbar"></div>
        <div class="media-frame-uploader"></div>
        <script id="uip-folder-script" src="<?php echo $this->pathURL . "assets/js/uip-folders.min.js"; ?>"></script>
      </script>
      
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          
      
          if( typeof wp.media.view.Attachment != 'undefined' ){
            wp.media.view.MediaFrame.prototype.template = wp.media.template( 'media-frame_custom' );
            
            wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend({
              attributes:  function () { return {draggable: "true", 'data-id':  this.model.get( 'imageID' ), 'folder-id':  this.model.get( 'folder' )} },
              //folderName: function () { return 'attachment ' + this.model.get( 'imageID' ); },
              //attr: 'blue',
              });
      
            wp.media.view.Modal.prototype.on('open', function() {
              
              
            });
      
      
          }
        });
        
        
        
      </script>
      
        <?php
  }

  /**
   * Outputs the folder system on the uipress content page
   * @since 2.2
   */
  public function output_for_content()
  {
    $utils = new uipress_util();
    $foldersOn = $utils->get_option("folders", "status");
    $foldersDisabledForUser = $utils->valid_for_user($utils->get_option("folders", "disabled-for", true));

    if ($foldersOn == "true" || $foldersDisabledForUser) {
      return;
    }
    ?>
    <div class="uip-position-relative" id="uip-folder-app" ></div>
    <?php wp_enqueue_script("uip-folder-scripte", $this->pathURL . "assets/js/uip-folders.min.js", ["uip-app"], $this->version, true);
  }

  /**
   * Filters media by folder
   * @since 1.4
   */
  public function legacy_media_filter($args)
  {
    if (isset($_REQUEST["query"]["folder_id"])) {
      $folderid = $_REQUEST["query"]["folder_id"];

      error_log($folderid);

      if ($folderid == "" || $folderid == "all") {
      } elseif ($folderid == "nofolder") {
        $args["meta_query"] = [
          "relation" => "OR",
          [
            "key" => "admin2020_folder",
            "compare" => "NOT EXISTS",
          ],
          [
            "key" => "admin2020_folder",
            "value" => "",
            "compare" => "=",
          ],
        ];
      } else {
        $args["meta_query"] = [
          [
            "key" => "admin2020_folder",
            "value" => $folderid,
            "compare" => "=",
          ],
        ];
      }
    }

    return $args;
  }

  /**
   * Deletes folder from media panel
   * @since 2.2
   */
  public function uip_delete_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $folderID = $utils->clean_ajax_input($_POST["activeFolder"]);

      if (!is_numeric($folderID) && !$folderID > 0) {
        $returndata["error"] = __("No folder to delete", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $currentParent = get_post_meta($folderID, "parent_folder", true);

      $status = wp_delete_post($folderID);

      if (!$status) {
        $returndata["error"] = __("Unable to delete the folder", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $args = [
        "numberposts" => -1,
        "post_type" => "admin2020folders",
        "orderby" => "title",
        "order" => "ASC",
        "meta_query" => [
          [
            "key" => "parent_folder",
            "value" => $folderID,
            "compare" => "=",
          ],
        ],
      ];

      $folders = get_posts($args);

      foreach ($folders as $folder) {
        if ($currentParent) {
          update_post_meta($folder->ID, "parent_folder", $currentParent);
        } else {
          delete_post_meta($folder->ID, "parent_folder");
        }
      }

      ///FIND CONTENT WITH DELETED FOLDER
      $args = [
        "post_type" => "attachment",
        "fields" => "ids",
        "posts_per_page" => -1,
        "post_status" => "any",
        "meta_query" => [
          [
            "key" => "admin2020_folder",
            "value" => $folderID,
            "compare" => "=",
          ],
        ],
      ];

      $query = new WP_Query($args);
      $contentWithFolder = $query->get_posts();

      foreach ($contentWithFolder as $item) {
        delete_post_meta($item, "admin2020_folder");
      }

      $returndata["message"] = __("Folder deleted", $this->textDomain);
      echo json_encode($returndata);
      die();
    }
    die();
  }

  /**
   * Moves folder from media panel
   * @since 2.2
   */
  public function uip_move_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $folderToMove = $utils->clean_ajax_input($_POST["folderiD"]);
      $destination = $utils->clean_ajax_input($_POST["destinationId"]);

      if ($folderToMove == $destination) {
        $returndata["error"] = __("Unable to move folder into itself", "admin2020");
        echo json_encode($returndata);
        die();
      }

      $currentParent = get_post_meta($folderToMove, "parent_folder", true);

      if ($destination == "toplevel") {
        $status = delete_post_meta($folderToMove, "parent_folder");
      } else {
        $status = update_post_meta($folderToMove, "parent_folder", $destination);
      }

      if ($status != true) {
        $returndata["error"] = __("Unable to move folder", "admin2020");
        echo json_encode($returndata);
        die();
      }

      ///CHECK IF WE NEED TO MAKE SUB FOLDERS TOP LEVEL
      if (!$currentParent || $currentParent == "") {
        $args = [
          "numberposts" => -1,
          "post_type" => "admin2020folders",
          "orderby" => "title",
          "order" => "ASC",
          "meta_query" => [
            [
              "key" => "parent_folder",
              "value" => $folderToMove,
              "compare" => "=",
            ],
          ],
        ];

        $folders = get_posts($args);

        foreach ($folders as $folder) {
          delete_post_meta($folder->ID, "parent_folder");
        }
      }

      $returndata["message"] = __("Folder moved", "admin2020");
      echo json_encode($returndata);
      die();
    }
    die();
  }

  /**
   * Removes content and folders from folders
   * @since 2.2
   */
  public function uip_remove_from_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $contentIds = $utils->clean_ajax_input($_POST["items"]);
      $type = $utils->clean_ajax_input($_POST["itemtype"]);

      if (!is_array($contentIds)) {
        $returndata["error"] = __("No content to move", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      foreach ($contentIds as $contentId) {
        if ($type == "content") {
          $status = delete_post_meta($contentId, "admin2020_folder");
        }
        if ($type == "folder") {
          $status = delete_post_meta($contentId, "parent_folder");
        }
      }

      $returndata["message"] = __("Content moved", $this->textDomain);
      echo json_encode($returndata);
      die();
    }
    die();
  }
  /**
   * Moves content to folder from media panel
   * @since 2.2
   */
  public function uip_move_content_to_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $contentIds = $utils->clean_ajax_input($_POST["contentID"]);
      $destination = $utils->clean_ajax_input($_POST["destinationId"]);

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
   * Updates folder from media panel
   * @since 2.2
   */
  public function uip_update_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $folder = $utils->clean_ajax_input($_POST["folderInfo"]);

      $foldername = $folder["title"];
      $folderid = $folder["id"];
      $foldertag = $folder["color"];

      $my_post = [
        "post_title" => $foldername,
        "post_status" => "publish",
        "ID" => $folderid,
      ];

      // Insert the post into the database.
      $thefolder = wp_update_post($my_post);

      if (!$thefolder) {
        $returndata = [];
        $returndata["error"] = __("Something went wrong", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      update_post_meta($folderid, "color_tag", $foldertag);

      $returndata = [];
      $returndata["message"] = __("Folder updated", $this->textDomain);
      echo json_encode($returndata);
    }
    die();
  }

  /**
   * Creates folder from media panel
   * @since 2.2
   */
  public function uip_create_folder()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $folderInfo = $utils->clean_ajax_input($_POST["folderInfo"]);
      $parent = $utils->clean_ajax_input($_POST["parent"]);

      $name = $folderInfo["name"];
      $color = $folderInfo["color"];

      if (!$name) {
        $returndata["error"] = __("Title is required", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      if (!$color) {
        $returndata["error"] = __("Colour is required", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      $my_post = [
        "post_title" => $name,
        "post_status" => "publish",
        "post_type" => "admin2020folders",
      ];

      // Insert the post into the database.
      $thefolder = wp_insert_post($my_post);

      if (!$thefolder) {
        $returndata["error"] = __("Unable to create folder", $this->textDomain);
        echo json_encode($returndata);
        die();
      }

      update_post_meta($thefolder, "color_tag", $color);

      if (is_numeric($parent) && $parent > 0) {
        update_post_meta($thefolder, "parent_folder", $parent);
      }

      $returndata["message"] = __("Folder created", $this->textDomain);
      echo json_encode($returndata);
      die();
    }
    die();
  }

  /**
   * Build content for front end folders
   * @since 2.2
   */

  public function uip_get_folders()
  {
    if (defined("DOING_AJAX") && DOING_AJAX && check_ajax_referer("uip-security-nonce", "security") > 0) {
      $utils = new uipress_util();
      $contentPage = $utils->clean_ajax_input($_POST["contentPage"]);
      $privatemode = $utils->get_option("content", "private-mode");

      $args = [
        "numberposts" => -1,
        "post_type" => "admin2020folders",
        "orderby" => "title",
        "order" => "ASC",
      ];

      if ($privatemode == "true") {
        $args["author"] = get_current_user_id();
      }

      $folders = get_posts($args);
      $structure = [];
      $folderIDS = [];

      foreach ($folders as $folder) {
        array_push($folderIDS, $folder->ID);
      }

      if ($contentPage != "true") {
        $types = "attachment";
      }

      $allFoldered = 0;
      $allMedia = wp_count_attachments();
      $totalMedia = 0;
      foreach ($allMedia as $minecount) {
        $totalMedia += $minecount;
      }

      if ($contentPage == "true") {
        ////QUERY POSTS
        $types = [];
        $args = ["public" => true];
        $output = "objects";
        $post_types = get_post_types($args, $output);

        foreach ($post_types as $posttype) {
          array_push($types, $posttype->name);
        }

        $post_types_enabled = $utils->get_option("content", "post-types-content");

        if ($post_types_enabled && is_array($post_types_enabled)) {
          $types = $post_types_enabled;
        }

        $args = [
          "post_type" => $types,
          "post_status" => "any",
          "fields" => "ids",
        ];

        if ($privatemode == "true") {
          $args["author"] = get_current_user_id();
        }

        wp_reset_query();
        $attachments = new WP_Query($args);
        $totalMedia = $attachments->found_posts;
      }

      ///QUERY CONTENT
      $args = [
        "post_type" => $types,
        "fields" => "ids",
        "posts_per_page" => -1,
        "post_status" => "any",
        "meta_query" => [
          [
            "key" => "admin2020_folder",
            "value" => $folderIDS,
            "compare" => "IN",
          ],
        ],
      ];

      $query = new WP_Query($args);
      $contentWithFolder = $query->get_posts();
      $contentCount = [];

      foreach ($contentWithFolder as $item) {
        $folderid = get_post_meta($item, "admin2020_folder", true);

        if (isset($contentCount[$folderid])) {
          $contentCount[$folderid] += 1;
        } else {
          $contentCount[$folderid] = 1;
        }
      }

      foreach ($folders as $folder) {
        $parent_folder = get_post_meta($folder->ID, "parent_folder", true);

        if (!$parent_folder) {
          $structure[] = $this->build_folder_structure($folder, $folders, $contentCount);
        }
      }

      ///QUERY CONTENT
      $args = [
        "post_type" => $types,
        "fields" => "ids",
        "posts_per_page" => -1,
        "post_status" => "any",
        "meta_query" => [
          "relation" => "OR",
          [
            "key" => "admin2020_folder",
            "compare" => "NOT EXISTS",
          ],
          [
            "key" => "admin2020_folder",
            "value" => "",
            "compare" => "=",
          ],
        ],
      ];

      if ($privatemode == "true") {
        $args["author"] = get_current_user_id();
      }

      $query = new WP_Query($args);
      $contentWithNoFolder = $query->post_count;

      $returnata["folders"] = $structure;
      $returnata["mediaCount"] = $totalMedia;
      $returnata["noFolderCount"] = $contentWithNoFolder;
      echo json_encode($returnata);
    }
    die();
  }

  /**
   * Build data structure for folders array
   * @since 2.2
   */
  public function build_folder_structure($folder, $folders, $contentcount)
  {
    $temp = [];
    $foldercolor = get_post_meta($folder->ID, "color_tag", true);
    $top_level = get_post_meta($folder->ID, "parent_folder", true);
    $title = $folder->post_title;

    $temp["title"] = $title;
    $temp["color"] = $foldercolor;
    $temp["id"] = $folder->ID;
    $temp["count"] = 0;

    if (isset($contentcount[$folder->ID])) {
      $temp["count"] = $contentcount[$folder->ID];
    }

    foreach ($folders as $aFolder) {
      $folderParent = get_post_meta($aFolder->ID, "parent_folder", true);

      if ($folderParent == $folder->ID) {
        $temp["subs"][] = $this->build_folder_structure($aFolder, $folders, $contentcount);
      }
    }

    return $temp;
  }
}
