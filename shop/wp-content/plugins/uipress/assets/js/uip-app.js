const uipTranslations = JSON.parse(uip_ajax.translations);
const uipMasterPrefs = JSON.parse(uip_ajax.masterPrefs);
const uipDefaults = JSON.parse(uip_ajax.defaults);
const uipUserPrefs = JSON.parse(uip_ajax.preferences);

const uipNetwork = uip_ajax.network;

function uipNotification(message) {
  var notiArea = document.getElementById("notification-drop");
  if (!notiArea) {
    var notiArea = document.createElement("div");
    notiArea.setAttribute("id", "notification-drop");
    document.body.appendChild(notiArea);
  }
  var elemDiv = document.createElement("div");
  elemDiv.classList.add("uip-notification");
  elemDiv.innerHTML = message;
  notiArea.appendChild(elemDiv);
  setTimeout(function () {
    elemDiv.remove();
  }, 8000);
}

function uip_check_for_updates() {
  jQuery.ajax({
    url: uip_ajax.ajax_url,
    type: "post",
    data: {
      action: "uip_check_for_updates",
      security: uip_ajax.security,
    },
    success: function (response) {
      if (response) {
        data = JSON.parse(response);
        if (data.error) {
          uipNotification(data.message, "danger");
        } else {
          uipNotification(data.message, "success");
          location.reload();
        }
      }
    },
  });
}

function importOldSettings(network) {
  uipNotification(uipTranslations.importStarted, "danger");
  jQuery.ajax({
    url: uip_ajax.ajax_url,
    type: "post",
    data: {
      action: "uip_import_old_settings",
      security: uip_ajax.security,
      network: network,
    },
    success: function (response) {
      if (response) {
        data = JSON.parse(response);
        if (data.error) {
          uipNotification(data.message, "danger");
        } else {
          uipNotification(data.message, "success");
          //location.reload();
          location.reload();
        }
      }
    },
  });
}
function hideImportSettings(network) {
  jQuery.ajax({
    url: uip_ajax.ajax_url,
    type: "post",
    data: {
      action: "uip_hide_import_old_settings",
      security: uip_ajax.security,
      network: network,
    },
    success: function (response) {
      if (response) {
        data = JSON.parse(response);
        if (data.error) {
          uipNotification(data.message, "danger");
        } else {
          uipNotification(data.message, "success");
          //location.reload();
          location.reload();
        }
      }
    },
  });
}

const UIPadminMenuOptions = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      translations: uipTranslations,
      masterPrefs: uipMasterPrefs,
      userPreferences: uipUserPrefs,
      appDefaults: uipDefaults,
    };
  },
  watch: {},
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {
    returnDefaults() {
      return this.appDefaults;
    },
  },
  mounted: function () {
    window.setInterval(() => {
      ///TIMED FUNCTIONS
    }, 15000);
    this.getScreenWidth();
  },
  methods: {
    getScreenWidth() {
      this.screenWidth = window.innerWidth;
      if (this.isSmallScreen()) {
        jQuery("#adminmenumain").addClass("uip-hidden");
        this.appDefaults.mobile = true;
      } else {
        jQuery("#adminmenumain").removeClass("uip-hidden");
        this.appDefaults.mobile = false;
      }
    },
    isSmallScreen() {
      if (this.screenWidth < 900) {
        return true;
      } else {
        return false;
      }
    },
    loaded() {
      this.loading = false;
    },
  },
  template:
    '<menu-loader-placeholder v-if="loading"></menu-loader-placeholder>\
  <get-menu @menu-loaded="loaded()"\
  :translations="translations"\
  :appPrefs="userPreferences" \
  :appDefaults="returnDefaults"\
  :appOptions="masterPrefs"></get-menu>',
};
const UIPmenu = uipVue.createApp(UIPadminMenuOptions);

