const UIPtoolbarOptions = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      translations: uipTranslations,
      masterPrefs: uipMasterPrefs,
      defaults: uipDefaults,
      userPreferences: uipUserPrefs,
      uipToolbar: "",
    };
  },
  watch: {},
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {},
  mounted: function () {
    window.setInterval(() => {
      ///TIMED FUNCTIONS
    }, 15000);
    this.loading = false;
  },
  methods: {
    showLegacy() {
      if (this.userPreferences.legacy_admin_links == true || this.masterPrefs.toolbar.options["legacy-admin"].value == true) {
        return false;
      }
      return true;
    },
    getScreenWidth() {
      this.screenWidth = window.innerWidth;
    },
    isSmallScreen() {
      if (this.screenWidth < 900) {
        return true;
      } else {
        return false;
      }
    },
    toggleMenu() {
      jQuery("#adminmenumain").toggleClass("uip-mobile-menu");
    },
  },
};
const UIPtoolbar = uipVue.createApp(UIPtoolbarOptions);

UIPtoolbar.component("toolbar-logo", {
  props: {
    defaults: Object,
    options: Object,
    translations: Object,
    preferences: Object,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {
    this.loading = false;
  },
  methods: {
    getLogo() {
      if (this.options.menu.options["light-logo"].value) {
        return this.options.menu.options["light-logo"].value;
      } else {
        return this.defaults.logo;
      }
    },
    getDarkLogo() {
      if (this.options.menu.options["dark-logo"].value) {
        return this.options.menu.options["dark-logo"].value;
      } else {
        return this.defaults.darkLogo;
      }
    },
    isTrue(thetest) {
      if (thetest == "true" || thetest == true) {
        return true;
      }
      if (thetest == "false" || thetest == false || thetest == "") {
        return false;
      }
    },
    showTitle() {
      if (this.options.menu.options["show-site-logo"].value == true) {
        return true;
      }
      return false;
    },
  },
  template:
    '<div class="uip-flex uip-flex-center">\
        <div class="uip-margin-right-s">\
            <a v-if="!loading" :href="defaults.adminHome" class="uip-no-outline">\
                <img v-if="preferences.darkmode != true" class="uip-display-block uip-h-28 uip-max-h-28" :src="getLogo()">\
                <img v-if="preferences.darkmode" class="uip-display-block uip-h-28 uip-max-h-28" :src="getDarkLogo()">\
            </a>\
            <a v-if="loading" href="#">\
                <div class="uip-border-circle uip-background-muted" style="height:35px;width:35px;"></div>\
            </a>\
        </div>\
        <div v-if="showTitle()" class="uip-margin-right-m uip-text-bold uip-text-m uip-body-font">\
          {{defaults.siteName}}\
        </div>\
    </div>',
});

UIPtoolbar.component("toolbar-search", {
  props: {
    defaults: Object,
    options: Object,
    translations: Object,
    preferences: Object,
  },
  data: function () {
    return {
      loading: true,
      search: {
        open: false,
        term: "",
        perPage: 20,
        currentPage: 1,
        results: [],
        totalFound: 0,
        categorized: [],
        nothingFound: false,
      },
    };
  },
  mounted: function () {
    this.loading = false;
  },
  computed: {
    searchedCats() {
      return this.search.categorized;
    },
  },
  methods: {
    masterSearch() {
      adminbar = this;
      searchString = this.search.term;
      perpage = this.search.perPage;
      currentpage = this.search.currentPage;
      this.search.loading = true;
      this.search.nothingFound = false;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_master_search",
          security: uip_ajax.security,
          search: searchString,
          perpage: perpage,
          currentpage: currentpage,
        },
        success: function (response) {
          adminbar.search.loading = false;
          if (response) {
            data = JSON.parse(response);
            if (data.error) {
              UIkit.notification(data.error_message, "danger");
            } else {
              adminbar.search.results = data.founditems;
              adminbar.search.totalPages = data.totalpages;
              adminbar.search.totalFound = data.totalfound;
              adminbar.search.categorized = data.categorized;

              if (data.totalpages == 0) {
                adminbar.search.nothingFound = true;
                return;
              }

              if (adminbar.search.currentPage > data.totalpages) {
                adminbar.search.currentPage = 1;
                adminbar.masterSearch();
              }
            }
          }
        },
      });
    },
    loadMoreResults() {
      perpage = this.search.perPage;
      this.search.perPage = Math.floor(perpage * 3);
      this.masterSearch();
    },
    openSearch() {
      if (document.activeElement) {
        document.activeElement.blur();
      }
      this.search.open = true;
    },
    closeSearch() {
      if (document.activeElement) {
        document.activeElement.blur();
      }
      this.search.open = false;
    },
    isEnabled() {
      search = this.options.toolbar.options["search-disabled"].value;

      if (search == "true" || search === true) {
        return false;
      }

      return true;
    },
  },
  template:
    '<div v-if="isEnabled()" class="uip-flex uip-flex-center">\
       <span @click="openSearch()"\
       class="material-icons-outlined uip-background-icon uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
          search\
       </span>\
    </div>\
    <div v-if="search.open" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal" \
    style="background:rgba(0,0,0,0.3);z-index:99999;top:0;left:0;right:0;max-height:100vh" \
    :class="{\'uip-nothidden\' : search.open}">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p">\
        <div class="uip-flex-grow" @click="closeSearch()" ></div>\
        <div class="uip-w-500 uip-background-default uip-padding-m uip-overflow-auto " >\
          <div class="" style="max-height: 100vh;">\
            <!-- SEARCH TITLE -->\
            <div class="uip-flex uip-margin-bottom-s">\
              <div class="uip-text-xl uip-text-bold uip-flex-grow">{{translations.search}}</div>\
              <div class="">\
                 <span @click="search.open = false"\
                  class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                     close\
                  </span>\
              </div>\
            </div>\
            <!-- CHECK CONNECTION -->\
            <div v-if="options.dataConnect != true" class="uip-margin-bottom-s">\
                <a href="https://uipress.co/pricing/" target="_BLANK" class="uip-no-underline uip-padding-xs uip-border-round uip-background-primary-wash uip-text-bold uip-text-emphasis uip-display-inline-block uip-display-block uip-w-100p uip-border-box">\
                    <div class="uip-flex">\
                      <span class="material-icons-outlined uip-margin-right-xs">redeem</span>\
                      <span>\
                        {{translations.preFeature}}\
                      </span>\
                    </div>\
                    <p class="uip-text-normal">{{translations.unlockSearch}}</p>\
                </a>\
            </div>\
            <!-- CHECK CONNECTION -->\
            <!-- SEARCH -->\
            <div class="uip-margin-bottom-m uip-padding-xs uip-background-muted uip-border-round">\
              <div class="uip-flex uip-flex-center">\
                <span class="uip-margin-right-xs uip-text-muted">\
                  <span class="material-icons-outlined">search</span>\
                </span> \
                <input type="search" :placeholder="translations.search" class="uip-blank-input uip-flex-grow" \
                v-on:keyup.enter="masterSearch()"\
                v-model="search.term" autofocus>\
              </div>\
            </div>\
            <!-- SEARCH RESULTS -->\
            <loading-placeholder v-if="search.loading"></loading-placeholder>\
            <loading-placeholder v-if="search.loading"></loading-placeholder>\
            <div v-if="search.nothingFound" class="uip-flex uip-flex-middle uip-flex-center uip-h-150">\
              <span class="uip-text-muted">{{translations.nothingFound}}</span>\
            </div>\
            <template v-for="cat in searchedCats" v-if="!search.loading">\
              <div class="uip-text-m uip-text-muted uip-border-round uip-text-bold uip-background-muted uip-padding-xs uip-margin-bottom-s" >{{cat.label}}</div>\
              <div class="uip-margin-bottom-s uip-padding-xs">\
                <template v-for="foundItem in cat.found" v-if="!search.loading">\
                  <div class="uip-margin-bottom-s">\
                    <div class="uip-flex uip-flex-middle">\
                      <div class="uip-margin-right-xs">\
                        <img v-if="foundItem.image" :src="foundItem.image" style="height:26px;border-radius: 4px;">\
                        <span v-if="foundItem.attachment && !foundItem.image" class="uip-background-primary-wash uip-padding-xxs uip-text-s uip-border-round">{{foundItem.mime}}</span>\
                        <span v-if="!foundItem.attachment && !foundItem.image" class="uip-background-primary-wash uip-padding-xxs uip-text-s uip-border-round" :class="foundItem.status" style="display: block;">{{foundItem.status}}</span>\
                      </div>\
                      <div class="uip-flex-grow uip-margin-right-xs uip-flex uip-flex-center">\
                        <a class="uip-link-muted uip-no-underline uip-no-outline" :href="foundItem.editUrl" v-html="foundItem.name"></a>\
                        <div>\
                        </div>\
                      </div>\
                      <div class="uip-margin-right-xs">\
                        <a :href="foundItem.editUrl"\
                        class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-link-muted uip-no-underline uip-no-outline">\
                           edit_note\
                        </a>\
                      </div>\
                      <div class="uip-margin-right-xs">\
                        <a :href="foundItem.url"\
                        class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-link-muted uip-no-underline uip-no-outline">\
                           pageview\
                        </a>\
                      </div>\
                    </div>\
                  </div>\
                </template>\
              </div>\
            </template>\
            <!-- LOAD MORE -->\
            <div v-if="search.totalPages > 1" class="uip-margin-bottom-s">\
              <button class="uip-button-secondary" @click="loadMoreResults">\
                <span>{{translations.showMore}}</span>\
                <span>({{search.totalFound - search.results.length}}</span>\
                <span>{{translations.otherMatches}})</span>\
              </button>\
            </div>\
          </div>\
        </div>\
      </div>\
    </div>',
});

