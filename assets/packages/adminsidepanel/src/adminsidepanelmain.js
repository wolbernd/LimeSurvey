//globals formId
import Vue from "vue";
import App from "./App.vue";
import {getAppState} from "./store/vuex-store.js";
// import {PluginLog} from "./mixins/logSystem.js";
// import Loader from './helperComponents/loader.vue';

//Ignore phpunits testing tags
Vue.config.ignoredElements = ["x-test"];
Vue.config.devtools = true;

Vue.use(getAppState); // Self developed Vuex by previous frontend dev.

// Vue.use(PluginLog);

//Vue.component('loader-widget', Loader);

/** Vue.mixin({
    methods: {
        updatePjaxLinks: function () {
            this.$forceUpdate();
            this.$store.commit('newToggleKey');
        },
        redoTooltips: function () {
            window.LS.doToolTip();
        },
        translate(string){
            return window.SideMenuData.translate[string] || string;
        }
    },
    filters: {
        translate(string){
            return window.SideMenuData.translate[string] || string;
        }
    }
 }); **/

new Vue({
    render: h => h(App),
    store: getAppState,
}).$mount('#vue-sidebar-container');