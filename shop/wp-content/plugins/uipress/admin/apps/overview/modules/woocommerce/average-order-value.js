export function moduleName() {
  return "average-order-value";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
      editingMode: Boolean,
      premium: Boolean,
    },
    data: function () {
      return {
        cardOptions: this.cardData,
        loading: true,
        numbers: [],
        startDate: this.dateRange.startDate,
        noAccount: false,
        woocommerce: true,
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

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_analytics_get_average_order_value",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              self.loading = false;
              self.woocommerce = false;
              return;
            }

            self.loading = false;
            self.numbers = responseData.numbers;
          },
        });
      },
    },
    template:
      '<div class="uip-padding-s uip-position-relative" >\
    	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
          <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
          <div v-if="!woocommerce" class="notice" >\
              <p>{{translations.woocommerce}}</p>\
          </div>\
          <div v-if=" loading != true && !noAccount && woocommerce" >\
            <div class="uip-flex uip-flex-center uip-margin-bottom-xs">\
              <div class="uip-margin-right-s uip-text-xxl uip-text-emphasis uip-text-bold" >{{numbers.total}}</div>\
              <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex" \
              :class="{\'uip-background-red-wash\' : numbers.change < 0}">\
                <span v-if="numbers.change > 0" class="material-icons-outlined">expand_less</span>\
                <span v-if="numbers.change < 0" class="material-icons-outlined" >expand_more</span>\
                {{numbers.change}}%\
              </div>\
              </div>\
            <div>\
                <div class="uip-text-muted">{{translations.vsPrevious}} {{daysDif}} {{translations.vsdays}} ({{numbers.total_comparison}})</div>\
            </div>\
          </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