/////////////////////////
//FETCHES THE ADMIN MENU
/////////////////////////
UIPmenu.component("get-menu", {
  emits: ["menu-loaded", "menuLoaded"],
  props: {
    translations: Object,
    appPrefs: Object,
    appDefaults: Object,
    appOptions: Object,
  },
  data: function () {
    return {
      masterMenu: [],
      preferences: [],
    };
  },
  mounted: function () {
    //this.getMenu();
    this.$emit("menu-loaded");
    this.masterMenu = this.processCustom(uipMasterMenu);
    this.preferences = uipMasterMenu.prefs;
  },
  computed: {
    formatPrefs() {
      return this.preferences;
    },
  },
  methods: {
    updatePrefs(sentdata) {
      if (this.appOptions.dataConnect != true) {
        return;
      }

      let self = this;
      this.preferences = sentdata;
      let tempPrefs = {};

      for (var key of Object.keys(self.preferences)) {
        tempPrefs[key] = self.preferences[key];
      }
      data = {
        action: "uip_save_prefs",
        security: uip_ajax.security,
        userPref: tempPrefs,
      };
      jQuery.ajax({
        url: uip_ajax.ajax_url,
        type: "post",
        data: data,
        success: function (response) {
          data = JSON.parse(response);
          if (data.error) {
            ///SOMETHING WENT WRONG
            console.log(response);
          } else {
            ///SOMETHING WENT RIGHT
            console.log(response);
          }
        },
      });
    },
    processCustom(masterMenu) {
      if (masterMenu.customMenu != "true") {
        return masterMenu;
      }

      let activeLink = jQuery("#adminmenu a[aria-current='page']").attr("href");
      let original = masterMenu.menu;

      if (masterMenu.customMenu == "true" && activeLink) {
        for (i = 0; i < original.length; i++) {
          let item = original[i];
          let link = "";

          if (item.type == "sep") {
            continue;
          }

          if (item.href && item.href != "") {
            link = item.href;
            item.url = link;
          } else {
            link = item.url;
          }

          if (link != "") {
            link = link.replace(/&amp;/g, "&");
            item.url = link;
          }

          if (link == "admin.php?page=admin_2020_content") {
            item.url = "admin.php?page=uip-content";
            link = item.url;
          }
          if (link == "options-general.php?page=admin-2020-menu-creator") {
            item.url = "options-general.php?page=uip-menu-creator";
            link = item.url;
          }

          if (link == "options-general.php?page=admin2020-settings") {
            item.url = "options-general.php?page=uip-settings";
            link = item.url;
          }

          if (link == activeLink) {
            item.active = true;
          }

          if (item.submenu && item.submenu.length > 0) {
            let subActiveCount = 0;
            for (p = 0; p < item.submenu.length; p++) {
              let subItem = item.submenu[p];
              let sublink = "";

              if (subItem.type == "sep") {
                continue;
              }

              name = subItem.name.toLowerCase();

              if (subItem.href && subItem.href != "") {
                sublink = subItem.href;
                subItem.url = sublink;
              } else {
                sublink = subItem.url;
              }

              if (sublink == "admin.php?page=admin_2020_content") {
                subItem.url = "admin.php?page=uip-content";
                sublink = subItem.url;
              }
              if (sublink == "options-general.php?page=admin-2020-menu-creator") {
                subItem.url = "options-general.php?page=uip-menu-creator";
                sublink = subItem.url;
              }

              if (sublink == "options-general.php?page=admin2020-settings") {
                subItem.url = "options-general.php?page=uip-settings";
                sublink = subItem.url;
              }

              if (sublink != "") {
                sublink = sublink.replace(/&amp;/g, "&");
                subItem.url = sublink;
              }

              if (sublink == activeLink) {
                subActiveCount += 1;
                subItem.active = true;
              }
            }

            if (item.active == true && subActiveCount == 0) {
              item.active = false;
            }

            if (subActiveCount > 0) {
              item.active = true;
            }
          }
        }
      }

      return masterMenu;
    },
  },
  template:
    '<toolbar-logo :defaults="appDefaults" :options="appOptions" :translations="translations" :preferences="formatPrefs"></toolbar-logo>\
    <build-menu  :appDefaults="appDefaults" :appPrefs="appOptions" :preferences="formatPrefs" :translations="translations" :masterMenu="masterMenu"></build-menu>\
  	<build-options :appDefaults="appDefaults" :appPrefs="appOptions" :preferences="formatPrefs" :updateFunc="updatePrefs" :translations="translations"></build-options>',
});

