jQuery(document).on("dragstart", ".attachments-browser .attachments .attachment", function (ev) {
  jQuery(".uploader-window").addClass("uip-hidden");

  allIDS = [];
  if (jQuery(".attachments-browser .attachments .attachment[aria-checked='true']").length > 0) {
    jQuery(".attachments-browser .attachments .attachment[aria-checked='true']").each(function (index) {
      tempid = jQuery(this).attr("data-id");
      allIDS.push(tempid);
    });

    ev.originalEvent.dataTransfer.setData("itemID", JSON.stringify(allIDS));
  } else {
    theid = jQuery(ev.currentTarget).attr("data-id");
    ev.originalEvent.dataTransfer.setData("itemID", JSON.stringify([theid]));
  }

  thefiles = uipTranslations.oneFile;

  ev.originalEvent.dataTransfer.dropEffect = "move";
  ev.originalEvent.dataTransfer.effectAllowed = "move";
  ev.originalEvent.dataTransfer.setData("type", "content");

  ///SET DRAG HANDLE

  var elem = document.createElement("div");
  elem.id = "uip-content-drag";
  elem.innerHTML = thefiles;
  elem.style.position = "absolute";
  elem.style.top = "-1000px";
  document.body.appendChild(elem);
  ev.originalEvent.dataTransfer.setDragImage(elem, 0, 0);

  jQuery(".uip-remove-folder").addClass("uip-nothidden");
});

jQuery(document).on("dragend", ".attachments-browser .attachments .attachment", function (ev) {
  jQuery(".uip-remove-folder").removeClass("uip-nothidden");
});

const UIPfolderOptions = {
  emits: ["folder-change"],
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      translations: uipTranslations,
      masterPrefs: uipMasterPrefs,
      defaults: uipDefaults,
      preferences: uipUserPrefs,
      mediaCount: 0,
      noFolderCount: 0,
      folders: [],
      activeFolder: "all",
      activeFolderObject: [],
      openFolders: [],
    };
  },
  watch: {},
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {
    formattedFolders() {
      return this.folders;
    },
  },
  mounted: function () {
    this.getFolders();
    jQuery(".attachment").attr("draggable", "true");
  },
  methods: {
    getScreenWidth() {
      this.screenWidth = window.innerWidth;
    },
    isSmallScreen() {
      if (this.screenWidth < 1000) {
        return true;
      } else {
        return false;
      }
    },
    setActiveFolder(folderID, folderObject) {
      this.activeFolder = folderID;

      if (folderObject) {
        this.activeFolderObject = folderObject;
      }

      if (typeof uipContentPage !== "undefined") {
        window.dispatchEvent(
          new CustomEvent("folder-change", {
            detail: { folder: folderID },
          })
        );
        return;
      }

      if (wp.media.frames.browse) {
        wp.media.frames.browse.content.get().collection.props.set({ folder_id: folderID });
      } else {
        wp.media.frame.content.get().collection.props.set({ folder_id: folderID });
      }
    },
    setOpenFolders(folderID) {
      if (this.openFolders.includes(folderID)) {
        index = this.openFolders.indexOf(folderID);
        this.openFolders.splice(index, 1);
      } else {
        this.openFolders.push(folderID);
      }

      if (!this.openFolders) {
        this.openFolders = [];
      }
    },
    getFolders() {
      let self = this;

      contentPage = "false";
      if (typeof uipContentPage !== "undefined") {
        contentPage = "true";
      }

      console.log(contentPage);

      data = {
        action: "uip_get_folders",
        security: uip_ajax.security,
        contentPage: contentPage,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;
          if (data.error) {
            ///SOMETHING WENT WRONG
          } else {
            ///SOMETHING WENT RIGHT
            self.folders = data.folders;
            self.mediaCount = data.mediaCount;
            self.noFolderCount = data.noFolderCount;
          }
        },
      });
    },
  },
  template:
    '<div class="">\
    <default-folders :mediaCount="mediaCount" :noFolderCount="noFolderCount" :translations="translations" :masterPrefs="masterPrefs" :defaults="defaults" :preferences="preferences"\
    :activeFolder="activeFolder" :folderUpdate="setActiveFolder"></default-folders>\
    <user-folders :activeFolderObject="activeFolderObject" :setOpenFolders="setOpenFolders" :openFolders="openFolders" :refreshFolders="getFolders" :translations="translations" :masterPrefs="masterPrefs" :defaults="defaults" :preferences="preferences"\
    :activeFolder="activeFolder" :folderUpdate="setActiveFolder" :folders="formattedFolders"></user-folders>\
  </div>',
};
const UIPfolder = uipVue.createApp(UIPfolderOptions);

