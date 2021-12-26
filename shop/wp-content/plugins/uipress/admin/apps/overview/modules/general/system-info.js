export function moduleName() {
  return "system-info";
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
        recentPosts: [],
        currentPage: 1,
        maxPage: 1,
        totalFound: 0,
        loading: true,
      };
    },
    mounted: function () {
      this.loading = false;
    },
    computed: {
      getPostsOnce() {
        this.getPosts();
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
            action: "uipress_get_system_info",
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
            self.recentPosts = responseData.posts;
            self.loading = false;
          },
        });
      },
    },
    template:
      '<div class="uip-padding-s">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <div v-if="loading == false" >\
      			<div class="uip-flex uip-flex-center uip-margin-bottom-xs" v-for="post in formattedPosts">\
      			  <div class="uip-flex-grow">\
      				{{post.name}}\
      			  </div>\
      			  <div class="">\
      				  <div class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold">{{post.version}}</div>\
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
