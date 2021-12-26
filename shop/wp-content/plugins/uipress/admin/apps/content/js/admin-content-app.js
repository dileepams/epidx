const a2020prefs = JSON.parse(a2020_content_ajax.a2020_content_prefs);
const uipContentPage = a2020_content_ajax.contentPage;

const wpteams = {
  data() {
    return {
      loading: true,
      upload: false,
      masterLoader: false,
      translations: uipTranslations,
      dataConnect: uipMasterPrefs.dataConnect,
      contentTable: {
        content: [],
        total: 0,
        test: true,
        currentPage: 1,
        totalPages: 1,
        selected: [],
        selectAll: false,
        postTypes: [],
        postStatuses: [],
        fileTypes: [],
        categories: [],
        tags: [],
        mode: a2020prefs.viewMode,
        gridSize: a2020prefs.gridSize,
        folderPanel: a2020prefs.folderView == "true",
        views: {
          allViews: [],
          currentView: [],
        },
        filters: {
          search: "",
          selectedPostTypes: [],
          selectedPostStatuses: [],
          selectedFileTypes: [],
          selectedCategories: [],
          selectedTags: [],
          date: "",
          dateComparison: "on",
          perPage: a2020prefs.perPage,
          activeFolder: "all",
        },
      },
      newView: {
        name: "",
      },
      folders: {
        allFolders: [],
        openFolders: [],
        activeFolder: [],
        activeFolderObj: [],
        newFolder: {
          name: "",
          color: "#0c5cef",
          parent: "",
        },
        editFolder: {
          name: "",
          color: "",
          id: "",
        },
      },
      quickEdit: {
        id: "",
        title: "",
        status: "",
        author: "",
        created: "",
        modified: "",
        postType: "",
        url: "",
        selectedCategories: [],
        selectedStatus: [],
        selectedTags: [],
      },
      batchUpdate: {
        tags: [],
        categories: [],
        replaceTags: false,
        replaceCats: false,
      },
      batchRename: {
        renameTypes: a2020prefs.renameOptions,
        selectedAttribute: "name",
        metaKey: "",
        selectedTypes: [],
        selectedOption: 0,
        preview: [],
      },
      ui: {
        batchRename: false,
        catsTags: false,
        quickEdit: false,
        newView: false,
        filters: false,
      },
    };
  },
  computed: {
    queryContent() {
      this.getFiles();
    },
    fileList() {
      this.queryContent;
      return this.contentTable.content;
    },
  },
  mounted: function () {
    window.setInterval(() => {
      ///TIMED FUNCTIONS
    }, 15000);
    self = this;
    this.masterLoader = true;
    ////SETUP FILE POND

    window.addEventListener("folder-change", function (event) {
      self.setActiveFolder(event.detail.folder);
    });
  },
  watch: {
    "contentTable.filters.perPage": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.saveUserPrefSingle("content_per_page", newValue, false);
      }
    },
    "contentTable.gridSize": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.saveUserPrefSingle("content_grid_size", newValue, false);
      }
    },
    "contentTable.mode": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.saveUserPrefSingle("content_view_mode", newValue, false);
      }
    },
  },
  methods: {
    getCardWidthClass(width) {
      if (width == 6) {
        return "uip-width-xxsmall";
      }
      if (width == 5) {
        return "uip-width-xsmall";
      }
      if (width == 4) {
        return "uip-width-small";
      }
      if (width == 3) {
        return "uip-width-small-medium";
      }
      if (width == 2) {
        return "uip-width-medium";
      }
      if (width == 1) {
        return "uip-width-xlarge";
      }
    },
    getdatafromComp(data) {
      return data;
    },
    setActiveFolder(data) {
      this.contentTable.filters.activeFolder = data;
      this.getFiles();
      //console.log(data);
    },
    //////TITLE: Adds a batch rename option/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    addBatchNameOption() {
      selectedOption = this.batchRename.selectedOption;

      temp = {};
      temp.name = selectedOption;
      temp.primaryValue = null;
      temp.secondaryValue = null;

      this.batchRename.selectedTypes.push(temp);
    },
    //////TITLE:SAVES UEER PREFS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    saveUserPrefSingle(optionName, optionValue, notification) {
      if (optionName == "" || optionValue == "") {
        return;
      }

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_save_pref_single",
          security: a2020_content_ajax.security,
          optionName: optionName,
          optionValue: optionValue,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }
        },
      });
    },

    removeBatchOption(index) {
      this.batchRename.selectedTypes.splice(index, 1);
    },
    moveBatchOptionUp(currentIndex) {
      this.array_move(this.batchRename.selectedTypes, currentIndex, currentIndex - 1);
    },
    moveBatchOptionDown(currentIndex) {
      this.array_move(this.batchRename.selectedTypes, currentIndex, currentIndex + 1);
    },
    array_move(arr, old_index, new_index) {
      if (new_index >= arr.length) {
        var k = new_index - arr.length + 1;
        while (k--) {
          arr.push(undefined);
        }
      }
      arr.splice(new_index, 0, arr.splice(old_index, 1)[0]);
    },
    startFilePond() {
      self = this;
      ////SETUP FILE POND
      jQuery.fn.filepond.registerPlugin(FilePondPluginFileEncode);
      jQuery.fn.filepond.registerPlugin(FilePondPluginFileValidateSize);
      jQuery.fn.filepond.registerPlugin(FilePondPluginImageExifOrientation);
      jQuery.fn.filepond.registerPlugin(FilePondPluginFileValidateType);

      jQuery.fn.filepond.setDefaults({
        acceptedFileTypes: JSON.parse(a2020_content_ajax.a2020_allowed_types),
        allowRevert: false,
      });

      //jQuery("#a2020_file_upload").filepond();

      FilePond.setOptions({
        server: {
          url: a2020_content_ajax.ajax_url,
          type: "post",
          process: {
            url: "?action=a2020_process_upload&security=" + a2020_content_ajax.security + "&folder=" + self.folders.activeFolder[0],
            method: "POST",
            ondata: (formData) => {
              formData.append("folder", self.folders.activeFolder[0]);
              return formData;
            },
            onload: (res) => {
              // select the right value in the response here and return
              if (res) {
                data = JSON.parse(res);

                if (data.error) {
                  return res;
                }

                self.getFiles();
              }
              return res;
            },
          },
        },
      });
    },
    batchRenamePreview() {
      self = this;
      selected = this.contentTable.selected;

      options = self.batchRename.selectedTypes;
      fieldToRename = this.batchRename.selectedAttribute;
      metaKey = this.batchRename.metaKey;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_batch_rename_preview",
          security: a2020_content_ajax.security,
          selected: selected,
          batchoptions: options,
          fieldToRename: fieldToRename,
          metaKey: metaKey,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          self.batchRename.preview = data.newnames;
        },
      });
    },

    batchRenameProcess() {
      self = this;
      selected = this.contentTable.selected;

      options = self.batchRename.selectedTypes;
      fieldToRename = this.batchRename.selectedAttribute;
      metaKey = this.batchRename.metaKey;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_process_batch_rename",
          security: a2020_content_ajax.security,
          selected: selected,
          batchoptions: options,
          fieldToRename: fieldToRename,
          metaKey: metaKey,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          uipNotification(data.message, { pos: "bottom-left", status: "success" });
          self.getFiles();
          self.batchRename.preview = [];
        },
      });
    },
    //////TITLE: GETS APP CONTENT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: MAIN QUERY FOR CONTENT
    getFiles() {
      self = this;
      this.loading = true;

      searchString = self.contentTable.filters.search;
      page = self.contentTable.currentPage;
      types = self.contentTable.filters.selectedPostTypes;
      statuses = self.contentTable.filters.selectedPostStatuses;
      filters = self.contentTable.filters;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_get_content",
          security: a2020_content_ajax.security,
          searchString: searchString,
          page: page,
          types: types,
          statuses: statuses,
          filters: filters,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data) {
            self.contentTable.content = data.content;
            self.contentTable.total = data.total;
            self.contentTable.totalPages = data.totalPages;
            self.contentTable.postTypes = data.postTypes;
            self.contentTable.postStatuses = data.postStatuses;
            self.contentTable.fileTypes = data.fileTypes;
            self.contentTable.categories = data.categories;
            self.contentTable.tags = data.tags;
            self.contentTable.views.allViews = data.views;

            self.loading = false;

            if (page < 1 || !page || page > self.contentTable.totalPages) {
              page = 1;
              self.contentTable.currentPage = 1;
            }

            return self.contentTable.content;
          }
        },
      });
    },
    nameNewView() {
      self.ui.filters = false;
      self.ui.newView = true;
    },
    openBatchRename() {
      this.ui.batchRename = true;
    },
    openCatsTags() {
      this.ui.catsTags = true;
    },
    switchFolderPanel() {
      this.contentTable.folderPanel = !this.contentTable.folderPanel;
      if (this.contentTable.folderPanel) {
        data = "true";
      } else {
        data = "false";
      }
      this.saveUserPrefSingle("content_folder_view", data, false);
    },

    //////TITLE: OPENS QUICK EDIT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    openQuickEdit(itemid) {
      self = this;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_open_quick_edit",
          security: a2020_content_ajax.security,
          itemid: itemid,
        },
        success: function (response) {
          data = JSON.parse(response);
          self.quickEdit = data;
          self.ui.quickEdit = true;
        },
      });
    },
    openImageEdit() {
      var imgSRC = this.quickEdit.src;
      self = this;

      var {
        createDefaultImageReader,
        createDefaultImageWriter,
        locale_en_gb,
        setPlugins,
        plugin_crop,
        plugin_crop_defaults,
        plugin_crop_locale_en_gb,
        plugin_filter,
        plugin_filter_defaults,
        plugin_filter_locale_en_gb,
        plugin_finetune,
        plugin_finetune_defaults,
        plugin_finetune_locale_en_gb,
        plugin_annotate,
        plugin_annotate_locale_en_gb,
        plugin_sticker,
        plugin_sticker_locale_en_gb,
        markup_editor_defaults,
        markup_editor_locale_en_gb,
      } = jQuery.fn.doka;

      setPlugins(plugin_crop, plugin_filter, plugin_finetune, plugin_annotate, plugin_sticker);
      // inline
      var ImageEditor = jQuery.fn.doka.openEditor({
        src: imgSRC,
        imageReader: createDefaultImageReader(),
        imageWriter: createDefaultImageWriter(),
        stickers: [["Emoji", ["⭐️", "😊", "👍", "👎", "☀️", "🌤", "🌥"]]],

        // set default view properties
        cropSelectPresetOptions: plugin_crop_defaults.cropSelectPresetOptions,
        filterFunctions: plugin_filter_defaults.filterFunctions,
        filterOptions: plugin_filter_defaults.filterOptions,
        finetuneControlConfiguration: plugin_finetune_defaults.finetuneControlConfiguration,
        finetuneOptions: plugin_finetune_defaults.finetuneOptions,

        markupEditorToolbar: markup_editor_defaults.markupEditorToolbar,
        markupEditorToolStyles: markup_editor_defaults.markupEditorToolStyles,
        markupEditorShapeStyleControls: markup_editor_defaults.markupEditorShapeStyleControls,

        // set locale to en_gb
        locale: Object.assign(
          {},
          locale_en_gb,
          plugin_crop_locale_en_gb,
          plugin_finetune_locale_en_gb,
          plugin_filter_locale_en_gb,
          plugin_annotate_locale_en_gb,
          plugin_sticker_locale_en_gb,
          markup_editor_locale_en_gb
        ),
      });

      // this will update the result image with the returned image file
      ImageEditor.on("process", (res) => self.saveEditedImage(res.dest));
    },
    saveEditedImage(theblob) {
      uipNotification("Saving");
      self = this;
      fd = new FormData();
      fd.append("ammended_image", theblob);
      fd.append("attachmentid", self.quickEdit.id);
      fd.append("security", a2020_content_ajax.security);
      fd.append("action", "a2020_save_edited_image");

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: fd,
        async: true,
        cache: false,
        contentType: false,
        processData: false,
        success: function (response) {
          if (response) {
            data = JSON.parse(response);

            if (data.error) {
              uipNotification(data.error_message, "danger");
            } else {
              self.quickEdit.src = data.src;
              uipNotification(data.message, "success");
            }
          }
        },
        error: function (error) {
          console.log(error);
        },
      });
    },
    //////TITLE: SAVES ITEM FROM QUICK EDIT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    updateItem() {
      self = this;
      options = self.quickEdit;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_update_item",
          security: a2020_content_ajax.security,
          options: options,
        },
        success: function (response) {
          data = JSON.parse(response);
          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            uipNotification(data.message, { pos: "bottom-left", status: "success" });
            self.quickEdit.status = data.status;
            self.getFiles();
          }
        },
      });
    },
    //////TITLE: MAKE FOLDER TOP LEVEL/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    dropInTopLevel(evt) {
      var itemID = evt.dataTransfer.getData("itemID");
      var dropItemType = evt.dataTransfer.getData("type");
      if (dropItemType == "folder") {
        this.moveFolder(itemID, "toplevel");
      }
      if (dropItemType == "content") {
        this.moveContentToFolder(itemID, "toplevel");
      }
      jQuery("#a2020-folder-template").hide();
    },

    moveContentToFolder(contentID, destinationId) {
      self = this;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_content_to_folder",
          security: a2020_content_ajax.security,
          contentID: contentID,
          destinationId: destinationId,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            uipNotification(data.message, { pos: "bottom-left", status: "succes" });
            self.getFiles();
            return self.getFolders();
          }
        },
      });
    },
    ////DRAG & DROP//////
    //////TITLE: SETS DATA FOR ITEM DRAG/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    startContentDrag(evt, item) {
      allSelected = this.contentTable.selected;

      if (allSelected.length > 0) {
        evt.dataTransfer.setData("itemID", JSON.stringify(allSelected));
        thefiles = allSelected.length + " files";
      } else {
        evt.dataTransfer.setData("itemID", JSON.stringify([item.id]));
        thefiles = "1 file";
      }

      evt.dataTransfer.dropEffect = "move";
      evt.dataTransfer.effectAllowed = "move";
      evt.dataTransfer.setData("type", "content");
      jQuery("#a2020-folder-template").show();

      ///SET DRAG HANDLE

      var elem = document.createElement("div");
      elem.id = "uip-content-drag";
      elem.innerHTML = thefiles;
      elem.style.position = "absolute";
      elem.style.top = "-1000px";
      document.body.appendChild(elem);
      evt.dataTransfer.setDragImage(elem, 0, 0);

      jQuery(".uip-remove-folder").addClass("uip-nothidden");
    },

    endContentDrag(evt, item) {
      jQuery("#a2020-folder-template").hide();
    },
    //////TITLE: SELECTS ALL IN TABLE/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    selectAllTable() {
      self = this;
      if (self.contentTable.selectAll === false) {
        self.contentTable.selected = [];
        self.contentTable.content.forEach(function (item, index) {
          self.contentTable.selected.push(item.id);
        });
        self.contentTable.selectAll = true;
      } else {
        self.contentTable.selected = [];
        self.contentTable.selectAll = false;
      }
    },
    //////TITLE: SELECTS ALL IN TABLE/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    setView(view) {
      this.resetFilters();
      this.contentTable.views.currentView = view;

      for (var key in view.filters) {
        this.contentTable.filters[key] = view.filters[key];
      }
    },
    //////TITLE: Resets all filters/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    resetFilters() {
      this.contentTable.views.currentView = [];
      this.contentTable.filters = {
        search: "",
        selectedPostTypes: [],
        selectedPostStatuses: [],
        selectedFileTypes: [],
        selectedCategories: [],
        selectedTags: [],
        date: "",
        dateComparison: "on",
        perPage: 20,
      };
    },
    ////CHECK IF SOMETHING IS IN A ARRAY
    isIn(option, options) {
      return options.includes(option);
    },
    //////TITLE: REMOVES ITEM FROM ARRAY/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    removeFromList(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        options = options.splice(index, 1);
      }
    },
    //////TITLE: checks total filters/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    totalFilters() {
      total = 0;
      total += this.contentTable.filters.selectedPostTypes.length;
      total += this.contentTable.filters.selectedPostStatuses.length;
      total += this.contentTable.filters.date.length;
      total += this.contentTable.filters.selectedFileTypes.length;
      total += this.contentTable.filters.selectedCategories.length;
      total += this.contentTable.filters.selectedTags.length;

      if (total > 0) {
        return true;
      } else {
        return false;
      }
    },
    //////TITLE: Saves current view/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    saveView() {
      self = this;
      count = this.contentTable.views.allViews.length + 1;

      newView = {
        name: this.newView.name,
        filters: this.contentTable.filters,
        id: count,
      };

      this.contentTable.views.allViews.push(newView);
      this.refreshViews();
    },
    //////TITLE: REFRESHES VIEWS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    refreshViews() {
      allViews = this.contentTable.views.allViews;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_save_view",
          security: a2020_content_ajax.security,
          allViews: allViews,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data) {
            self.loading = false;
            uipNotification(data.message, { pos: "bottom-left", status: "success" });
            UIkit.modal("#new-view-modal").hide();
            self.getFiles();
          }
        },
      });
    },
    //////TITLE: REMOVES VIEWS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    removeView(option) {
      options = this.contentTable.views.allViews;
      newViews = [];
      options.forEach(function (item, index) {
        if (item.id != option.id) {
          newViews.push(item);
        }
      });
      this.contentTable.views.allViews = newViews;
      this.refreshViews();
    },

    duplicateItem(itemid) {
      this.contentTable.selected = [itemid];
      this.duplicateMultiple();
    },
    duplicateMultiple() {
      self = this;
      selected = this.contentTable.selected;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_duplicate_selected",
          security: a2020_content_ajax.security,
          selected: selected,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONF
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///USER DELETED
            totaldeleted = parseInt(data.deleted_total);
            if (totaldeleted > 0) {
              uipNotification(data.deleted_total + " " + data.deleted_message, { pos: "bottom-left", status: "success" });
            }
            ///FAILED
            totalfailed = parseInt(data.failed_total);
            if (totalfailed > 0) {
              uipNotification(data.failed_total + " " + data.failed_message, { pos: "bottom-left", status: "warning" });
            }
            self.contentTable.selected = [];
            self.getFiles();
          }
        },
      });
    },

    batchUpdateTagsCats() {
      self = this;
      selected = this.contentTable.selected;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_batch_tags_cats",
          security: a2020_content_ajax.security,
          selected: selected,
          theTags: self.batchUpdate,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONF
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            uipNotification(data.message, { pos: "bottom-left", status: "success" });
            self.contentTable.selected = [];
            UIkit.modal("#tags-cats-modal").hide();
            //self.getFiles();
          }
        },
      });
    },
    //////TITLE: DELETE SINGLE ITEM/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    deleteItem(itemid) {
      this.contentTable.selected = [itemid];
      this.deleteMultiple();
    },
    //////TITLE: DELETE SELECTED ITEMS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    deleteMultiple() {
      selected = this.contentTable.selected;

      if (confirm(self.translations.confirmDelete)) {
        self.deleteSelected();
      }
    },
    deleteSelected() {
      self = this;
      selected = this.contentTable.selected;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_delete_selected",
          security: a2020_content_ajax.security,
          selected: selected,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONF
            uipNotification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///USER DELETED
            totaldeleted = parseInt(data.deleted_total);
            if (totaldeleted > 0) {
              uipNotification(data.deleted_total + " " + data.deleted_message, { pos: "bottom-left", status: "success" });
            }
            ///FAILED
            totalfailed = parseInt(data.failed_total);
            if (totalfailed > 0) {
              uipNotification(data.failed_total + " " + data.failed_message, { pos: "bottom-left", status: "warning" });
            }
            self.contentTable.selected = [];
            self.getFiles();
          }
        },
      });
    },
  },
};

