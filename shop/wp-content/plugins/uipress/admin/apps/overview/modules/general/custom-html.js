export function moduleName() {
  return "custom-html";
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
        strippedShort: "",
        shortCode: "",
      };
    },
    mounted: function () {
      this.loading = false;

      if (this.cardOptions.shortcode) {
        this.strippedShort = this.cardOptions.shortcode.replace(/\\(.)/gm, "$1");
      }
    },
    watch: {
      strippedShort: function (newValue, oldValue) {
        this.cardOptions.shortcode = this.strippedShort;
      },
      cardOptions: {
        handler(newValue, oldValue) {
          this.$emit("card-change", newValue);
        },
        deep: true,
      },
    },
    computed: {},
    methods: {
      getDataFromComp(code) {
        return code;
      },
    },
    template:
      '<div class="uip-padding-s">\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <div v-if="!editingMode" v-html="strippedShort">\
        </div>\
        <div v-if="editingMode" class="uk-form-stacked" >\
          <div class="uip-margin-bottom-s">\
              <div class="uip-text-bold uip-margin-bottom-xs" for="form-stacked-text">{{translations.title}}</div>\
              <div class="">\
                  <input class="uk-input uk-form-small"  type="text" v-model="cardOptions.name" :placeholder="translations.title">\
              </div>\
          </div>\
          <div class="uip-margin-bottom-s">\
              <div class="uip-text-bold uip-margin-bottom-xs" for="form-stacked-text">HTML</div>\
              <div class="">\
                  <code-flask  language="HTML"  :usercode="strippedShort" \
                  @code-change="strippedShort = getDataFromComp($event)"></code-flask>\
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
