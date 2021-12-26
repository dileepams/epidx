export function moduleName() {
  return "country-visits";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
      editingMode: Boolean,
      premium: Boolean,
      analytics: Boolean,
    },
    data: function () {
      return {
        tableData: {},
        loading: true,
        numbers: [],
        startDate: this.dateRange.startDate,
        countries: Object,
        GAaccount: this.analytics,
        cardOptions: this.cardData,
      };
    },
    mounted: function () {
      this.loading = false;
      this.getData();
    },
    watch: {
      dateRange: function (newValue, oldValue) {
        if (newValue.startDate != oldValue.startDate || newValue.endDate != oldValue.endDate) {
          this.getData();
        }
      },
      cardOptions: {
        handler(newValue, oldValue) {
          this.$emit("card-change", newValue);
        },
        deep: true,
      },
    },
    computed: {
      getTheDates() {
        return this.dateRange;
      },
      isGAconnected() {
        return this.analytics;
      },
      getPostsOnce() {
        this.getPosts();
      },
      formattedPosts() {
        this.getPostsOnce;
        return this.recentPosts;
      },
      getTheType() {
        let self = this;
        if (self.cardOptions.chartType) {
          return self.cardOptions.chartType;
        } else {
          return "line";
        }
      },
      daysDif() {
        self = this;
        var b = moment(self.dateRange.startDate);
        var a = moment(self.dateRange.endDate);
        return a.diff(b, "days");
      },
    },
    methods: {
      getData() {
        let self = this;
        self.loading = true;

        if (!self.isGAconnected) {
          self.loading = false;
          return;
        }

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_analytics_get_country_visits",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.noaccount) {
              ///SOMETHING WENT WRONG
              self.GAaccount = false;
              self.loading = false;
              return;
            }

            self.GAaccount = true;
            self.loading = false;
            self.tableData = responseData.dataSet;
            self.countries = responseData.countrieData;
          },
        });
      },
      defaultImage(e) {
        jQuery(e.target).replaceWith('<span class="material-icons-outlined uip-margin-right-s">flag</span>');
      },
    },
    template:
      '<div class="uip-padding-s uip-position-relative" :accountConnected="isGAconnected">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
        <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
        <div v-if="loading != true && !editingMode  && isGAconnected" class="uip-overflow-auto">\
          <uip-country-chart v-if="cardOptions.hideMap != \'true\'" :translations="translations" :cdata="countries" :dates="dateRange"></uip-country-chart>\
          <div class="uip-w-100p uip-min-w-300 uip-overflow-auto">\
            <div class="uip-flex uip-margin-bottom-s">\
                    <div class="uip-text-muted uip-text-bold uip-flex-grow">{{translations.country}}</div>\
                    <div class="uip-text-muted uip-text-bold uip-margin-left-s uip-w-80 uip-text-right">{{translations.visits}}</div>\
                    <div class="uip-text-muted uip-text-bold uip-margin-left-s uip-w-100 uip-text-right">{{translations.change}}</div>\
            </div>\
            <div>\
                <div v-for="item in tableData" class="uip-flex uip-flex-center uip-margin-bottom-s">\
                    <div class="uip-flex-grow uip-flex uip-overflow-hidden uip-text-ellipsis uip-no-wrap">\
                      <img :src="item.flag" alt="flag" @error="defaultImage" style="width:20px;margin-right:10px;border-radius:2px;">\
                      <span>{{item.name}}</span>\
                    </div>\
                    <div class="uip-margin-left-s uip-w-80 uip-text-right uip-text-bold uip-flex-no-shrink">{{item.visits}}</div>\
                    <div class="uip-margin-left-s uip-w-100 uip-text-right uip-flex uip-flex-right uip-flex-no-shrink">\
                      <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex"\
                      :class="{\'uip-background-red-wash\' : item.change < 0}">\
                        <span v-if="item.change > 0" class="material-icons-outlined uip-margin-right-xxs">expand_less</span>\
                        <span v-if="item.change < 0" class="material-icons-outlined uip-margin-right-xxs">expand_more</span>\
                        <span>{{item.change}}%</span>\
                      </div>\
                    </div>\
                </div>\
            </div>\
          </div>\
        </div>\
        <form v-if="editingMode" class="uk-form-stacked" style="padding: var(--a2020-card-padding);">\
              <div class="">\
                  <div class="uip-text-bold uip-margin-bottom-xs">{{translations.showmap}}</div>\
                  <label class="uip-switch">\
                    <input type="checkbox" v-model="cardOptions.hideMap">\
                    <span class="uip-slider"></span>\
                  </label>\
              </div>\
        </form>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