///BUILD VUE APP
const contentPageApp = uipVue.createApp(wpteams);

contentPageApp.component("uip-file-upload", {
  props: {
    maxUpload: Number,
    activeFolder: Number,
  },
  data: function () {
    return {};
  },
  mounted: function () {
    this.startFilePond();
  },
  methods: {
    startFilePond() {
      self = this;
      ////SETUP FILE POND
      jQuery.fn.filepond.registerPlugin(FilePondPluginFileEncode);
      jQuery.fn.filepond.registerPlugin(FilePondPluginFileValidateSize);
      jQuery.fn.filepond.registerPlugin(FilePondPluginImageExifOrientation);
      jQuery.fn.filepond.registerPlugin(FilePondPluginFileValidateType);

      jQuery.fn.filepond.setDefaults({
        acceptedFileTypes: JSON.parse(a2020_content_ajax.a2020_allowed_types),
        allowRevert: false,
      });

      jQuery("#a2020_file_upload").filepond();

      FilePond.setOptions({
        server: {
          url: a2020_content_ajax.ajax_url,
          type: "post",
          process: {
            url: "?action=a2020_process_upload&security=" + a2020_content_ajax.security + "&folder=" + self.activeFolder,
            method: "POST",
            ondata: (formData) => {
              formData.append("folder", self.activeFolder);
              return formData;
            },
            onload: (res) => {
              // select the right value in the response here and return
              if (res) {
                data = JSON.parse(res);

                if (data.error) {
                  return res;
                }

                self.$root.getFiles();
              }
              return res;
            },
          },
        },
      });
    },
  },
  template:
    '<input type="file" \
    class="filepond"\
    name="filepond" \
    multiple \
    id="a2020_file_upload"\
    data-allow-reorder="true"\
    :data-max-file-size="maxUpload"\
    data-max-files="30">',
});

