export function moduleName() {
  return "custom-video";
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
        loading: true,
        cardOptions: this.cardData,
      };
    },
    watch: {
      cardOptions: {
        handler(newValue, oldValue) {
          this.$emit("card-change", newValue);
        },
        deep: true,
      },
    },
    mounted: function () {
      this.loading = false;
    },
    methods: {},
    template:
      '<div class="uip-position-relative">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
        <div v-if="!editingMode" class="uip-w-100p">\
          <iframe v-if="cardOptions.videotype == \'vimeo\' || cardOptions.videotype == \'youtube\'" \
          :src="cardOptions.videoURL" frameborder="0" \
          allowfullscreen uk-responsive uk-video="automute: false;autoplay: false" style="min-width:100%;min-height:200px"></iframe>\
          <video v-if="cardOptions.videotype == \'direct\'" :src="cardOptions.videoURL" controls uk-video="autoplay: false"></video>\
        </div>\
        <div v-if="editingMode" class="uip-padding-s">\
          <div class="uip-margin-bottom-s">\
              <div class="uip-text-bold uip-margin-bottom-xs" for="form-stacked-text">{{translations.title}}</div>\
              <div class="uk-form-controls">\
                  <input type="text" v-model="cardOptions.name" :placeholder="translations.title">\
              </div>\
          </div>\
          <div class="uip-margin-bottom-s">\
              <div class="uip-text-bold uip-margin-bottom-xs" for="form-stacked-text">{{translations.videourl}}</div>\
              <div class="uk-form-controls">\
                  <input v-model="cardOptions.videoURL" type="text" :placeholder="translations.videourl">\
              </div>\
          </div>\
          <div class="uip-margin-bottom-s">\
              <div class="uip-text-bold uip-margin-bottom-xs" for="form-stacked-select">{{translations.embedType}}</div>\
              <div class="uk-form-controls">\
                  <select class="uk-select" id="form-stacked-select" v-model="cardOptions.videotype">\
                      <option value="vimeo">Vimeo (iframe)</option>\
                      <option value="youtube">Youtube (iframe)</option>\
                      <option value="direct">Direct Link to video</option>\
                  </select>\
              </div>\
          </div>\
        </div>\
		 </div>',
  };
  return compData;
}