UIPtoolbar.component("toolbar-create", {
  props: {
    defaults: Object,
    options: Object,
    translations: Object,
    preferences: Object,
  },
  data: function () {
    return {
      loading: true,
      create: {
        open: false,
        types: [],
        loading: false,
      },
    };
  },
  mounted: function () {
    this.loading = false;
  },
  computed: {
    postTypes() {
      return this.create.types;
    },
  },
  methods: {
    getPostTypes() {
      adminbar = this;
      adminbar.create.loading = true;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_get_create_types",
          security: uip_ajax.security,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            adminbar.create.loading = false;
            if (data.error) {
              UIkit.notification(data.error_message, "danger");
            } else {
              adminbar.create.types = data.types;
            }
          }
        },
      });
    },
    openSearch() {
      let self = this;
      if (self.create.types.length === 0) {
        self.getPostTypes();
      }
      this.create.open = true;
    },
    closeSearch() {
      if (document.activeElement) {
        document.activeElement.blur();
      }
      this.create.open = false;
    },
    isEnabled() {
      search = this.options.toolbar.options["new-enabled"].value;

      if (search == "true" || search === true) {
        return false;
      }

      return true;
    },
  },
  template:
    '<div v-if="isEnabled()" class="uip-flex uip-flex-center uip-margin-left-s">\
       <div @click="openSearch()"\
       class="uip-background-dark uip-padding-xxs uip-padding-left-xs uip-padding-right-xs  uip-border-round hover:uip-background-secondary uip-cursor-pointer uip-flex uip-flex-center uip-text-inverse">\
          <span>{{translations.create}}</span>\
          <span class="material-icons-outlined">chevron_right</span>\
       </div>\
    </div>\
    <div v-if="create.open" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal" \
    style="background:rgba(0,0,0,0.3);z-index:99999;top:0;left:0;right:0;max-height:100vh" \
    :class="{\'uip-nothidden\' : create.open}">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p">\
        <div class="uip-flex-grow" @click="closeSearch()" ></div>\
        <div class="uip-w-500 uip-background-default uip-padding-m" >\
          <div  style="max-height: 100vh;">\
            <!-- CREATE TITLE -->\
            <div class="uip-flex uip-margin-bottom-m">\
              <div class="uip-text-xl uip-text-bold uip-flex-grow">{{translations.create}}</div>\
              <div class="">\
                 <span @click="create.open = false"\
                  class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                     close\
                  </span>\
              </div>\
            </div>\
            <!-- SEARCH RESULTS -->\
            <loading-placeholder v-if="create.loading"></loading-placeholder>\
            <loading-placeholder v-if="create.loading"></loading-placeholder>\
            <div class="">\
              <template v-for="type in postTypes">\
                <a :href="type.href" class="uip-flex uip-flex-middle uip-flex-center uip-margin-bottom-xs uip-padding-xxs hover:uip-background-muted uip-border-round uip-link-muted uip-no-underline" >\
                  <span class="uip-background-dark uip-text-inverse uip-border-round uip-margin-right-s uip-padding-xxs uip-flex uip-flex-center">\
                    <span v-if="type.icon" class="dashicons uip-h-18 uip-w-18" :class="type.icon"></span>\
                    <span  v-if="!type.icon" class="material-icons-outlined uip-h-18 uip-w-18">post_add</span>\
                  </span>\
                  <span class="uip-flex-grow uip-text-bold" style="font-size:16px;">{{type.name}}</span>\
                  <span class="material-icons-outlined">chevron_right</span>\
                </a>\
              </template>\
            </div>\
          </div>\
        </div>\
      </div>\
    </div>',
});