/////////////////////////
//CREATES DEFAULT FOLDERS
/////////////////////////
UIPfolder.component("default-folders", {
  props: {
    translations: Object,
    masterPrefs: Object,
    defaults: Object,
    preferences: Object,
    folderUpdate: Function,
    activeFolder: [String, Number],
    mediaCount: Number,
    noFolderCount: Number,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {},
  methods: {},
  template:
    '<div class="uip-margin-bottom-s">\
      <div @click="folderUpdate(\'all\')" :class="{\'uip-background-muted uip-text-bold uip-text-emphasis\' : activeFolder == \'all\'}"\
      class="uip-border-round uip-flex uip-padding-xxs uip-text-m hover:uip-background-muted uip-cursor-pointer uip-margin-bottom-xxs">\
        <span class="material-icons-outlined uip-margin-right-xxs">folder</span>\
        <span class="uip-flex-grow">{{translations.allContent}}</span>\
        <span class="uip-border-round uip-background-primary-wash uip-padding-left-xxs uip-padding-right-xxs">{{mediaCount}}</span>\
      </div>\
      <div @click="folderUpdate(\'nofolder\')"  :class="{\'uip-background-muted uip-text-bold uip-text-emphasis\' : activeFolder == \'nofolder\'}"\
      class="uip-border-round uip-flex uip-padding-xxs uip-text-m hover:uip-background-muted uip-cursor-pointer ">\
        <span class="material-icons-outlined uip-margin-right-xxs">folder</span>\
        <span class="uip-flex-grow">{{translations.noFolder}}</span>\
        <span class="uip-border-round uip-background-primary-wash uip-padding-left-xxs uip-padding-right-xxs">{{noFolderCount}}</span>\
      </div>\
    </div>',
});

/////////////////////////
//LOOPS USER FOLDERS
/////////////////////////
UIPfolder.component("user-folders", {
  props: {
    translations: Object,
    masterPrefs: Object,
    defaults: Object,
    preferences: Object,
    folderUpdate: Function,
    activeFolder: [String, Number],
    folders: Object,
    refreshFolders: Function,
    openFolders: Array,
    setOpenFolders: Function,
    activeFolderObject: Object,
  },
  data: function () {
    return {
      loading: true,
      ui: {
        createNew: {
          open: false,
          name: "",
          color: "#0c5cef",
        },
        edit: {
          open: false,
          active: this.activeFolderObject,
        },
      },
    };
  },
  mounted: function () {},
  computed: {
    formattedFolders() {
      return this.folders;
    },
  },
  methods: {
    openCreateFolder() {
      this.ui.createNew.open = true;
    },
    openEditFolder() {
      this.ui.edit.active = this.activeFolderObject;

      this.ui.edit.open = true;
    },
    deleteFolder() {
      let self = this;
      data = {
        action: "uip_delete_folder",
        security: uip_ajax.security,
        activeFolder: self.activeFolder,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;

          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error);
          } else {
            ///SOMETHING WENT RIGHT
            self.folderUpdate("all", {});
            uipNotification(data.message);
            self.refreshFolders();
          }
        },
      });
    },
    createFolder() {
      let self = this;
      data = {
        action: "uip_create_folder",
        security: uip_ajax.security,
        folderInfo: self.ui.createNew,
        parent: self.activeFolder,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;
          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error);
          } else {
            ///SOMETHING WENT RIGHT
            uipNotification(data.message);
            self.refreshFolders();
          }
        },
      });
    },
    updateFolder() {
      let self = this;
      data = {
        action: "uip_update_folder",
        security: uip_ajax.security,
        folderInfo: self.ui.edit.active,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;
          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error);
          } else {
            ///SOMETHING WENT RIGHT
            uipNotification(data.message);
            self.refreshFolders();
          }
        },
      });
    },
    addDropClass(evt, folder) {
      evt.preventDefault();
      target = evt.target;
      this.dragCounter++;
      if (jQuery(target).hasClass("uip-folder-can-drop")) {
        jQuery(target).addClass("uip-background-primary-wash");
      } else {
        jQuery(target).closest(".uip-folder-can-drop").addClass("uip-background-primary-wash");
      }
    },
    removeDropClass(evt, folder) {
      evt.preventDefault();
      target = evt.target;
      this.dragCounter--;

      if (this.dragCounter != 0) {
        return;
      }
      if (jQuery(target).hasClass("uip-folder-can-drop")) {
        jQuery(target).removeClass("uip-background-primary-wash");
      } else {
        jQuery(target).closest(".uip-folder-can-drop").removeClass("uip-background-primary-wash");
      }
    },
    removeFromFolder(evt) {
      this.dragCounter = 0;
      var itemID = evt.dataTransfer.getData("itemID");
      var dropItemType = evt.dataTransfer.getData("type");

      if (dropItemType == "folder") {
        itemID = [itemID];
      } else {
        itemID = JSON.parse(itemID);
      }
      this.removeTheFolder(itemID, dropItemType);

      jQuery(".uip-folder-can-drop").removeClass("uip-background-primary-wash");
      jQuery(".uploader-window").addClass("uip-opacity-0");
      jQuery(".uploader-window").removeClass("uip-hidden");
      jQuery(".uip-remove-folder").removeClass("uip-nothidden");

      setTimeout(function () {
        jQuery(".uploader-window").removeClass("uip-opacity-0");
      }, 1000);
    },
    removeTheFolder(items, itemtype) {
      let self = this;
      data = {
        action: "uip_remove_from_folder",
        security: uip_ajax.security,
        items: items,
        itemtype: itemtype,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;
          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error);
          } else {
            ///SOMETHING WENT RIGHT
            uipNotification(data.message);
            self.refreshFolders();
          }
        },
      });
    },
  },
  template:
    '<div class="uip-margin-bottom-xs uip-padding-xxs uip-flex">\
      <div class="uip-text-muted">{{translations.folders}}</div>\
      <div class="uip-flex-grow uip-margin-left-xs uip-flex">\
        <div @click="openCreateFolder()"\
        class="uip-background-muted uip-border-round material-icons-outlined hover:uip-background-grey uip-cursor-pointer">add</div>\
      </div>\
      <div @click="openEditFolder()" v-if="!isNaN(activeFolder)"\
      class="uip-background-muted uip-border-round material-icons-outlined uip-margin-left-xs hover:uip-background-grey uip-cursor-pointer">edit</div>\
      <div @click="deleteFolder()" v-if="!isNaN(activeFolder)"\
      class="uip-background-red-wash uip-border-round material-icons-outlined uip-margin-left-xs hover:uip-background-grey uip-cursor-pointer">delete_outline</div>\
    </div>\
    <p class="uip-padding-xxs uip-text-muted" v-if="formattedFolders.length < 1">{{translations.noFolders}}</p>\
    <div class="uip-overflow-auto uip-max-h-400">\
      <template v-for="folder in formattedFolders">\
        <create-folder :refreshFolders="refreshFolders" :openFolders="openFolders" :setOpenFolders="setOpenFolders" :folder="folder" :translations="translations" :masterPrefs="masterPrefs" :defaults="defaults" :preferences="preferences"\
        :activeFolder="activeFolder" :folderUpdate="folderUpdate"></create-folder>\
      </template>\
    </div>\
    <!-- REMOVE FROM FOLDER -->\
    <div @drop="removeFromFolder($event, folder)" \
    @dragenter="addDropClass($event, folder)"\
    @dragleave="removeDropClass($event, folder)"\
    @dragover.prevent\
    @dragenter.prevent\
    class="uip-background-muted uip-border-round uip-flex uip-padding-xs uip-margin-top-s uip-text-m uip-folder-can-drop uip-remove-folder uip-hidden">\
      {{translations.removeFromFolder}}\
    </div>\
    <!-- CREATE NEW FOLDER -->\
    <div v-if="ui.createNew.open" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal"\
    :class="{\'uip-nothidden\' : ui.createNew.open}" style="background: rgba(0, 0, 0, 0.3); z-index: 99999; top: 0px; left: 0px; right: 0px; max-height: 100vh;">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p uip-h-viewport">\
        <div class="uip-flex-grow" @click="ui.createNew.open = false"></div>\
        <div class="uip-w-500 uip-background-default uip-padding-m uip-overflow-auto">\
          <div class="uk-width-xlarge uk-background-default uk-padding uk-overflow-auto" uk-height-viewport style="max-height: 100vh;">\
            <!-- NEW FOLDER TITLE -->\
            <div class="uip-flex uip-margin-bottom-m">\
              <div class="uip-text-xl uip-text-bold uip-flex-grow">{{translations.newFolder}}</div>\
              <div class="">\
                 <span @click="ui.createNew.open = false"\
                  class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                     close\
                  </span>\
              </div>\
            </div>\
            <!-- END OF NEW FOLDER TITLE -->\
            <div class="uip-margin-bottom-s">\
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.name}}:</div>\
              <input class="uip-w-100p uip-standard-input" type="text" :placeholder="translations.folderName" style="padding: 5px 8px;"\
              v-model="ui.createNew.name">\
            </div>\
            <div class="uip-margin-bottom-m">\
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.color}}:</div>\
              <div class="uip-margin-bottom-xm uip-padding-xxs uip-border uip-border-round uip-w-200" style="padding: 5px 8px;">\
                <div class="uip-flex uip-flex-center">\
                  <span class="uip-margin-right-xs uip-text-muted uip-margin-right-s">\
                      <label class="uip-border-circle uip-h-18 uip-w-18 uip-border uip-display-block" v-bind:style="{\'background-color\' : ui.createNew.color}">\
                        <input\
                        type="color"\
                        v-model="ui.createNew.color" style="visibility: hidden;">\
                      </label>\
                  </span> \
                  <input v-model="ui.createNew.color" type="search" placeholder="#HEX" class="uip-blank-input uip-margin-right-s " style="min-width:0;">\
                  <span class="uip-text-muted">\
                      <span class="material-icons-outlined uip-text-muted">color_lens</span>\
                  </span>\
                </div>\
              </div>\
            </div>\
            <div class="">\
              <button @click="createFolder()" class="uip-button-default uip-w-100p uip-padding-xs" type="button">{{translations.create}}</button>\
            </div>\
          </div>\
        </div>\
      </div>\
      <!-- END OF MODAL GRID -->\
    </div>\
    <!-- END OF CREATE NEW FOLDER -->\
    <!-- EDIT FOLDER -->\
    <div v-if="ui.edit.open" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal"\
    :class="{\'uip-nothidden\' : ui.edit.open}" style="background: rgba(0, 0, 0, 0.3); z-index: 99999; top: 0px; left: 0px; right: 0px; max-height: 100vh;">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p uip-h-viewport">\
        <div class="uip-flex-grow" @click="ui.edit.open = false"></div>\
        <div class="uip-w-500 uip-background-default uip-padding-m uip-overflow-auto">\
          <div class="uk-width-xlarge uk-background-default uk-padding uk-overflow-auto" uk-height-viewport style="max-height: 100vh;">\
            <!-- NEW FOLDER TITLE -->\
            <div class="uip-flex uip-margin-bottom-m">\
              <div class="uip-text-xl uip-text-bold uip-flex-grow">{{translations.editFolder}}</div>\
              <div class="">\
                 <span @click="ui.edit.open = false"\
                  class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                     close\
                  </span>\
              </div>\
            </div>\
            <!-- END OF NEW FOLDER TITLE -->\
            <div class="uip-margin-bottom-s">\
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.name}}:</div>\
              <input class="uip-w-100p uip-standard-input" type="text" :placeholder="translations.folderName" style="padding: 5px 8px;"\
              v-model="ui.edit.active.title">\
            </div>\
            <div class="uip-margin-bottom-m">\
              <div class="uip-text-muted uip-margin-bottom-xs">{{translations.color}}:</div>\
              <div class="uip-margin-bottom-xm uip-padding-xxs uip-border uip-border-round uip-w-200" style="padding: 5px 8px;">\
                <div class="uip-flex uip-flex-center">\
                  <span class="uip-margin-right-xs uip-text-muted uip-margin-right-s">\
                      <label class="uip-border-circle uip-h-18 uip-w-18 uip-border uip-display-block" v-bind:style="{\'background-color\' : ui.edit.active.color}">\
                        <input\
                        type="color"\
                        v-model="ui.edit.active.color" style="visibility: hidden;">\
                      </label>\
                  </span> \
                  <input v-model="ui.edit.active.color" type="search" placeholder="#HEX" class="uip-blank-input uip-margin-right-s " style="min-width:0;">\
                  <span class="uip-text-muted">\
                      <span class="material-icons-outlined uip-text-muted">color_lens</span>\
                  </span>\
                </div>\
              </div>\
            </div>\
            <div class="">\
              <button @click="updateFolder()" class="uip-button-default uip-w-100p uip-padding-xs" type="button">{{translations.update}}</button>\
            </div>\
          </div>\
        </div>\
      </div>\
      <!-- END OF MODAL GRID -->\
    </div>\
    <!-- END OF EDIT FOLDER -->',
});

