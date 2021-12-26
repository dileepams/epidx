const UIPstylesOptions = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      translations: uipTranslations,
      masterPrefs: uipMasterPrefs,
      defaults: uipDefaults,
      preferences: uipUserPrefs,
      styles: [],
    };
  },
  watch: {},
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {
    formattedSettings() {
      return this.styles;
    },
  },
  mounted: function () {
    this.getOptions();
  },
  methods: {
    getScreenWidth() {
      this.screenWidth = window.innerWidth;
    },
    isSmallScreen() {
      if (this.screenWidth < 700) {
        return true;
      } else {
        return false;
      }
    },
    getOptions() {
      let self = this;
      data = {
        action: "uip_get_styles",
        security: uip_ajax.security,
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
            self.styles = data.styles;
          }
        },
      });
    },
    saveSettings() {
      let self = this;

      data = {
        action: "uip_save_styles",
        security: uip_ajax.security,
        options: self.styles,
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
            uipNotification(self.translations.somethingWrong);
          } else {
            ///SOMETHING WENT RIGHT
            uipNotification(self.translations.settingsSaved);
          }
        },
      });
    },
    clearSettings() {
      let options = this.formattedSettings;

      for (const key in options) {
        cat = options[key].options;
        for (let p = 0; p < cat.length; p++) {
          option = cat[p];
          if (Array.isArray(option.value)) {
            option.value = [];
          } else {
            option.value = "";
          }

          if ("darkValue" in option) {
            if (Array.isArray(option.darkValue)) {
              option.darkValue = [];
            } else {
              option.darkValue = "";
            }
          }
        }
      }
    },
    exportSettings() {
      self = this;
      ALLoptions = JSON.stringify(this.formattedSettings);

      var today = new Date();
      var dd = String(today.getDate()).padStart(2, "0");
      var mm = String(today.getMonth() + 1).padStart(2, "0"); //January is 0!
      var yyyy = today.getFullYear();

      date_today = mm + "_" + dd + "_" + yyyy;
      filename = "uip-styles-" + date_today + ".json";

      var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(ALLoptions);
      var dlAnchorElem = document.getElementById("uip-export-styles");
      dlAnchorElem.setAttribute("href", dataStr);
      dlAnchorElem.setAttribute("download", filename);
      dlAnchorElem.click();
    },
    importSettings() {
      self = this;

      var thefile = jQuery("#uip-import-settings")[0].files[0];

      if (thefile.type != "application/json") {
        uipNotification(self.translations.notValidJson);
        return;
      }

      if (thefile.size > 100000) {
        uipNotification(self.translations.fileToBig);
        return;
      }

      var file = document.getElementById("uip-import-settings").files[0];
      var reader = new FileReader();
      reader.readAsText(file, "UTF-8");

      reader.onload = function (evt) {
        json_settings = evt.target.result;
        parsed = JSON.parse(json_settings);

        if (parsed != null) {
          ///GOOD TO GO;
          self.styles = parsed;
          uipNotification(self.translations.stylesImported);
        } else {
          uipNotification(self.translations.somethingWrong);
        }
      };
    },
  },
};
const UIPstyles = uipVue.createApp(UIPstylesOptions);