/////////////////////////
//STARTS ADMIN MENU BUILD
/////////////////////////
UIPmenu.component("build-menu", {
  props: {
    masterMenu: Object,
    translations: Object,
    preferences: Object,
    appPrefs: Object,
    appDefaults: Object,
  },
  data: function () {
    return {
      loading: true,
      searchString: "",
    };
  },
  computed: {
    menuWithSearch() {
      let original = this.masterMenu.menu;
      let tempmenu = Object.assign([], original);
      let filterMenu = [];

      if (this.searchString.length > 0) {
        let searchString = this.searchString.toLowerCase();

        //LOOP THROUGH MENU
        for (i = 0; i < tempmenu.length; i++) {
          let tempSub = [];
          let menuitem = tempmenu[i];
          if (menuitem.type == "sep") {
            continue;
          }
          //CHECK SUB ITEMS FIRST
          if (menuitem.submenu) {
            for (p = 0; p < menuitem.submenu.length; p++) {
              let subItem = menuitem.submenu[p];
              name = subItem.name.toLowerCase();
              if (name.includes(searchString)) {
                tempSub.push(subItem);
              }
            }
          }
          if (tempSub.length > 0) {
            menuitem.active = true;
            menuitem.submenu = tempSub;
            pos = filterMenu.push(menuitem);
          } else {
            //CHECK PARENT
            name = menuitem.name.toLowerCase();
            if (name.includes(searchString)) {
              filterMenu.push(menuitem);
            }
          }
        }
        //RETURN TEMP MENU
        return filterMenu;
      } else {
        filterMenu = original;
      }

      return filterMenu;
    },
  },
  mounted: function () {
    this.loading = false;
  },
  methods: {
    showSubMenu(menuItem, ev) {
      if (this.preferences.showSubmenuHover || this.preferences.menuShrunk) {
        menuItem.hover = true;
      }
      const left = this.$el.getBoundingClientRect().left;
      const top = this.$el.getBoundingClientRect().top;
      //console.log(ev.target);
    },
    hideSubMenu(menuItem) {
      if (this.preferences.showSubmenuHover || this.preferences.menuShrunk) {
        menuItem.hover = false;
      }
    },
    showSearch() {
      if (this.appPrefs.menu.options["search-enabled"].value == true) {
        return false;
      }

      if (this.preferences.hideSearch == true) {
        return false;
      }

      if (this.preferences.menuShrunk == true) {
        if (this.appDefaults.mobile == true) {
          return true;
        }
        return false;
      }

      return true;
    },
    isShrunkMenu() {
      if (this.appDefaults.mobile == true) {
        return false;
      }
      if (this.preferences.menuShrunk == true || this.preferences.menuShrunk == "true") {
        return true;
      } else {
        return false;
      }
    },
    getItemUrl(item) {
      return item.url;
    },
    hrefTarget(item) {
      if (item && item != "") {
        if (item == "1" || item == true || item == "true") {
          return "_BLANK";
        } else {
          return "";
        }
      } else {
        return "";
      }
    },
    makeActive(item) {
      if (this.preferences.showSubmenuHover != true && !this.isShrunkMenu()) {
        item.active = !item.active;
      }
      if (this.preferences.showSubmenuHover && this.appDefaults.mobile == true) {
        item.active = !item.active;
      }
    },
    showNormalSub(item) {
      if (item.submenu && item.active && this.preferences.showSubmenuHover != true && !this.isShrunkMenu()) {
        return true;
      } else {
        if (item.submenu && item.active && this.appDefaults.mobile == true) {
          return true;
        } else {
          return false;
        }
      }
    },
    showHoverSub(item) {
      if (item.submenu && item.submenu.length > 0 && item.hover && this.appDefaults.mobile != true && (this.preferences.showSubmenuHover || this.isShrunkMenu())) {
        return true;
      } else {
        return false;
      }
    },
    formatClases(item) {
      let classes = "";
      if ("toplevel_page_jetpack" == item.id) {
        classes = classes + " toplevel_page_jetpack";
      }

      if (item.classes) {
        allClasses = item.classes;
        brokenClasses = allClasses.split(" ");

        for (var i = 0; i < brokenClasses.length; i++) {
          singleclass = brokenClasses[i];
          if (singleclass.includes("ame-menu") || singleclass.includes("ame-has-custom-dashicon")) {
            classes = classes + " " + singleclass;
          }
        }
      }

      if (item.userClasses) {
        classes = classes + " " + item.userClasses;
      }

      return classes;
    },
    formatLinkClases(item) {
      let classes = "";

      if (item.classes) {
        allClasses = item.classes;
        brokenClasses = allClasses.split(" ");

        for (var i = 0; i < brokenClasses.length; i++) {
          singleclass = brokenClasses[i];
          if (singleclass.includes("ame-menu") || singleclass.includes("ame-has-custom-dashicon")) {
            classes = classes + " " + singleclass;
          }
        }
      }

      if (item.userClasses) {
        classes = classes + " " + item.userClasses;
      }

      return classes;
    },
  },
  template:
    '<div class="uip-body-font uip-menu-padding uip-flex-grow uip-overflow-auto" id="uip-menu-content">\
	  <div v-if="showSearch()" class="uip-margin-bottom-m uip-padding-xxs uip-background-muted uip-border-round">\
	  	<div class="uip-flex uip-flex-center">\
  			<span class="uip-margin-right-xs uip-text-normal">\
  			  <span class="material-icons-outlined">manage_search</span>\
  			</span> \
  			<input type="search" :placeholder="translations.searchMenu" class="uip-blank-input uip-min-width-0 uip-flex-grow"  v-model="searchString">\
  		</div>\
	  </div>\
	  <div>\
		<template v-for="item in menuWithSearch">\
      <!-- SEP -->\
			<div v-if="item.type == \'sep\' && !item.name" class="uip-margin-m"></div>\
      <div v-if="item.type == \'sep\' && item.name && !isShrunkMenu()" class="uip-margin-bottom-xxs uip-padding-xxs uip-margin-top-s uip-text-bold uip-text-emphasis">{{item.name}}</div>\
      <div v-if="item.type == \'sep\' && item.name && isShrunkMenu()" class="uip-margin-m"></div>\
      <!-- NORMAL MENU -->\
			<li class="uip-margin-remove" v-if="item.type != \'sep\'" @mouseover="showSubMenu(item, $event)" @mouseleave="hideSubMenu(item)" :class="formatClases(item)">\
				<div class="uip-margin-bottom-xxs uip-padding-xxs uip-border-round hover:uip-background-muted"\
				:class="{\'uip-background-muted \' : item.active}">\
					<div class="uip-flex uip-flex-center">\
						<a :href="getItemUrl(item)" v-if="preferences.hideIcons != true" class="uip-margin-right-xs uip-link-muted" \
            :class="[{\'uip-text-emphasis\' : item.active}, formatLinkClases(item)]"  v-html="item.icon" :target="hrefTarget(item.blankPage)"></a>\
						<a :href="getItemUrl(item)" :class="{\'uip-text-emphasis \' : item.active}" class="uip-text-bold uip-link-muted"\
						v-if="!isShrunkMenu()" v-html="item.name" :target="hrefTarget(item.blankPage)"></a>\
						<span v-if="item.submenu && item.submenu.length > 0 && !isShrunkMenu() && (!preferences.showSubmenuHover || appDefaults.mobile == true)" \
            @click="makeActive(item)" class="uip-cursor-pointer uip-flex-grow uip-text-right" :class="{\'uip-text-emphasis \' : item.active}">\
							<span v-if="!item.active" class="material-icons-outlined">chevron_left</span>\
							<span v-if="item.active" class="material-icons-outlined">expand_more</span>\
						</span>\
            <span v-if="item.submenu && item.submenu.length > 0 && !isShrunkMenu() && preferences.showSubmenuHover && appDefaults.mobile != true" \
            @click="makeActive(item)" class="uip-cursor-pointer uip-flex-grow uip-text-right" :class="{\'uip-text-emphasis \' : item.active}">\
              <span class="material-icons-outlined">chevron_right</span>\
            </span>\
					</div>\
				</div>\
				<!-- NORMAL SUB MENU -->\
				<div v-if="showNormalSub(item)" class="uip-padding-top-xs uip-margin-bottom-s uip-sub-menu" style="margin-left:3px;"\
				:class="{\'uip-padding-left-xs \' : preferences.hideIcons, \'uip-padding-left-m \' : preferences.hideIcons != true}">\
					<template v-for="subitem in item.submenu">\
						<div class="uip-margin-bottom-xxs" :class="subitem.userClasses">\
							<a :target="hrefTarget(subitem.blankPage)" :href="getItemUrl(subitem)" :class="{\'uip-text-emphasis uip-text-bold\' : subitem.active}" class="uip-link-muted" v-html="subitem.name"></a>\
						</div>\
					</template>\
				</div>\
				<!-- HOVER MENU -->\
        <uip-menu-dropdown v-if="showHoverSub(item)" >\
          <template v-for="subitem in item.submenu">\
            <div class="uip-margin-bottom-xxs" :class="subitem.userClasses">\
              <a :target="hrefTarget(subitem.blankPage)" :href="getItemUrl(subitem)" :class="{\'uip-text-emphasis uip-text-bold\' : subitem.active}" class="uip-link-muted" v-html="subitem.name"></a>\
            </div>\
          </template>\
        </uip-menu-dropdown>\
      </li>\
		</template>\
	  </div>\
  </div>',
});

