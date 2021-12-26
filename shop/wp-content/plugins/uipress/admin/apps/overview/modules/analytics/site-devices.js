export function moduleName() {
  return "site-devices";
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
        chartData: {
          labels: ["30th July 21", "31st July 21", "1st August 21", "2nd August 21", "3rd August 21", "4th August 21"],
          datasets: [
            {
              label: "Site Users",
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
        output: [],
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
      cardOptions: {
        handler(newValue, oldValue) {
          this.$emit("card-change", newValue);
        },
        deep: true,
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
          return "horizontalbar";
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
            action: "uipress_analytics_get_site_devices",
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
            self.chartData = responseData.dataSet;
            self.output = responseData.output;

            self.loading = false;
          },
        });
      },
    },
    template:
      '<div class="uip-padding-s uip-position-relative" :accountConnected="isGAconnected">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
        <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
        <div v-if="!editingMode && loading != true && isGAconnected" class="uip-flex uip-grid uip-grid-small">\
          <div class="uip-width-medium-large">\
            <uip-chart :removeLabels="true" :dates="getTheDates" v-if="loading != true" :type="getTheType" :chartData="chartData"  :gridLines="false" cWidth="200px"></uip-chart>\
          </div>\
          <div class="uip-width-small-medium uip-flex uip-flex-column">\
            <div v-for="item in output" class="uip-flex uip-flex-center uip-margin-bottom-xs" >\
              <span :style="{\'background\' : item.color}" class="uip-margin-right-xs uip-border-round uip-h-10 uip-w-10 uip-display-inline-block"></span>\
              <span class="uip-text-muted uip-margin-right-xs">{{item.name}}:</span>\
              <span class="uip-text-bold">{{item.value}}</span>\
            </div>\
          </div>\
        </div>\
        <div v-if="editingMode" class="uk-form-stacked">\
          <div class="uip-text-bold uip-margin-bottom-xs" for="form-stacked-select" >{{translations.chartType}}</div>\
          <select  class="uip-w-100p" v-model="cardOptions.chartType">\
              <option value="doughnut">{{translations.doughnut}}</option>\
              <option value="polarArea">{{translations.polarArea}}</option>\
              <option value="bar">{{translations.bar}}</option>\
              <option value="horizontalbar">{{translations.hbar}}</option>\
          </select>\
      </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