/////////////////////////
//OUTPUTS UIPRESS SETTINGS
/////////////////////////
UIPstyles.component("output-options", {
  props: {
    translations: Object,
    alloptions: Object,
    activemodule: String,
    uipdata: Boolean,
  },
  data: function () {
    return {
      loading: true,
      settings: this.alloptions,
    };
  },
  watch: {
    alloptions: {
      handler(newValue, oldValue) {
        this.settings = newValue;
        this.formatStyles();
      },
      deep: true,
    },
  },
  mounted: function () {
    this.loading = false;
    this.formatStyles();
  },
  computed: {
    returnSettings() {
      return this.settings;
    },
  },
  methods: {
    formatStyles() {
      let styles = "";
      let globalStyles = "";
      let options = this.returnSettings;
      importurl = false;

      for (const key in options) {
        cat = options[key].options;
        for (let p = 0; p < cat.length; p++) {
          option = cat[p];

          if (option.cssVariable == "--uip-body-font-family") {
            if (option.value[0]) {
              formattedFont = "'" + option.value[0] + "', " + option.value[1];
              globalStyles = globalStyles + option.cssVariable + ":" + formattedFont + "!important;";

              fontURL = "https://fonts.googleapis.com/css2?family=" + option.value[0] + "&display=swap";
              formattedURL = fontURL.replace(" ", "%20");
              importurl = "@import url('" + formattedURL + "');";
            }
          } else if (option.value != "") {
            styles = styles + option.cssVariable + ":" + option.value + ";";

            if (option.global == true) {
              globalStyles = globalStyles + option.cssVariable + ":" + option.value + "!important;";
            }
          }
        }
      }

      styles = 'html:not([data-theme="dark"]) {' + styles;

      if (importurl) {
        styles = importurl + styles;
      }

      styles = styles + "}";

      styles = styles + 'html[data-theme="dark"] {';

      for (const key in options) {
        cat = options[key].options;
        for (let p = 0; p < cat.length; p++) {
          option = cat[p];
          if (option.darkValue) {
            styles = styles + option.cssVariable + ":" + option.darkValue + ";";
          }
        }
      }

      styles = styles + "}";

      styles = styles + "html {" + globalStyles + "}";
      jQuery("#uip-variable-preview").html(styles);
    },
    setDataFromComp(data, option) {
      console.log(option);
      option = data;
    },
    getdatafromComp(data) {
      return data;
    },
  },
  template:
    '<template v-for="(item, index) in returnSettings">\
      <div class="uip-text-l uip-text-emphasis uip-text-bold uip-margin-bottom-s uip-padding-s uip-background-muted uip-border-round uip-flex uip-flex-between">\
        <span>{{item.label}}</span>\
        <span class="material-icons-outlined uip-cursor-pointer" @click="item.hidden = false" v-if="item.hidden">chevron_left</span>\
        <span class="material-icons-outlined uip-cursor-pointer" @click="item.hidden = true" v-if="!item.hidden">expand_more</span>\
      </div>\
      <div class="uip-margin-bottom-l uip-padding-s" v-if="item.hidden != true">\
        <div class="uip-flex uip-margin-bottom-m">\
          <div class="uip-w-300"></div>\
          <div class="uip-w-200 uip-padding-left-l uip-text-bold uip-text-muted">{{translations.default}}</div>\
          <div class="uip-w-200 uip-padding-left-l uip-text-bold uip-text-muted">{{translations.darkMode}}</div>\
        </div>\
        <!-- OPTIONS BLOCK -->\
        <div  v-for="option in item.options"> \
          <!-- data connect -->\
          <div class="uip-flex uip-margin-bottom-xs" v-if="uipdata != true && option.premium == true">\
            <div class="uip-w-300">\
              <div class="uip-text-bold uip-text-m uip-margin-bottom-xs">{{option.name}}</div>\
            </div>\
            <div class="uip-w-200 uip-padding-left-l uip-margin-bottom-s">\
              <feature-flag :translations="translations"></feature-flag>\
            </div>\
          </div>\
          <!-- data connect -->\
          <div class="uip-flex uip-margin-bottom-xs" v-else>\
            <div class="uip-w-300">\
              <div class="uip-text-bold uip-text-m uip-margin-bottom-xs">{{option.name}}</div>\
            </div>\
            <!-- COLOR -->\
            <template v-if="option.type == \'color\'">\
              <div class="uip-w-200 uip-padding-left-l">\
              <div class="uip-margin-bottom-xm uip-padding-xxs uip-border uip-border-round uip-w-200 uip-background-default uip-border-box">\
                <div class="uip-flex uip-flex-center">\
                  <span class="uip-margin-right-xs uip-text-muted">\
                      <uip-color-dropdown @color-change="option.value = getdatafromComp($event)" :color="option.value"></uip-color-dropdown>\
                  </span> \
                  <input v-model="option.value" type="search" placeholder="#HEX" class="uip-blank-input uip-margin-right-s " style="min-width:0;">\
                  <span class="uip-text-muted">\
                      <span class="material-icons-outlined uip-text-muted">color_lens</span>\
                  </span> \
                </div>\
              </div>\
            </div>\
            <div class="uip-w-200 uip-padding-left-l">\
              <div class="uip-margin-bottom-xs uip-padding-xxs uip-border uip-border-round uip-w-200 uip-background-default uip-border-box" >\
                <div class="uip-flex uip-flex-center">\
                  <span class="uip-margin-right-xs uip-text-muted">\
                      <uip-color-dropdown @color-change="option.darkValue = getdatafromComp($event)" :color="option.darkValue"></uip-color-dropdown>\
                  </span> \
                  <input v-model="option.darkValue" type="search" placeholder="#HEX" class="uip-blank-input uip-margin-right-s " style="min-width:0;">\
                  <span class="uip-text-muted">\
                      <span class="material-icons-outlined uip-text-muted">color_lens</span>\
                  </span> \
                </div>\
              </div>\
            </div>\
            </template>\
            <!-- COLOR -->\
            <!-- FONT -->\
            <div v-if="option.type == \'font\'" class="uip-w-200 uip-padding-left-l">\
              <font-select :selected="option.value" name="font" placeholder="Search Fonts" ></font-select>\
            </div>\
            <!-- FONT -->\
            <!-- TEXT -->\
            <div v-if="option.type == \'text\'" class="uip-w-200 uip-padding-left-l uip-margin-bottom-xs">\
              <input type="text" class="uip-w-100p" v-model="option.value" placeholder="px / %" >\
            </div>\
            <!-- TEXT -->\
          </div>\
        </div>\
        <!-- END OPTIONS BLOCK -->\
      </div>\
    </template>',
});