UIPmenu.component("uip-menu-dropdown", {
  props: {},
  data: function () {
    return {
      modelOpen: false,
    };
  },
  mounted: function () {
    this.getTop;
  },
  computed: {
    getTop() {
      self = this;
      returnDatat = 0;
      ///SET TOP
      let POStop = self.$el.getBoundingClientRect().top;
      let POSright = self.$el.getBoundingClientRect().right + 10;
      returnDatat = POStop + "px";

      //CHECK FOR OFFSCREEN

      submenu = self.$el.getElementsByClassName("uip-sub-menu")[0];
      let rect = submenu.getBoundingClientRect();

      submenu.setAttribute("style", "top:" + returnDatat + ";left:" + POSright + "px");

      if (rect.bottom > (window.innerHeight - 50 || document.documentElement.clientHeight - 50)) {
        // Bottom is out of viewport
        submenu.setAttribute("style", "top: " + (POStop - rect.height + 30) + "px;" + "left:" + POSright + "px");
      }
    },
  },
  methods: {},
  template:
    '<div class="uip-w-100p uip-min-w-28">\
      <div class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-shadow uip-drop-right uip-w-150 uip-sub-menu" >\
          <slot></slot>\
      </div>\
    </div>',
});

UIPmenu.component("toolbar-logo", {
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
  computed: {
    menuCollapsed() {
      return this.preferences.menuShrunk;
    },
    isDarkMode() {
      if (this.preferences.darkmode == true || this.preferences.darkmode == "true") {
        return true;
      } else {
        return false;
      }
    },
  },
  methods: {
    getLogo() {
      //CHECK FOR SMALL LOGO IN COLLAPSED MENU
      if (this.menuCollapsed == true || this.menuCollapsed == "true") {
        if (this.options.menu.options["light-logo-collapsed"].value) {
          return this.options.menu.options["light-logo-collapsed"].value;
        }
      }
      if (this.options.menu.options["light-logo"].value) {
        return this.options.menu.options["light-logo"].value;
      } else {
        return this.defaults.logo;
      }
    },
    getDarkLogo() {
      //CHECK FOR SMALL LOGO IN COLLAPSED MENU
      if (this.menuCollapsed == true || this.menuCollapsed == "true") {
        if (this.options.menu.options["dark-logo-collapsed"].value) {
          return this.options.menu.options["dark-logo-collapsed"].value;
        }
      }
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
      if (this.options.menu.options["show-site-logo"].value == true && !this.menuCollapsed) {
        return true;
      }
      return false;
    },
  },
  template:
    '<div class="uip-flex uip-flex-center uip-menu-padding uip-margin-bottom-s" style="padding-bottom:0;">\
        <div class="">\
            <a v-if="!loading" :href="defaults.adminHome" class="uip-no-outline">\
                <img class="uip-display-block uip-menu-logo-height uip-light-logo" :src="getLogo()">\
                <img class="uip-display-block uip-menu-logo-height uip-dark-logo" :src="getDarkLogo()">\
            </a>\
            <a v-if="loading" href="#">\
                <div class="uip-border-circle uip-background-muted" style="height:35px;width:35px;"></div>\
            </a>\
        </div>\
        <div v-if="showTitle()" class="uip-margin-left-s uip-text-bold uip-text-m uip-body-font">\
          {{defaults.siteName}}\
        </div>\
    </div>',
});

