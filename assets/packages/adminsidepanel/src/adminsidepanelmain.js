//globals formId
import Vue from "vue";
import App from "./App.vue";

import Vuex from "vuex";
import VuexPersistence from 'vuex-persist';
import VueLocalStorage from 'vue-localstorage';

import state from './store/state';
import getters from './store/getters';
import mutations from './store/mutations';
import actions from './store/actions';

// import {PluginLog} from "./mixins/logSystem.js";
// import Loader from './helperComponents/loader.vue';

//Ignore phpunits testing tags
Vue.config.ignoredElements = ["x-test"];
Vue.config.devtools = true;

Vue.use(VueLocalStorage);
Vue.use(Vuex);

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
 }); */

const getRandomInt = function (maximal) {
    return Math.floor(Math.random() * Math.floor(maximal));
};

const AppStateName = 'limesurveyadminsidepanel';
const randomIntForUserID   = getRandomInt(100);
const randomIntForSurveyID = getRandomInt(200);
const vuexLocal = new VuexPersistence({
    key: AppStateName + '_' + randomIntForUserID + '_' + randomIntForSurveyID,
    storage: window.sessionStorage
});

const store = Vuex.Store({
    state: state,
    plugins: [
        vuexLocal.plugin
    ],
    getters,
    mutations,
    actions
});

new Vue({
    render: h => h(App),
    store: store,
}).$mount('#vue-sidebar-container');