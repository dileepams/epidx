export function moduleName() {
  return "recent-posts";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
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
            action: "uipress_get_posts",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
            currentPage: self.currentPage,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(responseData.error, { pos: "bottom-left", status: "danger" });
              self.loading = false;
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
      '<div class="uip-padding-s">\
	  	<p v-if="totalFound == 0" class="uk-text-meta">{{nonfound}}</p>\
      <div class="uip-flex uip-background-primary-wash uip-padding-s uip-border-round uip-margin-bottom-m" >\
        <div class="">\
            <div class="uip-text-bold uip-text-emphasis uip-text-xxl">\
              <span class="">{{totalFound}}</span>\
            </div>\
            <div class="uip-text-muted ">{{translations.inTheLast}} {{daysDif}} {{translations.days}}</div>\
        </div>\
      </div>\
      <loading-placeholder v-if="loading == true"></loading-placeholder>\
      <loading-placeholder v-if="loading == true"></loading-placeholder>\
      <loading-placeholder v-if="loading == true"></loading-placeholder>\
		  <div v-if="loading == false && formattedPosts.length > 0" class="uip-w-100p">\
  			<div class="uip-flex uip-flex-center uip-padding-xxs hover:uip-background-muted uip-border-round" v-for="post in formattedPosts">\
          <div class="uip-flex-grow">\
    				<a :href="post.href" class="uip-link-default uip-text-bold uip-no-underline">{{post.title}}</a><br/>\
    				<span class="uip-text-muted">{{post.author}}</span>\
  			  </div>\
          <div class="uip-margin-left-xs">\
            <span class="uip-background-primary-wash uip-border-round uip-padding-left-xxs uip-padding-right-xxs uip-text-bold">{{post.type}}</span>\
          </div>\
  			  <div class="uip-text-right uip-text-muted uip-margin-left-xs">\
  				  {{post.date}}\
  			  </div>\
  			</div>\
		  </div>\
		  <div class="uip-flex uip-margin-top-s" v-if="maxPage > 1">\
  		  <button @click="currentPage -= 1" :disabled="currentPage == 1"\
  		  class="uip-button-default material-icons-outlined uip-margin-right-xxs">chevron_left</button>\
  		  <button @click="currentPage += 1" :disabled="currentPage == maxPage"\
  		  class="uip-button-default material-icons-outlined">chevron_right</button>\
		  </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