contentPageApp.component("uip-dropdown", {
  props: {
    type: String,
    icon: String,
    pos: String,
    translation: String,
    size: String,
    primary: Boolean,
  },
  data: function () {
    return {
      modelOpen: false,
    };
  },
  mounted: function () {},
  methods: {
    onClickOutside(event) {
      const path = event.path || (event.composedPath ? event.composedPath() : undefined);
      // check if the MouseClick occurs inside the component
      if (path && !path.includes(this.$el) && !this.$el.contains(event.target)) {
        this.closeThisComponent(); // whatever method which close your component
      }
    },
    openThisComponent() {
      this.modelOpen = this.modelOpen != true; // whatever codes which open your component
      // You can also use Vue.$nextTick or setTimeout
      requestAnimationFrame(() => {
        document.documentElement.addEventListener("click", this.onClickOutside, false);
      });
    },
    closeThisComponent() {
      this.modelOpen = false; // whatever codes which close your component
      document.documentElement.removeEventListener("click", this.onClickOutside, false);
    },
    getClass() {
      if (this.pos == "botton-left") {
        return "uip-margin-top-s uip-right-0";
      }
      if (this.pos == "botton-center") {
        return "uip-margin-top-s uip-right-center";
      }
      if (this.pos == "top-left") {
        return "uip-margin-bottom-s uip-right-0 uip-bottom-100p";
      }
    },
    getPaddingClass() {
      if (!this.size) {
        return "uip-padding-xs";
      }
      if (this.size == "small") {
        return "uip-padding-xxs";
      }
      if (this.size == "large") {
        return "uip-padding-s";
      }
      return "uip-padding-xs";
    },
    getPrimaryClass() {
      if (!this.primary) {
        return "uip-button-default";
      }
      if (this.primary) {
        return "uip-button-primary uip-text-bold";
      }
      return "uip-button-default";
    },
  },
  template:
    '<div class="uip-position-relative">\
      <div class="">\
        <div v-if="type == \'icon\'" @click="openThisComponent" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer  material-icons-outlined" type="button" :class="getPaddingClass()">{{icon}}</div>\
        <button v-if="type == \'button\'" @click="openThisComponent" class="uip-button-default" :class="[getPaddingClass(), getPrimaryClass() ]" type="button">{{translation}}</button>\
      </div>\
      <div v-if="modelOpen" :class="getClass()"\
      class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-shadow uip-min-w-200 uip-z-index-9999">\
        <slot></slot>\
      </div>\
    </div>',
});

