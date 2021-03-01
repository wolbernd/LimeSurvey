export default  function(userid) {
    return {
        // surveyid: 0,
        // language: '',
        // maxHeight: 0,
        // inSurveyViewHeight: 1000,
        // sideBodyHeight: '100%', // Never used (mutations, state)
        // sideBarHeight: 400,
        currentUser: userid,
        currentTab: 'settings',
        // sidebarwidth: 380,
        maximalSidebar: false,
        // isCollapsed: false,
        pjax: null,
        pjaxLoading: false,
        lastMenuOpen: false,
        lastMenuItemOpen: false,
        lastQuestionOpen: false,
        lastQuestionGroupOpen: false,
        questionGroupOpenArray: [],
        questiongroups: [],
        collapsedmenus: null,
        sidemenus: null,
        surveyActiveState: false,
        toggleKey: Math.floor(Math.random()*10000)+'--key',
        allowOrganizer: true
    };
}