UIPtoolbar.component("toolbar-offcanvas", {
  emits: ["updateprefs"],
  props: {
    defaults: Object,
    options: Object,
    translations: Object,
    preferences: Object,
  },
  data: function () {
    return {
      loading: true,
      settings: {
        defaults: this.defaults,
        translations: this.translations,
        preferences: this.preferences,
      },
      panel: {
        open: false,
      },
      updates: {
        allUpdates: [],
        loading: false,
        updateCount: 0,
      },
      notices: {
        allNotices: [],
        formatted: [],
        loading: false,
        noticeCount: 0,
        supressed: [],
        suppressedForPage: 0,
      },
      prefs: this.preferences,
    };
  },
  watch: {
    "prefs.darkmode": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.uip_save_preferences("darkmode", newValue, false);
        this.returnPrefs();

        if (newValue == true) {
          jQuery("html").attr("data-theme", "dark");
        } else {
          jQuery("html").attr("data-theme", "light");
        }
      }
    },
    "prefs.screen_options": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.returnPrefs();
        this.uip_save_preferences("screen_options", newValue, false);
      }
    },
    "prefs.legacy_admin_links": function (newValue, oldValue) {
      if (newValue != oldValue) {
        this.returnPrefs();
        this.uip_save_preferences("legacy_admin_links", newValue, false);
      }
    },
  },
  mounted: function () {
    this.loading = false;
  },
  computed: {
    allUpdates() {
      return this.updates.allUpdates;
    },
    formatNotices() {
      let toolbar = this;
      data = jQuery.parseHTML(toolbar.notices.allNotices);
      notis = [];
      supressed = toolbar.notices.supressed;
      toolbar.notices.suppressedForPage = 0;

      jQuery(data).each(function () {
        temp = [];

        text = jQuery(this).text().trim().substring(0, 40);
        html = jQuery(this).prop("outerHTML");

        if (html) {
          if (!supressed.includes(text)) {
            temp["type"] = "primary";
            if (html.includes("notice-error")) {
              temp["type"] = "errormsg";
            }
            if (html.includes("notice-warning")) {
              temp["type"] = "warning";
            }
            if (html.includes("notice-success")) {
              temp["type"] = "success";
            }
            if (html.includes("notice-info")) {
              temp["type"] = "info";
            }

            temp["content"] = html;
            temp["shortDes"] = text;
            temp["open"] = false;
            notis.push(temp);
          } else {
            toolbar.notices.suppressedForPage += 1;
          }
        }
      });
      toolbar.notices.formatted = notis;
      toolbar.notices.noticeCount = notis.length;
      return toolbar.notices.formatted;
    },
  },
  methods: {
    uip_save_preferences(pref, value, notification = null) {
      if (pref == "") {
        return;
      }

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uip_save_user_prefs",
          security: uip_ajax.security,
          pref: pref,
          value: value,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            if (data.error) {
              uipNotification(data.error_message, "danger");
            } else {
              uipNotification(data.message, "success");
            }
          }
        },
      });
    },
    isDisabled(optionName) {
      notifications = this.options.toolbar.options[optionName].value;

      if (notifications == "true" || notifications === true) {
        return false;
      }

      return true;
    },
    getNoticeClass(noticetype) {
      if (noticetype == "info" || noticetype == "primary") {
        return "uip-background-primary-wash";
      }
      if (noticetype == "warning") {
        return "uip-background-orange-wash";
      }
      if (noticetype == "errormsg") {
        return "uip-background-red-wash";
      }
      if (noticetype == "success") {
        return "uip-background-green-wash";
      }
    },
    hideNotification(des) {
      this.notices.supressed.push(des);
      this.uip_save_preferences("uip-supressed-notifications", this.notices.supressed, false);
      uipNotification(this.settings.translations.notificationHidden, "success");
    },
    showAllNotifications() {
      this.notices.supressed = [];
      this.uip_save_preferences("uip-supressed-notifications", [""], false);
    },
    returnPrefs() {
      data = this.prefs;
      this.$emit("updateprefs", data);
    },
    openOffcanvas() {
      let self = this;
      if (self.updates.allUpdates.length === 0) {
        self.getUpdates();
        self.getNotices();
      }
      self.panel.open = true;
    },
    getUpdates() {
      adminbar = this;
      adminbar.updates.loading = true;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_get_updates",
          security: uip_ajax.security,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            adminbar.updates.loading = false;
            if (data.error) {
              UIkit.notification(data.error_message, "danger");
            } else {
              adminbar.updates.allUpdates = data.updates;
              adminbar.updates.updateCount = data.total;
            }
          }
        },
      });
    },
    getNotices() {
      adminbar = this;
      adminbar.notices.loading = true;

      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_get_notices",
          security: uip_ajax.security,
        },
        success: function (response) {
          if (response) {
            data = JSON.parse(response);
            adminbar.notices.loading = false;
            if (data.error) {
              UIkit.notification(data.error_message, "danger");
            } else {
              console.log(data);
              adminbar.notices.allNotices = data.notices;
              adminbar.notices.supressed = data.supressed;
            }
          }
        },
      });
    },
    isfront() {
      front = this.settings.defaults.front;
      if (front == "true" || front === true) {
        return true;
      }
      return false;
    },
    showLegacy() {
      if (this.options.toolbar.options["legacy-admin"].value == true) {
        return false;
      }
      return true;
    },
    getTarget() {
      newtab = this.options.toolbar.options["view-new-tab"].value;

      if (newtab == true || newtab == "true") {
        return "_BLANK";
      }

      return "_self";
    },
  },
  template:
    '<div class="uip-flex uip-flex-center uip-margin-left-s" style="height:100%">\
      <div @click="openOffcanvas()"\
      class="uip-background-primary uip-border-circle uip-w-28 uip-h-28 hover:uip-background-primary-dark uip-flex uip-flex-center uip-flex-middle">\
        <span v-if="!settings.defaults.user.img" class="uip-text-inverse uip-text-m uip-no-select uip-line-height-0" >{{defaults.user.initial}}</span>\
        <img v-if="settings.defaults.user.img" class="uip-border-circle uip-w-100p" :src="settings.defaults.user.img">\
      </div>\
    </div>\
    <div v-if="panel.open" class="uip-position-fixed uip-w-100p uip-h-viewport uip-hidden uip-text-normal" \
    style="background:rgba(0,0,0,0.3);z-index:99999;top:0;left:0;right:0;max-height:100vh" \
    :class="{\'uip-nothidden\' : panel.open}">\
      <!-- MODAL GRID -->\
      <div class="uip-flex uip-w-100p">\
        <div class="uip-flex-grow" @click="panel.open = false" ></div>\
        <div class="uip-w-500 uip-background-default uip-padding-m" >\
          <div class="uip-text-normal"  style="max-height: 100vh;">\
            <!-- MODAL TITLE -->\
            <div class="uip-flex uip-flex-middle uip-margin-bottom-m">\
              <div class="uip-margin-right-s">\
                <div class="uip-border-circle uip-flex uip-flex-middle uip-flex-center uip-text-inverse uip-w-40 uip-h-40 uip-overflow-hidden" :class="{\'uip-background-primary\' : !settings.defaults.user.img}">\
                  <span v-if="!settings.defaults.user.img" class="uip-text-inverse uip-text-l  uip-line-height-0" >{{settings.defaults.user.initial}}</span>\
                  <img v-if="settings.defaults.user.img" :src="settings.defaults.user.img" style="width:100%;">\
                </div>\
              </div>\
              <div class="uip-flex-grow">\
                <div class="uip-text-l uip-text-bold uip-overflow-hidden uip-text-ellipsis uip-max-w-200" style="line-height:1">{{settings.defaults.user.username}}</div>\
                <div class="uip-text-muted uip-overflow-hidden uip-text-ellipsis uip-max-w-200">{{settings.defaults.user.email}}</div>\
              </div>\
              <div class="">\
                <div @click="panel.open = false"\
                 class="material-icons-outlined uip-background-muted uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">\
                    close\
                 </div>\
              </div>\
            </div>\
            <div v-if="panel.loading" >\
              <loading-placeholder ></loading-placeholder>\
            </div>\
            <!-- QUICK LINKS BLOCK -->\
            <div  class="uip-flex uip-margin-bottom-s" >\
              <div class="uip-w-50p uip-padding-right-s">\
                <a v-if="!isfront()" :href="settings.defaults.siteHome" :target="getTarget()"\
                class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs uip-background-muted hover:uip-background-grey uip-border-round uip-link-muted uip-no-underline uip-text-m uip-flex-left">\
                  <span class="material-icons-outlined uip-margin-right-s">launch</span>\
                  <span class="uip-text-bold ">{{settings.translations.viewSite}}</span>\
                </a>\
                <a v-if="isfront()" :href="settings.defaults.adminHome"\
                  class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs uip-background-muted hover:uip-background-grey uip-border-round uip-link-muted uip-no-underline uip-text-m uip-flex-left">\
                  <span class="material-icons-outlined uip-margin-right-s">launch</span>\
                  <span class="uip-text-bold ">{{settings.translations.viewDashboard}}</span>\
                </a>\
              </div>\
              <div class="uip-w-50p">\
                <a :href="settings.defaults.logOut"\
                class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs uip-background-muted hover:uip-background-grey uip-border-round uip-link-muted uip-no-underline uip-text-m uip-flex-left">\
                  <span class="material-icons-outlined uip-margin-right-s">logout</span>\
                  <span class="uip-text-bold ">{{settings.translations.logOut}}</span>\
                </a>\
              </div>\
            </div>\
            <!-- UPDATE BLOCK -->\
            <div class="" v-if="updates.updateCount > 0">\
              <!-- UPDATE HEADER -->\
              <div class="uip-flex uip-flex-middle uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round" >\
                <div class="uip-text-m uip-text-bold uip-flex-grow">\
                  {{settings.translations.updates}}\
                </div>\
                <div v-if="updates.updateCount > 0" class="uip-background-orange-wash  uip-text-orange uip-text-bold uip-border-round  uip-padding-left-xxs uip-padding-right-xxs uip-text-s uip-border-round uip-text-orange">\
                  {{updates.updateCount}}\
                </div>\
              </div>\
              <!-- UPDATE LIST -->\
              <loading-placeholder v-if="updates.loading"></loading-placeholder>\
              <div class="uip-margin-bottom-s">\
                <template v-if="!updates.loading" v-for="cat in allUpdates">\
                  <a :href="cat.href" class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs hover:uip-background-muted uip-border-round uip-link-muted uip-no-underline">\
                    <span class="material-icons-outlined uip-margin-right-s">{{cat.icon}}</span>\
                    <div class="uip-flex-grow">\
                      {{cat.title}}\
                    </div>\
                    <div v-if="cat.total > 0" class="uip-background-orange-wash uip-text-orange uip-text-bold uip-border-round  uip-padding-left-xxs uip-padding-right-xxs uip-text-s uip-border-round">\
                      {{cat.total}}\
                    </div>\
                    <div v-if="cat.total == 0" class="">\
                      <span class="material-icons-outlined uip-text-green">check_circle</span>\
                    </div>\
                  </a>\
                </template>\
              </div>\
            </div>\
            <!-- PREFERENCES BLOCK -->\
            <div class="uip-margin-bottom-s">\
              <!-- PREFS HEADER -->\
              <div class="uip-flex uip-flex-middle uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round">\
                <div class="uip-text-m uip-text-bold uip-flex-grow">\
                  {{settings.translations.preferences}}\
                </div>\
              </div>\
              <!-- PREFS LIST -->\
              <div>\
                <!-- DARK MODE -->\
                <div class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs">\
                  <span class="material-icons-outlined uip-margin-right-s">dark_mode</span>\
                  <div class="uip-flex-grow">\
                    {{settings.translations.darkMode}}\
                  </div>\
                  <div class="">\
                    <label class="uip-switch">\
                      <input type="checkbox" v-model="prefs.darkmode">\
                      <span class="uip-slider"></span>\
                    </label>\
                  </div>\
                </div>\
                <!-- SCREEN OPTIONS -->\
                <div class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs">\
                  <span class="material-icons-outlined uip-margin-right-s">tune</span>\
                  <div class="uip-flex-grow">\
                    {{settings.translations.showScreenOptions}}\
                  </div>\
                  <div class="">\
                    <label class="uip-switch">\
                      <input type="checkbox" v-model="prefs.screen_options">\
                      <span class="uip-slider"></span>\
                    </label>\
                  </div>\
                </div>\
                <!-- LEGACY LINKS OPTIONS -->\
                <div class="uip-flex uip-flex-middle uip-flex-center uip-padding-xs"\
                v-if="showLegacy()">\
                  <span class="material-icons-outlined uip-margin-right-s">link_off</span>\
                  <div class="uip-flex-grow">\
                    {{settings.translations.hideLegacy}}\
                  </div>\
                  <div class="">\
                    <label class="uip-switch">\
                      <input type="checkbox" v-model="prefs.legacy_admin_links">\
                      <span class="uip-slider"></span>\
                    </label>\
                  </div>\
                </div>\
              </div>\
            </div>\
            <!-- NOTICES BLOCK -->\
            <div v-if="isDisabled(\'notification-center-disabled\') && formatNotices.length > 0" >\
              <!-- NOTICE HEADER -->\
              <div class="uip-flex uip-flex-middle uip-margin-bottom-s uip-background-muted uip-border-rounded uip-padding-xs uip-border-round" >\
                <div class="uip-text-m uip-text-bold uip-flex-grow">\
                  {{settings.translations.notifications}}\
                </div>\
                <div v-if="formatNotices.length > 0" class="uip-background-orange-wash uip-text-orange uip-text-bold uip-border-round  uip-padding-left-xxs uip-padding-right-xxs uip-text-s">\
                  {{formatNotices.length}}\
                </div>\
              </div>\
              <!-- NOTICES LIST -->\
              <div class="" v-if="options.dataConnect != true">\
                <a href="https://uipress.co/pricing/" target="_BLANK" class="uip-no-underline uip-padding-xs uip-border-round uip-background-primary-wash uip-text-bold uip-text-emphasis uip-display-inline-block uip-display-block">\
                    <div class="uip-flex">\
                      <span class="material-icons-outlined uip-margin-right-xs">redeem</span>\
                      <span>\
                        {{translations.preFeature}}\
                      </span>\
                    </div>\
                    <p class="uip-text-normal">{{translations.unlockNotificationCenter}}</p>\
                </a>\
              </div>\
              <div v-if="options.dataConnect == true">\
                <loading-placeholder v-if="notices.loading"></loading-placeholder>\
                <template v-if="!notices.loading" v-for="notice in formatNotices">\
                  <div class="uip-background-muted uip-border-round uip-margin-bottom-s uip-padding-xs">\
                    <div class="uip-flex">\
                      <span class="uip-margin-right-s uip-border-circle uip-h-18 uip-w-18" :class="getNoticeClass(notice.type)"></span>\
                      <div class="uip-text-bold uip-flex-grow" v-html="notice.shortDes">\
                      </div>\
                      <span v-if="!notice.open" @click="notice.open = true" class="material-icons-outlined uip-cursor-pointer">chevron_left</span>\
                      <span v-if="notice.open" @click="notice.open = false" class="material-icons-outlined uip-cursor-pointer">expand_more</span>\
                    </div>\
                    <div v-if="notice.open" class="uip-margin-top-xs">\
                      <button @click="hideNotification(notice.shortDes)" class="uip-button-secondary">{{settings.translations.hideNotification}}</button>\
                    </div>\
                    <div v-if="notice.open" class="uip-margin-top-xs">\
                      <div v-html="notice.content"></div>\
                    </div>\
                  </div>\
                </template>\
                <div v-if="notices.suppressedForPage > 0" >\
                  <span>{{notices.suppressedForPage}} {{settings.translations.hiddenNotification}}</span>\
                  <a href="#" @click="showAllNotifications()" >{{settings.translations.showAll}}</a>\
                </div>\
              </div>\
            </div>\
          </div>\
        </div>\
      </div>\
    </div>',
});

