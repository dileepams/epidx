export function moduleName() {
  return "calendar";
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
        loading: true,
        date: {
          fullDay: "",
          numberDay: "",
          hour: "",
          minute: "",
          second: "",
          ampm: "",
        },
      };
    },
    mounted: function () {
      this.loading = false;
      this.setTime();
      let clock = this;
      //setTimeout(clock.setTime(), 1000);
      window.setInterval(() => {
        clock.setTime();
      }, 1000);
    },
    computed: {},
    methods: {
      setTime() {
        let clock = this;
        clock.date.fullDay = moment().format("dddd");
        clock.date.numberDay = moment().format("Do");
        clock.date.hour = moment().format("h");
        clock.date.minute = moment().format("mm");
        clock.date.second = moment().format("ss");
        clock.date.ampm = moment().format("a");
      },
    },
    template:
      '<div class="uip-padding-s">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <div class="uip-flex uip-flex-center">\
          <div class="uip-margin-right-s uip-flex-grow">\
            <div class="">\
              <span class="uip-text-primary uip-text-bold uip-text-l">{{date.fullDay}}</span>\
            </div>\
            <div class="">\
    		     <span class="uip-text-bold uip-text-xxl uip-text-emphasis">{{date.numberDay}}</span>\
            </div>\
          </div>\
          <div class="">\
           <span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-l">{{date.hour}}</span>\
           <span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-l">{{date.minute}}</span>\
           <span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-l">{{date.second}}</span>\
           <span class="uip-background-primary-wash uip-border-round uip-padding-xxs uip-text-bold uip-text-l">{{date.ampm}}</span>\
          </div>\
        </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