/////////////////////////
//CREATES USER FOLDERS
/////////////////////////
UIPfolder.component("create-folder", {
  props: {
    translations: Object,
    masterPrefs: Object,
    defaults: Object,
    preferences: Object,
    folderUpdate: Function,
    activeFolder: [String, Number],
    folder: Object,
    setOpenFolders: Function,
    openFolders: Array,
    refreshFolders: Function,
  },
  data: function () {
    return {
      loading: true,
      dragCounter: 0,
    };
  },
  mounted: function () {},
  methods: {
    isFolderOpen(folderid) {
      if (this.openFolders.includes(folderid)) {
        return true;
      } else {
        return false;
      }
    },
    startFolderDrag(evt, item) {
      evt.dataTransfer.dropEffect = "move";
      evt.dataTransfer.effectAllowed = "move";
      evt.dataTransfer.setData("itemID", item.id);
      evt.dataTransfer.setData("type", "folder");
      jQuery(".uploader-window").addClass("uip-hidden");
      jQuery(".uip-remove-folder").addClass("uip-nothidden");
    },
    addDropClass(evt, folder) {
      evt.preventDefault();
      target = evt.target;
      this.dragCounter++;
      if (jQuery(target).hasClass("uip-folder-can-drop")) {
        jQuery(target).addClass("uip-background-primary-wash");
      } else {
        jQuery(target).closest(".uip-folder-can-drop").addClass("uip-background-primary-wash");
      }
    },
    dragEnd(evt, folder) {
      jQuery(".uip-folder-can-drop").removeClass("uip-background-primary-wash");
      jQuery(".uploader-window").addClass("uip-opacity-0");
      jQuery(".uploader-window").removeClass("uip-hidden");
      jQuery(".uip-remove-folder").removeClass("uip-nothidden");
    },
    removeDropClass(evt, folder) {
      evt.preventDefault();
      target = evt.target;
      this.dragCounter--;

      if (this.dragCounter != 0) {
        return;
      }
      if (jQuery(target).hasClass("uip-folder-can-drop")) {
        jQuery(target).removeClass("uip-background-primary-wash");
      } else {
        jQuery(target).closest(".uip-folder-can-drop").removeClass("uip-background-primary-wash");
      }
    },
    dropInfolder(evt, folder) {
      this.dragCounter = 0;
      var itemID = evt.dataTransfer.getData("itemID");
      var dropItemType = evt.dataTransfer.getData("type");

      if (dropItemType == "folder") {
        this.moveFolder(itemID, folder.id);
      }
      if (dropItemType == "content") {
        this.moveContentToFolder(itemID, JSON.parse(folder.id));
      }

      jQuery(".uip-folder-can-drop").removeClass("uip-background-primary-wash");
      jQuery(".uploader-window").addClass("uip-opacity-0");
      jQuery(".uploader-window").removeClass("uip-hidden");
      jQuery(".uip-remove-folder").removeClass("uip-nothidden");

      setTimeout(function () {
        jQuery(".uploader-window").removeClass("uip-opacity-0");
      }, 1000);
    },
    moveFolder(folderiD, destinationId) {
      let self = this;
      data = {
        action: "uip_move_folder",
        security: uip_ajax.security,
        folderiD: folderiD,
        destinationId: destinationId,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;
          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error);
          } else {
            ///SOMETHING WENT RIGHT
            uipNotification(data.message);
            self.refreshFolders();
          }
        },
      });
    },
    moveContentToFolder(contentID, destinationId) {
      allIDs = JSON.parse(contentID);
      let self = this;
      data = {
        action: "uip_move_content_to_folder",
        security: uip_ajax.security,
        contentID: allIDs,
        destinationId: destinationId,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;
          if (data.error) {
            ///SOMETHING WENT WRONG
            uipNotification(data.error);
          } else {
            ///SOMETHING WENT RIGHT
            uipNotification(data.message);
            self.refreshFolders();
          }
        },
      });
    },
  },
  template:
    '<div>\
      <div :class="{\'uip-background-muted uip-text-bold uip-text-emphasis\' : activeFolder == folder.id}"\
      class="uip-border-round uip-flex uip-padding-xxs uip-text-m hover:uip-background-muted uip-margin-bottom-xxs uip-folder-can-drop"\
      @dragstart="startFolderDrag($event,folder)"\
      @dragend="dragEnd($event,folder)"\
      @drop="dropInfolder($event, folder)" \
      @dragenter="addDropClass($event, folder)"\
      @dragleave="removeDropClass($event, folder)"\
      @dragover.prevent\
      @dragenter.prevent draggable="true">\
        <span class="material-icons-outlined uip-margin-right-xxs" :style="{\'color\': folder.color}">folder</span>\
        <span class="uip-flex-grow uip-cursor-pointer" @click="folderUpdate(folder.id, folder)" >{{folder.title}}</span>\
        <span class="uip-border-round uip-background-primary-wash uip-padding-left-xxs uip-padding-right-xxs">{{folder.count}}</span>\
        <span class="uip-w-28 uip-text-right">\
          <span v-if="folder.subs && !isFolderOpen(folder.id)"\
          class="material-icons-outlined  uip-cursor-pointer" @click="setOpenFolders(folder.id)">chevron_right</span>\
          <span v-if="folder.subs && isFolderOpen(folder.id)"\
          class="material-icons-outlined  uip-cursor-pointer" @click="setOpenFolders(folder.id)">expand_more</span>\
        </span>\
      </div>\
      <!-- IF SUB -->\
      <div class="uip-margin-left-s" v-if="folder.subs && openFolders.includes(folder.id)">\
        <template v-for="sub in folder.subs">\
          <create-folder :refreshFolders="refreshFolders" :openFolders="openFolders" :setOpenFolders="setOpenFolders" :folder="sub" :translations="translations" :masterPrefs="masterPrefs" :defaults="defaults" :preferences="preferences"\
          :activeFolder="activeFolder" :folderUpdate="folderUpdate"></create-folder>\
        </template>\
      </div>\
    </div>',
});
/////////////////////////
//FETCHES THE ADMIN MENU
/////////////////////////
UIPfolder.component("premium-feature", {
  props: {
    translations: Object,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {},
  methods: {},
  template:
    '<span class="uip-padding-xxs uip-border-round uip-background-orange uip-text-bold uip-text-white uip-flex">\
	  <span class="material-icons-outlined uip-margin-right-xs">\
	  	card_giftcard\
	  </span>\
  	  <span>\
		{{translations.preFeature}}\
	  </span>\
  	</span>',
});

if (jQuery("#uip-folder-app").length > 0) {
  UIPfolder.mount("#uip-folder-app");
}
