export function moduleName() {
  return "recent-orders";
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
        width: "uk-width-" + this.cardData.size,
        recentPosts: [],
        currentPage: 1,
        startDate: this.dateRange.startDate,
        maxPage: 1,
        totalFound: 0,
        loading: true,
        nonfound: "",
        woocommerce: true,
      };
    },
    mounted: function () {
      this.loading = false;
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
      daysDif() {
        self = this;
        var b = moment(self.dateRange.startDate);
        var a = moment(self.dateRange.endDate);
        return a.diff(b, "days");
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
            action: "uipress_get_recent_orders",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
            currentPage: self.currentPage,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              self.loading = false;
              self.woocommerce = false;
              return;
            }

            self.recentPosts = responseData.posts;
            self.maxPage = responseData.maxPages;
            self.totalFound = responseData.totalFound;
            self.loading = false;
            self.nonfound = responseData.nocontent;
          },
        });
      },
    },
    template:
      '<div class="uip-padding-s uip-position-relative" >\
    	  	<p v-if="totalFound == 0" class="uk-text-meta">{{nonfound}}</p>\
          <div v-if="!woocommerce" class="uk-alert-warning" uk-alert>\
              <p>{{translations.woocommerce}}</p>\
          </div>\
          <div v-if="woocommerce" class="uip-background-primary-wash uip-padding-s uip-border-round uip-margin-bottom-m" >\
                <div class="uip-text-bold uip-text-emphasis uip-text-xxl">\
                  {{totalFound}}\
                </div>\
                <div class="uip-text-muted">{{translations.inTheLast}} {{daysDif}} {{translations.days}}</div>\
          </div>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
    		  <div v-if="loading == false && formattedPosts.length > 0">\
      			<div v-for="post in formattedPosts" class="uip-flex uip-flex-center uip-margin-bottom-xxs uip-padding-xxs hover:uip-background-muted uip-border-round">\
              <div class="uip-flex-grow">\
        				<a :href="post.editURL" class="uip-link-default uip-text-bold uip-no-underline">{{post.title}}</a>\
        				<span class="uip-text-muted">{{post.date}}</span>\
      			  </div>\
              <div class="uip-margin-left-s uip-w-80">\
                  <a class="uip-link-default uip-no-underline" :href="post.userURL">{{post.customer}}</a>\
              </div>\
              <div  class="uip-margin-left-s uip-w-60 uip-text-right">\
                  <div class="uip-text-bold">{{post.value}}</div>\
              </div>\
      			  <div  class="uip-margin-left-s uip-w-80 uip-flex uip-flex-right">\
      				    <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex">{{post.status}}</div>\
      			  </div>\
      			</div>\
    		  </div>\
    		  <div class="uip-margin-top-xs" v-if="maxPage > 1">\
      		  <button @click="currentPage -= 1" :disabled="currentPage == 1"\
      		  class="uip-button-default uip-margin-right-xxs" ><span class="material-icons-outlined">chevron_left</span></button>\
      		  <button @click="currentPage += 1" :disabled="currentPage == maxPage"\
      		  class="uip-button-default uip-margin-right-s"><span class="material-icons-outlined">chevron_right</span></button>\
    		  </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
