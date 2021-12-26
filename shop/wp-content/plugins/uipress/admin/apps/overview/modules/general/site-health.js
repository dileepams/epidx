export function moduleName() {
  return "site-health";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
      premium: Boolean,
    },
    data: function () {
      return {
        cardOptions: this.cardData,
        issues: [],
        chartData: {
          labels: ["30th July 21", "31st July 21", "1st August 21", "2nd August 21", "3rd August 21", "4th August 21"],
          datasets: [
            {
              label: "Page Views",
              fill: true,
              data: [12, 19, 3, 5, 2, 3],
              backgroundColor: ["rgba(12, 92, 239, 0.05)"],
              borderColor: ["rgba(12, 92, 239, 1)"],
              borderWidth: 0,
              cutout: "80%",
              spacing: 10,
              borderRadius: 5,
            },
          ],
        },
        loading: true,
        dataSets: [],
        chartLabels: [],
        colours: {
          bgColors: [],
          borderColors: [],
        },
        message: "",
        linkMessage: "",
        healthUrl: "",
      };
    },
    mounted: function () {
      this.loading = false;
      this.getPosts();
    },
    computed: {
      getPostsOnce() {
        this.getPosts();
      },
      getChartData() {
        return this.chartData;
      },
      formattedPosts() {
        this.getPostsOnce;
        return this.recentPosts;
      },
    },
    methods: {
      getPosts() {
        let self = this;
        self.loading = true;

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_get_system_health",
            security: uipress_overview_ajax.security,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(responseData.error, { pos: "bottom-left", status: "danger" });
              self.loading = false;
              return;
            }
            self.issues = responseData.issues;
            self.loading = false;

            self.chartData = responseData.dataSet;

            if (responseData.message) {
              self.message = responseData.message;
              self.linkMessage = responseData.linkMessage;
              self.healthUrl = responseData.healthUrl;
            }
          },
        });
      },
    },
    template:
      '<div class="uip-padding-s">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <div class="uip-flex uip-grid ">\
          <div class="uip-width-medium">\
            <uip-chart :removeLabels="true" v-if="loading != true" type="doughnut" :dates="dateRange" :chartData="chartData" :gridLines="false" cWidth="200px"></uip-chart>\
          </div>\
          <div class="uip-width-medium">\
            <div v-for="item in issues" class="uip-margin-bottom-xs" >\
              <span :style="{\'background\' : item.color}"\
              class="uip-margin-right-xs uip-border-round uip-h-10 uip-w-10 uip-display-inline-block"></span>\
              <span class="uip-text-muted uip-margin-right-xs">{{item.name}}:</span>\
              <span class="uip-text-bold">{{item.value}}</span>\
            </div>\
            <p v-if="message" class="uk-margin-remove-bottom">{{message}} <a :href="healthUrl">{{linkMessage}}</a></p>\
          </div>\
        </div>\
  		</div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
