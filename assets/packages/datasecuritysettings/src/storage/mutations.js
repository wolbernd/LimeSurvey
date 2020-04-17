import Vue from "vue";
import isEmpty from "lodash/isEmpty";
import keys from "lodash/keys";
import indexOf from "lodash/indexOf";

export default {

    setShowsurveypolicynotice(state, newValue) {
        state.showsurveypolicynotice = newValue;
    },
    setShowDataSecurityButton(state, newValue) {
        state.showdatasecuritybutton = newValue;
    },
    setShowLegalNoticeButton(state, newValue) {
        state.showlegalnoticebutton = newValue;
    },

    setDataseclabel(state, newValue) {
        state.dataseclabel = newValue;
    },
    setDatasecmessage(state, newValue) {
        state.datasecmessage = newValue;
    },
    setDatasecerror(state, newValue) {
        state.datasecerror = newValue;
    },
    setPermissions(state, newValue) {
        state.permissions = newValue;
    },
    setLegalNoticeMessage(state, newValue) {
        state.legalnoticemessage = newValue;
    },

    setDataseclabelForCurrentLanguage(state, newValue) {
        Vue.set(state.dataseclabel, state.activeLanguage, newValue);
    },
    setDatasecmessageForCurrentLanguage(state, newValue) {
        Vue.set(state.datasecmessage, state.activeLanguage, newValue);
    },
    setDatasecerrorForCurrentLanguage(state, newValue) {
        Vue.set(state.datasecerror, state.activeLanguage, newValue);
    },
    setLegalNoticeMessageForCurrentLanguage(state, newValue) {
        Vue.set(state.legalnoticemessage, state.activeLanguage, newValue);
    },

    //view controllers
    toggleVisible(state, newValue=null) {
        newValue = newValue === null ? !state.visible : newValue;
        state.visible = newValue;
    },
    
    setActiveLanguage : (state, newValue) => {
        state.activeLanguage = newValue;
    },
    setLanguages : (state, newValue) => {
        state.languages = newValue;
    },
    nextLanguage: (state) => {
        let keyList = keys(state.languages);
        let currentIndex = indexOf(keyList, state.activeLanguage);
        if(currentIndex < keyList.length) {
            state.activeLanguage = keyList[currentIndex+1];
        }
    },
    previousLanguage: (state) => {
        let keyList = keys(state.languages);
        let currentIndex = indexOf(keyList, state.activeLanguage);
        if(currentIndex > 0) {
            state.activeLanguage = keyList[currentIndex-1];
        }
    },
    toggleDebugMode: (state) => {
        state.debugMode = !state.debugMode;
    }
};
