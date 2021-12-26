export function moduleName() {
  return "popular-products";
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
        recentPosts: [],
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

        if (!this.recentPosts) {
          return [];
        } else {
          return this.recentPosts;
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
      getPosts() {
        let self = this;
        self.loading = true;

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_get_popular_products",
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
            self.loading = false;
            self.nonfound = responseData.nocontent;
            self.totalFound = responseData.totalFound;
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
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
    		  <div v-if="loading == false && formattedPosts.length > 0">\
            <div class="uip-margin-bottom-s uip-flex">\
                <div class="uip-text-bold uip-flex-grow">{{translations.product}}</div>\
                <div class="uip-text-bold uip-text-right uip-w-80">{{translations.sold}}</div>\
                <div class="uip-text-bold uip-text-right uip-w-80">{{translations.value}}</div>\
            </div>\
      			 <div class="uip-flex uip-flex-center uip-margin-bottom-xs" v-for="post in formattedPosts">\
                <div class="uip-flex uip-flex-grow uip-flex-center">\
                    <img v-if="post.img" :src="post.img" class="uip-margin-right-xs uip-w-28 uip-h-28 uip-border-round">\
                    <span v-if="!post.img" class="material-icons-outlined uip-margin-right-xs">local_offer</span>\
          				  <a :href="post.link" class="uip-link-default uip-no-underline uip-text-bold">{{post.title}}</a>\
                </div>\
                <div class="uip-margin-left-xs uip-w-80 uip-text-right uip-text-bold">\
                    {{post.salesCount}}\
                </div>\
                <div class="uip-flex uip-flex-right uip-margin-left-xs uip-w-80">\
                    <span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-flex">{{post.totalValue}}</span>\
                </div>\
        			</div>\
    		  </div>\
    		  <div class="uk-flex" v-if="maxPage > 1">\
    		  <button @click="currentPage -= 1" :disabled="currentPage == 1"\
    		  class="uk-button uk-button-small uk-margin-small-right uk-flex uk-flex-middle" style="padding:5px 20px 5px 20px;"><span class="material-icons-outlined">chevron_left</span></button>\
    		  <button @click="currentPage += 1" :disabled="currentPage == maxPage"\
    		  class="uk-button uk-button-small uk-margin-right uk-flex uk-flex-middle"  style="padding:5px 20px 5px 20px;"><span class="material-icons-outlined">chevron_right</span></button>\
    		  </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