contentPageApp.component("uip-offcanvas", {
  props: {
    type: String,
    icon: String,
    pos: String,
    translation: String,
    title: String,
  },
  data: function () {
    return {
      modelOpen: false,
    };
  },
  mounted: function () {},
  methods: {
    openThisComponent() {
      this.modelOpen = this.modelOpen != true; // whatever codes which open your component
      // You can also use Vue.$nextTick or setTimeout
    },
    closeThisComponent() {
      this.modelOpen = false; // whatever codes which close your component
    },
    getClass() {
      if (this.pos == "botton-left") {
        return "uip-margin-top-s uip-right-0";
      }
      if (this.pos == "botton-center") {
        return "uip-margin-top-s uip-right-center";
      }
    },
  },
  template:
    '<div class="uip-position-relative">\
      <div class="">\
        <div v-if="type == \'icon\'" @click="openThisComponent" class="uip-background-muted uip-border-round hover:uip-background-grey uip-cursor-pointer uip-padding-xs material-icons-outlined" type="button">{{icon}}</div>\
        <button v-if="type == \'button\'" @click="openThisComponent" class="uip-button-default material-icons-outlined" type="button">{{translation}}</button>\
      </div>\
    </div>\
    <div v-if="modelOpen" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal" \
    style="background:rgba(0,0,0,0.3);z-index:99999;top:0;left:0;right:0;max-height:100vh" \
    :class="{\'uip-nothidden\' : modelOpen}">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p">\
        <div class="uip-flex-grow" @click="closeThisComponent()" ></div>\
        <div class="uip-w-500 uip-background-default uip-padding-m uip-overflow-auto uip-h-viewport" >\
          <div class="uk-background-default uk-padding uk-overflow-auto" uk-height-viewport style="max-height: 100vh;">\
            <!-- SEARCH TITLE -->\
            <div class="uip-flex uip-margin-bottom-m">\
              <div class="uip-text-xl uip-text-bold uip-flex-grow">{{title}}</div>\
              <div class="">\
                 <span @click="this.modelOpen = false"\
                  class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                     close\
                  </span>\
              </div>\
            </div>\
            <div>\
              <slot></slot>\
            </div>\
          </div>\
        </div>\
      </div>\
    </div>',
});