/////////////////////////
//STARTS ADMIN MENU BUILD
/////////////////////////
UIPmenu.component("build-options", {
  emits: ["updateprefs"],
  props: {
    translations: Object,
    updateFunc: Function,
    preferences: Object,
    appPrefs: Object,
    appDefaults: Object,
  },
  data: function () {
    return {
      loading: true,
      ui: {
        options: false,
      },
      user: {
        prefs: this.preferences,
      },
    };
  },
  watch: {
    preferences: function (newValue, oldValue) {
      this.user.prefs = newValue;
    },
  },
  computed: {
    isMobile() {
      return this.appDefaults.mobile;
    },
  },
  mounted: function () {
    this.loading = false;
  },
  methods: {
    onClickOutside(event) {
      const path = event.path || (event.composedPath ? event.composedPath() : undefined);
      // check if the MouseClick occurs inside the component
      if (path && !path.includes(this.$el) && !this.$el.contains(event.target)) {
        this.closeThisComponent(); // whatever method which close your component
      }
    },
    openThisComponent() {
      this.ui.options = this.ui.options != true; // whatever codes which open your component
      // You can also use Vue.$nextTick or setTimeout
      requestAnimationFrame(() => {
        document.documentElement.addEventListener("click", this.onClickOutside, false);
      });
    },
    closeThisComponent() {
      this.ui.options = false; // whatever codes which close your component
      document.documentElement.removeEventListener("click", this.onClickOutside, false);
    },
    toggleMenuFold() {
      let folded = this.user.prefs.menuShrunk;
      if (folded) {
        jQuery("html").attr("menu-folded", false);
        this.user.prefs.menuShrunk = false;
        this.updateFunc(this.user.prefs);
      } else {
        jQuery("html").attr("menu-folded", true);
        this.user.prefs.menuShrunk = true;
        this.updateFunc(this.user.prefs);
      }
    },
  },
  template:
    '<div class="uip-body-font uip-padding-s">\
		 <div class="uip-background-muted uip-border-round uip-flex uip-flex-center uip-flex-between" :class="{\'uip-padding-xxs\' : preferences.menuShrunk != true}">\
			 <span @click="toggleMenuFold()" class="material-icons-outlined uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer"\
			 :class="{\'uip-rotate-180\' : preferences.menuShrunk}" v-if="isMobile != true">menu_open</span>\
			 <div class="uip-position-relative" v-if="preferences.menuShrunk != true">\
				 <span @click="openThisComponent" class="material-icons-outlined uip-padding-xxs uip-border-round hover:uip-background-grey uip-cursor-pointer">more_horiz</span>\
				 <div v-if="ui.options" class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-shadow uip-drop-top uip-w-250">\
				 	<div class="uip-margin-bottom-m uip-flex uip-flex-center">\
						<div class="uip-text-bold uip-margin-right-s ">{{translations.menuPreferences}}</div>\
						<feature-flag v-if="appPrefs.dataConnect != true" :translations="translations"></feature-flag>\
					</div>\
					<!-- HIDE SEARCH BAR -->\
				 	<div class="uip-margin-bottom-s uip-flex uip-flex-between">\
						 <span class="uip-text-muted uip-margin-right-s">{{translations.hideSearchBar}}</span>\
						 <label class="uip-switch" :class="{\'uip-disabled\' : appPrefs.dataConnect != true}">\
						   <input type="checkbox" v-model="user.prefs.hideSearch" @change="updateFunc(user.prefs)">\
						   <span class="uip-slider"></span>\
						 </label>\
				    </div>\
				    <!-- HIDE ICONS -->\
					<div class="uip-margin-bottom-s uip-flex uip-flex-between">\
						<span class="uip-text-muted uip-margin-right-s">{{translations.hideIcons}}</span>\
						<label class="uip-switch" :class="{\'uip-disabled\' : appPrefs.dataConnect != true}">\
						  <input type="checkbox" v-model="user.prefs.hideIcons" @change="updateFunc(user.prefs)">\
						  <span class="uip-slider"></span>\
						</label>\
				    </div>\
					<!-- SHOW SUBMENU ON HOVER -->\
					<div class="uip-flex uip-flex-between">\
						<span class="uip-text-muted uip-margin-right-s">{{translations.showSubmenuHover}}</span>\
						<label class="uip-switch" :class="{\'uip-disabled\' : appPrefs.dataConnect != true}">\
						  <input type="checkbox" v-model="user.prefs.showSubmenuHover" @change="updateFunc(user.prefs)">\
						  <span class="uip-slider"></span>\
						</label>\
					</div>\
				 </div>\
			 </div>\
		 </div>\
	  </div>',
});

