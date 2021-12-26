export function moduleName() {
  return "page-traffic";
}

export function moduleData() {
  return {
    props: {
      chartData: Object,
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
        noAccount: false,
        GAaccount: this.analytics,
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
    },
    computed: {
      isGAconnected() {
        return this.analytics;
      },
      getTheDates() {
        return this.dateRange;
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
            action: "uipress_analytics_get_page_traffic",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(responseData.error, { pos: "bottom-left", status: "danger" });
              self.loading = false;
              return;
            }

            if (responseData.noaccount) {
              ///SOMETHING WENT WRONG
              self.GAaccount = false;
              self.loading = false;
              return;
            }

            self.GAaccount = true;
            self.loading = false;
            self.tableData = responseData.dataSet;
          },
        });
      },
      defaultImage(e) {
        jQuery(e.target).replaceWith('<span class="material-icons-outlined" style="font-size: 20px;margin-right: 10px;">flag</span>');
      },
    },
    template:
      '<div class="uip-padding-s uip-position-relative" :accountConnected="isGAconnected">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
        <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
        <div v-if="loading != true  && isGAconnected" class="uk-overflow-auto">\
          <div class="uip-w-100p uip-min-w-300 uip-overflow-auto">\
            <div class="uip-flex uip-margin-bottom-s">\
                <div class="uip-text-muted uip-text-bold uip-flex-grow">{{translations.page}}</div>\
                <div class="uip-text-muted uip-text-bold uip-margin-left-s uip-w-80 uip-text-right">{{translations.visits}}</div>\
                <div class="uip-text-muted uip-text-bold uip-margin-left-s uip-w-100 uip-text-right">{{translations.change}}</div>\
            </div>\
            <div v-for="item in tableData" class="uip-flex uip-flex-center uip-margin-bottom-s">\
                <div class="uip-flex-grow uip-flex uip-flex-center uip-overflow-hidden uip-text-ellipsis uip-no-wrap uip-overflow-hidden uip-text-ellipsis uip-no-wrap">\
                  <div class="uip-overflow-hidden uip-text-ellipsis uip-no-wrap uip-max-w-200">{{item.name}}</div>\
                </div>\
                <div class="uip-margin-left-s uip-w-80 uip-text-right uip-text-bold uip-flex-no-shrink">{{item.visits}}</div>\
                <div class="uip-margin-left-s uip-w-100 uip-text-right uip-flex uip-flex-right uip-flex-no-shrink">\
                  <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex" \
                  :class="{\'uip-background-red-wash\' : item.change < 0}">\
                    <span v-if="item.change > 0" class="material-icons-outlined" >expand_less</span>\
                    <span v-if="item.change < 0" class="material-icons-outlined" >expand_more</span>\
                    {{item.change}}%\
                  </div>\
                </div>\
            </div>\
          </div>\
        </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
