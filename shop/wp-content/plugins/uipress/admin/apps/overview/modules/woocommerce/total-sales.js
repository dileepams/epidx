export function moduleName() {
  return "total-sales";
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
        chartData: {
          labels: ["30th July 21", "31st July 21", "1st August 21", "2nd August 21", "3rd August 21", "4th August 21"],
          datasets: [
            {
              label: "Page Views",
              fill: true,
              data: [12, 19, 3, 5, 2, 3],
              backgroundColor: ["rgba(12, 92, 239, 0.05)"],
              borderColor: ["rgba(12, 92, 239, 1)"],
              borderWidth: 2,
              cutout: "0%",
              spacing: 0,
              borderRadius: 0,
              tension: 0.2,
              pointRadius: 0,
            },
          ],
        },
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
            action: "uipress_analytics_get_total_sales",
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
            self.chartData = responseData.dataSet;
            self.analytics = responseData.analytics;
            self.numbers = responseData.numbers;
          },
        });
      },
    },
    template:
      '<div class="uip-padding-s uip-position-relative" >\
    	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
    		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
          <div v-if="!woocommerce" class="notice" >\
              <p>{{translations.woocommerce}}</p>\
          </div>\
          <div v-if="!editingMode && loading != true && !noAccount && woocommerce">\
            <div class="uip-flex uip-flex-center uip-margin-bottom-xs">\
              <div class="uip-margin-right-s uip-text-xxl uip-text-emphasis uip-text-bold" >{{numbers.total}}</div>\
              <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex" \
              :class="{\'uip-background-red-wash\' : numbers.change < 0}">\
                <span v-if="numbers.change > 0" class="material-icons-outlined">expand_less</span>\
                <span v-if="numbers.change < 0" class="material-icons-outlined" >expand_more</span>\
                {{numbers.change}}%\
              </div>\
              </div>\
            <div class="uip-margin-bottom-m">\
                <div class="uip-text-muted">{{translations.vsPrevious}} {{daysDif}} {{translations.vsdays}} ({{numbers.total_comparison}})</div>\
            </div>\
            <div class="uip-w-100p">\
              <uip-chart :dates="getTheDates" v-if="loading != true" :type="getTheType" :chartData="chartData"  :gridLines="true" cWidth="200px"></uip-chart>\
            </div>\
          </div>\
          <div v-if="editingMode">\
            <div class="uip-text-bold uip-margin-bottom-xs" >{{translations.chartType}}</div>\
            <select class="uip-w-100p" v-model="cardOptions.chartType">\
                <option value="line">{{translations.lineChart}}</option>\
                <option value="bar">{{translations.barChart}}</option>\
            </select>\
        </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