contentPageApp.component("uip-offcanvas-nb", {
  props: {
    type: String,
    icon: String,
    pos: String,
    translation: String,
    title: String,
    toggle: Boolean,
  },
  data: function () {
    return {
      modelOpen: this.toggle,
    };
  },
  watch: {
    modelOpen: function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.$emit("state-change", newValue);
      }
    },
  },
  mounted: function () {},
  computed: {
    isItOpen() {
      return this.modelOpen;
    },
  },
  methods: {
    openThisComponent() {
      this.modelOpen = this.modelOpen != true; // whatever codes which open your component
      // You can also use Vue.$nextTick or setTimeout
      this.$emit("state-change", this.modelOpen);
    },
    closeThisComponent() {
      this.modelOpen = false; // whatever codes which close your component
      this.$emit("state-change", this.modelOpen);
    },
    getClass() {
      if (this.pos == "botton-left") {
        return "uip-margin-top-s uip-right-0";
      }
      if (this.pos == "botton-center") {
        return "uip-margin-top-s uip-right-center";
      }
    },
  },
  template:
    '<div v-if="isItOpen" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal" \
    style="background:rgba(0,0,0,0.3);z-index:99999;top:0;left:0;right:0;max-height:100vh" \
    :class="{\'uip-nothidden\' : modelOpen}">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p">\
        <div class="uip-flex-grow" @click="closeThisComponent()" ></div>\
        <div class="uip-w-500 uip-padding-m uip-overflow-auto uip-h-viewport uip-background-default" >\
          <div class="uk-padding uk-overflow-auto" uk-height-viewport style="max-height: 100vh;">\
            <!-- SEARCH TITLE -->\
            <div class="uip-flex uip-margin-bottom-m">\
              <div class="uip-text-xl uip-text-bold uip-flex-grow">{{title}}</div>\
              <div class="">\
                 <span @click="this.modelOpen = false"\
                  class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                     close\
                  </span>\
              </div>\
            </div>\
            <div>\
              <slot></slot>\
            </div>\
          </div>\
        </div>\
      </div>\
    </div>',
});