UIPtoolbar.component("toolbar-links", {
  props: {
    defaults: Object,
    options: Object,
    translations: Object,
    preferences: Object,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {
    this.loading = false;
  },
  computed: {},
  methods: {
    toggleScreenMeta() {
      jQuery("#screen-meta").toggleClass("uip-show-so");
    },

    isEnabled() {
      search = this.options.toolbar.options["view-enabled"].value;

      if (search == "true" || search === true) {
        return false;
      }

      return true;
    },
    isfront() {
      front = this.defaults.front;
      if (front == "true" || front === true) {
        return true;
      }
      return false;
    },
    showScreenOptions() {
      let screen = this.preferences.screen_options;
      if (screen == "true" || screen === true) {
        if (!this.isfront()) {
          return true;
        } else {
          return false;
        }
      }
      return false;
    },
    getTarget() {
      newtab = this.options.toolbar.options["view-new-tab"].value;

      if (newtab == true || newtab == "true") {
        return "_BLANK";
      }

      return "_self";
    },
  },
  template:
    '<div class="uip-flex uip-flex-center" style="height:100%">\
      <a v-if="isEnabled() && !isfront()" :href="defaults.siteHome" :target="getTarget()"\
      class="material-icons-outlined uip-background-icon uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-toolbar-link uip-no-underline uip-no-outline uip-margin-left-xs">\
       house\
      </a>\
      <a v-if="isEnabled() && isfront()" :href="defaults.adminHome"\
      class="material-icons-outlined uip-background-icon uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-toolbar-link uip-no-underline uip-no-outline uip-margin-left-xs">\
        dashboard\
      </a>\
      <span v-if="showScreenOptions()"\
      @click="toggleScreenMeta()" \
      class="material-icons-outlined uip-background-icon uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer uip-margin-left-xs">\
        tune\
      </span>\
    </div>',
});

/////////////////////////
//ADDS FEATURE TAG///////
/////////////////////////
UIPtoolbar.component("feature-flag", {
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

/////////////////////////
//ADDS DROPDOWN//////////
/////////////////////////
UIPtoolbar.component("uip-dropdown", {
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
      if (this.pos == "botton-right") {
        return "uip-margin-top-s uip-left-0";
      }
      if (this.pos == "full-screen") {
        return "uip-margin-top-s uip-left-0 uip-right-0";
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
    '<div class="">\
      <div class="">\
        <div v-if="type == \'icon\'" @click="openThisComponent" class="uip-background-icon uip-border-round hover:uip-background-grey uip-cursor-pointer  material-icons-outlined" type="button" :class="getPaddingClass()">{{icon}}</div>\
        <button v-if="type == \'button\'" @click="openThisComponent" class="uip-button-default" :class="[getPaddingClass(), getPrimaryClass() ]" type="button">{{translation}}</button>\
      </div>\
      <div v-if="modelOpen" :class="getClass()"\
      class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-shadow uip-min-w-200 uip-z-index-9999">\
        <slot></slot>\
      </div>\
    </div>',
});
/////////////////////////
//LOADING PLACEHOLDER///////
/////////////////////////
UIPtoolbar.component("loading-placeholder", {
  props: {
    settings: Object,
  },
  data: function () {
    return {
      loading: true,
    };
  },
  mounted: function () {
    this.loading = false;
  },
  methods: {},
  template:
    '<svg role="img" width="400" height="200" style="width:100%" aria-labelledby="loading-aria" viewBox="0 0 400 200" preserveAspectRatio="none">\
      <title id="loading-aria">Loading...</title>\
      <rect x="0" y="0" width="100%" height="100%" clip-path="url(#clip-path)" style=\'fill: url("#fill");\'></rect>\
      <defs>\
        <clipPath id="clip-path">\
          <rect x="0" y="18" rx="2" ry="2" width="211" height="16" />\
          <rect x="0" y="47" rx="2" ry="2" width="120" height="16" />\
          <rect x="279" y="47" rx="2" ry="2" width="120" height="16" />\
          <rect x="0" y="94" rx="2" ry="2" width="211" height="16" />\
          <rect x="0" y="123" rx="2" ry="2" width="120" height="16" />\
          <rect x="279" y="123" rx="2" ry="2" width="120" height="16" />\
          <rect x="0" y="173" rx="2" ry="2" width="211" height="16" />\
          <rect x="0" y="202" rx="2" ry="2" width="120" height="16" />\
          <rect x="279" y="202" rx="2" ry="2" width="120" height="16" />\
        </clipPath>\
        <linearGradient id="fill">\
          <stop offset="0.599964" stop-color="#bbbbbb2e" stop-opacity="1">\
            <animate attributeName="offset" values="-2; -2; 1" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
          </stop>\
          <stop offset="1.59996" stop-color="#bbbbbb2e" stop-opacity="1">\
            <animate attributeName="offset" values="-1; -1; 2" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
          </stop>\
          <stop offset="2.59996" stop-color="#bbbbbb2e" stop-opacity="1">\
            <animate attributeName="offset" values="0; 0; 3" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
          </stop>\
        </linearGradient>\
      </defs>\
  </svg>',
});

if (jQuery("#uip-toolbar").length > 0) {
  UIPtoolbar.mount("#uip-toolbar");
}