/////////////////////////
//FETCHES THE ADMIN MENU
/////////////////////////
UIPmenu.component("feature-flag", {
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

UIPmenu.component("menu-loader-placeholder", {
  props: {
    translations: Object,
  },
  data: function () {
    return {};
  },
  mounted: function () {},
  methods: {},
  template:
    '<div class="uip-max-w-100p uip-overflow-hidden">\
    <div  class="uip-padding-s">\
      <div class="uip-flex uip-flex-row uip-margin-bottom-s">\
        <div>\
          <svg height="28" width="28">\
            <circle cx="14" cy="14" r="14" stroke-width="0" fill="#bbbbbb2e"></circle>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-flex uip-flex-row uip-padding-top-xs">\
        <div>\
          <svg height="28" width="200">\
            <rect width="200" height="28" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
    </div>\
    <div  class="uip-padding-left-s uip-padding-top-xs">\
      <div class="uip-flex uip-flex-row uip-padding-xxs">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="80">\
            <rect width="80" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-margin-m"></div>\
      <div class="uip-flex uip-flex-row uip-padding-xxs" style="padding-top: 0;">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="140">\
            <rect width="140" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-flex uip-flex-row uip-padding-xxs">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="50">\
            <rect width="50" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-flex uip-flex-row uip-padding-xxs">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="77">\
            <rect width="77" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-flex uip-flex-row uip-padding-xxs">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="107">\
            <rect width="107" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-margin-m"></div>\
      <div class="uip-flex uip-flex-row uip-padding-xxs" style="padding-top: 0;">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="87">\
            <rect width="87" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
      <div class="uip-flex uip-flex-row uip-padding-xxs" style="padding-top: 0;">\
        <div>\
          <svg class="uip-margin-right-xs" height="20" width="20">\
            <rect width="20" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
        <div>\
          <svg height="20" width="47">\
            <rect width="47" height="20" rx="4" fill="#bbbbbb2e"></rect>\
          </svg>\
        </div>\
      </div>\
    </div>\
  </div>',
});

if (jQuery("#uip-admin-menu").length > 0) {
  UIPmenu.mount("#uip-admin-menu");
}
