<?php
   /**
    * This view displays the sidemenu on the left side, containing the question explorer
    *
    */
?>
<?php
    // todo $showSideMenu is not used by vue sidebar.vue? normally set by $aData['sidemenu']['state']
    $sidemenu['state'] = isset($sidemenu['state']) ? $sidemenu['state'] : true;
    if (
        $sideMenuBehaviour == 'alwaysClosed'
        || ($sideMenuBehaviour == 'adaptive'
        && !$sidemenu['state'])
    ) {
        $showSideMenu = false;
    } else {
        $showSideMenu = true;
    }
    //todo: change urls after refactoring the controllers
    $getQuestionsUrl = $this->createUrl("/surveyAdministration/getAjaxQuestionGroupArray/", ["surveyid" => $surveyid]);
    $getMenuUrl = $this->createUrl("/surveyAdministration/getAjaxMenuArray/", ["surveyid" => $surveyid]);
    $createQuestionGroupLink = $this->createUrl('/questionGroupsAdministration/add/' , ["surveyid" => $surveyid]);
    $createQuestionLink = "questionAdministration/create/surveyid/".$surveyid;
    $unlockLockOrganizerUrl = $this->createUrl("admin/user/sa/togglesetting/", ['surveyid' => $surveyid]);

    $updateOrderLink =  $this->createUrl("questionGroupsAdministration/updateOrder/", ["surveyid" =>  $surveyid]);

    $createPermission = Permission::model()->hasSurveyPermission($surveyid, 'surveycontent', 'create');
    if ($activated || !$createPermission) {
        $createQuestionGroupLink = "";
        $createQuestionLink = "";
    }
    $landOnSideMenuTab = (isset($sidemenu['landOnSideMenuTab']) ? $sidemenu['landOnSideMenuTab'] : '');
    
    $menuObjectArray =  [
        "side" => [],
        "collapsed" => [],
        "top" => [],
        "bottom" => [],
    ];
    foreach ($menuObjectArray as $position => $arr) {
        $menuObjectArray[$position] = Survey::model()->findByPk($surveyid)->getSurveyMenus($position);
    }

    $isActive = (Survey::model()->findByPk($surveyid)->isActive);
   
    $data = '
    window.SideMenuData = {
        getQuestionsUrl: "'.$getQuestionsUrl.'",
        getMenuUrl: "'.$getMenuUrl.'",
        createQuestionGroupLink: "'.$createQuestionGroupLink.'",
        createQuestionLink: "'.$createQuestionLink.'",
        gid: '.(isset($gid) ? $gid : 'null').',
        options: [],
        surveyid: '.$surveyid.',
        basemenus: '.json_encode($menuObjectArray).',
        updateOrderLink: "'.$updateOrderLink.'",
        unlockLockOrganizerUrl: "'.$unlockLockOrganizerUrl.'",
        allowOrganizer: '.(SettingsUser::getUserSettingValue('lock_organizer') ? '0' : '1').',
        translate: '
        .json_encode(
            [
                "settings" => gT("Settings"),
                "structure" => gT("Structure"),
                "createPage" => gT("Add group"),
                "createQuestion" => gT("Add question"),
                "lockOrganizerTitle" => gT("Lock question organizer"),
                "unlockOrganizerTitle" => gT("Unlock question organizer"),
                "collapseAll" => gT("Collapse all question groups"),
            ]
        )
    .'};';
    
    Yii::app()->getClientScript()->registerScript('SideBarGlobalObject', $data, 
        LSYii_ClientScript::POS_HEAD
    );
?>

<div class="simpleWrapper ls-flex" id="vue-sidebar-container">
    <?php if($landOnSideMenuTab !== ''): ?>
        <app land-on-side-tab='<?php echo $landOnSideMenuTab ?>' is-active="<?php echo $isActive ?>" />
    <?php else: ?>
         <app />
    <?php endif; ?>
</div>