UIPstyles.component("uip-color-dropdown", {
  props: ["color"],
  data: function () {
    return {
      modelOpen: false,
    };
  },
  computed: {
    areWeOpen() {
      return this.modelOpen;
    },
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
    getdatafromComp(data) {
      return data;
    },
    returnData(data) {
      this.$emit("color-change", data);
    },
  },
  template:
    '<div class="uip-position-relative">\
      <span @click="openThisComponent" class="uip-border-circle uip-h-18 uip-w-18 uip-border uip-display-block uip-cursor-pointer" v-bind:style="{\'background-color\' : color}">\
      </span>\
      <div v-if="areWeOpen" \
      class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-shadow uip-min-w-200 uip-z-index-9999">\
        <uip-color-picker @color-change="returnData(getdatafromComp($event))" :color="color"></uip-color-picker>\
      </div>\
    </div>',
});

UIPstyles.component("uip-color-picker", {
  props: {
    color: String,
  },
  data: function () {
    return {
      modelOpen: this.isOpen,
    };
  },
  computed: {
    areWeOpen() {
      return this.modelOpen;
    },
  },
  mounted: function () {
    //let thepicker = new iro.ColorPicker(this.$el);
    picker = this.$el.getElementsByClassName("uip-color-picker")[0];
    let self = this;

    startColor = "";

    if (self.color) {
      startColor = self.color;
    }
    var colorPicker = new iro.ColorPicker(picker, {
      // Set the size of the color picker
      width: 250,
      // Set the initial color to pure red
      color: startColor,
      layout: [
        {
          component: iro.ui.Box,
        },
        {
          component: iro.ui.Slider,
          options: {
            id: "hue-slider",
            sliderType: "hue",
          },
        },
        {
          component: iro.ui.Slider,
          options: {
            sliderType: "saturation",
          },
        },
        {
          component: iro.ui.Slider,
          options: {
            sliderType: "value",
          },
        },
        {
          component: iro.ui.Slider,
          options: {
            sliderType: "alpha",
          },
        },
      ],
    });

    colorPicker.on("color:change", function (color) {
      self.$emit("color-change", color.rgbaString);
    });
  },
  methods: {},
  template: '<div><div class="uip-color-picker"></div></div>',
});

/////////////////////////
//FONT SELECT
/////////////////////////