contentPageApp.component("multi-select", {
  data: function () {
    return {
      thisSearchInput: "",
      ui: {
        dropOpen: false,
      },
    };
  },
  props: {
    options: Array,
    selected: Array,
    name: String,
    placeholder: String,
    single: Boolean,
  },
  methods: {
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    addSelected(option, options) {
      if (this.single == true) {
        options[0] = option;
      } else {
        options.push(option);
      }
    },
    //////TITLE: REMOVES A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    removeSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        options = options.splice(index, 1);
      }
    },

    //////TITLE:  CHECKS IF SELECTED OR NOT//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    ifSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        return false;
      } else {
        return true;
      }
    },
    //////TITLE:  CHECKS IF IN SEARCH//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: CHECKS IF ITEM CONTAINS STRING
    ifInSearch(option, searchString) {
      item = option.toLowerCase();
      string = searchString.toLowerCase();

      if (item.includes(string)) {
        return true;
      } else {
        return false;
      }
    },
    onClickOutside(event) {
      const path = event.path || (event.composedPath ? event.composedPath() : undefined);
      // check if the MouseClick occurs inside the component
      if (path && !path.includes(this.$el) && !this.$el.contains(event.target)) {
        this.closeThisComponent(); // whatever method which close your component
      }
    },
    openThisComponent() {
      this.ui.dropOpen = true; // whatever codes which open your component
      // You can also use Vue.$nextTick or setTimeout
      requestAnimationFrame(() => {
        document.documentElement.addEventListener("click", this.onClickOutside, false);
      });
    },
    closeThisComponent() {
      this.ui.dropOpen = false; // whatever codes which close your component
      document.documentElement.removeEventListener("click", this.onClickOutside, false);
    },
  },
  template:
    '<div class="uip-position-relative" >\
      <div @click="openThisComponent" class="uip-margin-bottom-xs uip-padding-left-xxs uip-padding-right-xxs uip-padding-top-xxs uip-background-default uip-border uip-border-round uip-w-400 uip-cursor-pointer uip-h-32 uip-border-box"> \
      	  <div class="uip-flex uip-flex-center uip-flex-row ">\
            <div class="uip-flex-grow uip-margin-right-s">\
          		<span v-if="selected.length < 1">\
          		  <span class="uip-text-meta">Select {{name}}...</span>\
          		</span>\
          		<span v-if="selected.length > 0" v-for="select in selected" class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-display-inline-block uip-margin-right-xxs uip-margin-bottom-xxs">\
          		  <template v-for="option in options">\
            		   <div v-if="option.name == select">\
            		   	{{option.label}}\
                     <span class="uip-margin-left-xxs"  @click="removeSelected(select,selected)">x</span>\
            		   </div>\
          		  </template>\
          		</span>\
        	  </div>\
            <div class="material-icons-outlined">expand_more</div>\
          </div>\
    	</div>\
      <!-- CONTAINER -->\
    	<div v-if="ui.dropOpen" class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-border uip-shadow uip-w-400 uip-border-box uip-z-index-9">\
        <div class="uip-flex uip-background-muted uip-padding-xxs uip-margin-bottom-s uip-border-round">\
          <span class="material-icons-outlined uip-text-muted uip-margin-right-xs">search</span>\
          <input class="uip-blank-input uip-flex-grow" type="search"  \
          :placeholder="placeholder" v-model="thisSearchInput" autofocus>\
        </div>\
        <div class="">\
          <template v-for="option in options">\
            <span \
            class="uip-background-muted uip-border-round uip-padding-xxs uip-display-inline-block uip-margin-right-xxs uip-margin-bottom-xxs uip-text-normal" \
            @click="addSelected(option.name, selected)" \
            v-if="ifSelected(option.name, selected) && ifInSearch(option.name, thisSearchInput)" \
            style="cursor: pointer">\
            {{option.label}}\
            </span>\
          </template>\
        </div>\
      </div>\
  	</div>',
});

contentPageApp.mount("#a2020-content-app");