UIPstyles.component("font-select", {
  data: function () {
    return {
      fontSearch: "",
      options: [],
      allFontsData: [],
      ui: {
        dropOpen: false,
      },
    };
  },
  props: {
    selected: Array,
    name: String,
    placeholder: String,
  },
  watch: {
    fontSearch: function (newValue, oldValue) {
      this.options = this.filterIt(this.allFontsData, this.fontSearch);
    },
    options: function (newValue, oldValue) {
      currentOptions = this.options.slice(0, 20);

      for (let index = 0; index < currentOptions.length; ++index) {
        currentFont = currentOptions[index];
        var css = "@import url('https://fonts.googleapis.com/css2?family=" + currentFont.fontName + "&display=swap');";
        jQuery("<style/>").append(css).appendTo(document.head);
      }
    },
  },
  mounted: function () {
    //console.log(this.selected);
  },
  computed: {
    runitonce() {
      this.queryFonts();
    },
    allFonts() {
      this.runitonce;
      return this.options.slice(0, 30);
    },
  },
  methods: {
    queryFonts() {
      var self = this;

      jQuery.getJSON("https://www.googleapis.com/webfonts/v1/webfonts?sort=popularity&key=AIzaSyCsOWMT4eyd1vd4yN0-h7jZnXSCf2qDmio", function (fonts) {
        var filteredFonts = [];
        allfonts = fonts.items;
        formattedFonts = [];

        jQuery.each(allfonts, function (k, v) {
          temp = [];
          temp.fontName = v.family;
          temp.category = v.category;

          str = "";
          font = str.concat("'", temp.fontName, "', ", temp.category);

          temp.fontFamily = font;
          formattedFonts.push(temp);
        });

        listfonts = formattedFonts;
        self.allFontsData = listfonts;
        self.options = listfonts;
      });
    },
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    addSelected(selectedoption, options) {
      if (this.single == true) {
        options[0] = selectedoption;
      } else {
        options.push(selectedoption);
      }
    },
    filterIt(arr, searchKey) {
      return arr.filter(function (obj) {
        return Object.keys(obj).some(function (key) {
          return obj[key].toLowerCase().includes(searchKey.toLowerCase());
        });
      });
    },
    //////TITLE: REMOVES A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    removeSelected(option, options) {
      this.selected[0] = "";
      this.selected[1] = "";
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
    saveFont(font, chosen) {
      this.selected[0] = font.fontName;
      this.selected[1] = font.category;
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
    '<div class="uip-position-relative" @click="openThisComponent">\
      <div class="uip-margin-bottom-xs uip-padding-left-xxs uip-padding-right-xxs uip-padding-top-xxs uip-background-default uip-border uip-border-round uip-w-200 uip-cursor-pointer uip-h-32 uip-border-box"> \
        <div class=" uip-flex ">\
          <div class="uip-flex-grow">\
            <span v-if="!selected[0]" class="selected-item" style="background: none;">\
              <span class="uk-text-meta">Select {{name}}...</span>\
            </span>\
            <span v-if="selected[0]"  class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-padding-bottom-remove uip-padding-top-remove uip-display-inline-block uip-margin-right-xxs uip-margin-bottom-xxs">\
              <span>\
                {{selected[0]}}\
                <a class="uk-margin-small-left" href="#" @click="removeSelected(select,selected)">x</a>\
              </span>\
            </span>\
          </div>\
          <span class="material-icons-outlined uip-text-muted">expand_more</span>\
        </div>\
      </div>\
      <div v-if="ui.dropOpen" class="uip-position-absolute uip-padding-s uip-background-default uip-border-round uip-border uip-shadow uip-w-400 uip-border-box uip-z-index-9">\
        <div class="uip-flex uip-background-muted uip-padding-xxs uip-margin-bottom-s uip-border-round">\
          <span class="material-icons-outlined uip-text-muted uip-margin-right-xs">search</span>\
          <input class="uip-blank-input uip-flex-grow" type="text" \
          :placeholder="placeholder" v-model="fontSearch">\
        </div>\
        <div class="uip-h-200 uip-overflow-auto">\
          <ul v-for="option in allFonts">\
            <li @click="saveFont(option, selected)" class="uip-text-l" v-bind:style="{ \'font-family\': option.fontFamily}">\
              <span class="uip-link-muted uip-no-underline">{{option.fontName}}</span>\
            </li>\
          </ul>\
        </div>\
      </div>\
    </div>',
});

/////////////////////////
//FETCHES THE ADMIN MENU
/////////////////////////
UIPstyles.component("feature-flag", {
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
    '<a href="https://uipress.co/pricing/" target="_BLANK" class="uip-no-underline uip-border-round uip-background-primary-wash uip-text-bold uip-text-emphasis uip-display-inline-block" style="padding: var(--uip-padding-button)">\
      <div class="uip-flex">\
        <span class="material-icons-outlined uip-margin-right-xs">redeem</span> \
        <span>{{translations.preFeature}}</span>\
      </div> \
    </a>',
});

if (jQuery("#uip-styles").length > 0) {
  UIPstyles.mount("#uip-styles");
}
