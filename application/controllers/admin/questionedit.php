<?php
/**
 * LimeSurvey
 * Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 */

/**
 * Controller questionedit
 * Contains methods to control and view the vuejs based question editor
 *
 * @package   LimeSurvey
 * @author    LimeSurvey Team <support@limesurvey.org>
 * @copyright 2019 LimeSurvey GmbH
 * @access    public
 */
class questionedit extends Survey_Common_Action
{
    /**
     * Main view function prepares the necessary global js parts and renders the HTML
     *
     * @param integer $surveyid
     * @param integer $gid
     * @param integer $qid
     * @param string  $landOnSideMenuTab Name of the side menu tab. Default behavior is to land on structure tab.
     * @throws CException
     * @throws CHttpException
     */
    public function view($surveyid, $gid = null, $qid = null, $landOnSideMenuTab = 'structure')
    {
        $aData = array();
        $iSurveyID = (int) $surveyid;
        $oSurvey = Survey::model()->findByPk($iSurveyID);
        $gid = $gid ?? $oSurvey->groups[0]->gid;
        $oQuestion = $this->getQuestionObject($qid, null, $gid);
        App()->getClientScript()->registerPackage('questioneditor');
        App()->getClientScript()->registerPackage('ace');
        $qrrow = $oQuestion->attributes;
        $baselang = $oSurvey->language;

        if (App()->session['questionselectormode'] !== 'default') {
            $questionSelectorType = App()->session['questionselectormode'];
        } else {
            $questionSelectorType = App()->getConfig('defaultquestionselectormode');
        }

        $aData['display']['menu_bars']['gid_action'] = 'viewquestion';
        $aData['questionbar']['buttons']['view'] = true;

        // Last question visited : By user (only one by user)
        $setting_entry = 'last_question_' . App()->user->getId();
        SettingGlobal::setSetting($setting_entry, $oQuestion->qid);

        // we need to set the sid for this question
        $setting_entry = 'last_question_sid_' . App()->user->getId();
        SettingGlobal::setSetting($setting_entry, $iSurveyID);

        // we need to set the gid for this question
        $setting_entry = 'last_question_gid_' . App()->user->getId();
        SettingGlobal::setSetting($setting_entry, $gid);

        // Last question for this survey (only one by survey, many by user)
        $setting_entry = 'last_question_' . App()->user->getId() . '_' . $iSurveyID;
        SettingGlobal::setSetting($setting_entry, $oQuestion->qid);

        // we need to set the gid for this question
        $setting_entry = 'last_question_' . App()->user->getId() . '_' . $iSurveyID . '_gid';
        SettingGlobal::setSetting($setting_entry, $gid);

        ///////////
        // combine aData
        $aData['surveyid'] = $iSurveyID;
        $aData['oSurvey'] = $oSurvey;
        $aData['aQuestionTypeList'] = QuestionTheme::findAllQuestionMetaDataForSelector();
        $aData['aQuestionTypeStateList'] = QuestionType::modelsAttributes();
        $aData['selectedQuestion'] = QuestionTheme::findQuestionMetaData($oQuestion->type);
        $aData['gid'] = $gid;
        $aData['qid'] = $oQuestion->qid;
        $aData['activated'] = $oSurvey->active;
        $aData['oQuestion'] = $oQuestion;
        $aData['languagelist'] = $oSurvey->allLanguages;
        $aData['qshowstyle'] = '';
        $aData['qrrow'] = $qrrow;
        $aData['baselang'] = $baselang;
        $aData['sImageURL'] = App()->getConfig('adminimageurl');
        $aData['iIconSize'] = App()->getConfig('adminthemeiconsize');
        $aData['display']['menu_bars']['qid_action'] = 'editquestion';
        $aData['display']['menu_bars']['gid_action'] = 'viewquestion';
        $aData['action'] = 'editquestion';
        $aData['editing'] = true;

        $aData['title_bar']['title'] = $oSurvey->currentLanguageSettings->surveyls_title
        . " (" . gT("ID") . ":" . $iSurveyID . ")";
        $aData['surveyIsActive'] = $oSurvey->active !== 'N';
        $aData['activated'] = $oSurvey->active;
        $aData['jsData'] = [
            'surveyid' => $iSurveyID,
            'surveyObject' => $oSurvey->attributes,
            'gid' => $gid,
            'qid' => $oQuestion->qid,
            'startType' => $oQuestion->type,
            'baseSQACode' => [
                'answeroptions' => SettingsUser::getUserSettingValue('answeroptionprefix', App()->user->id) ?? 'AO' ,
                'subquestions' => SettingsUser::getUserSettingValue('subquestionprefix', App()->user->id) ?? 'SQ',
            ],
            'startInEditView' => SettingsUser::getUserSettingValue('noViewMode', App()->user->id) == '1',
            'connectorBaseUrl' => 'admin/questioneditor',
            'questionSelectorType' => $questionSelectorType,
            'i10N' => [
                'Create question' => gT('Create question'),
                'General settings' => gT("General settings"),
                'Code' => gT('Code'),
                'Text elements' => gT('Text elements'),
                'Question type' => gT('Question type'),
                'Question' => gT('Question'),
                'Help' => gT('Help'),
                'subquestions' => gT('Subquestions'),
                'answeroptions' => gT('Answer options'),
                'Quick add' => gT('Quick add'),
                'Copy subquestions' => gT('Copy subquestions'),
                'Copy answer options' => gT('Copy answer options'),
                'Copy default answers' => gT('Copy default answers'),
                'Copy advanced options' => gT('Copy advanced options'),
                'Predefined label sets' => gT('Predefined label sets'),
                'Save as label set' => gT('Save as label set'),
                'More languages' => gT('More languages'),
                'Add subquestion' => gT('Add subquestion'),
                'Reset' => gT('Reset'),
                'Save' => gT('Save'),
                'Some example subquestion' => gT('Some example subquestion'),
                'Delete' => gT('Delete'),
                'Open editor' => gT('Open editor'),
                'Duplicate' => gT('Duplicate'),
                'No preview available' => gT('No preview available'),
                'Editor' => gT('Editor'),
                'Quick edit' => gT('Quick edit'),
                'Cancel' => gT('Cancel'),
                'Replace' => gT('Replace'),
                'Add' => gT('Add'),
                'Select delimiter' => gT('Select delimiter'),
                'Semicolon' => gT('Semicolon'),
                'Comma' => gT('Comma'),
                'Tab' => gT('Tab'),
                'New rows' => gT('New rows'),
                'Scale' => gT('Scale'),
                'Save and Close' => gT('Save and close'),
                'Script' => gT('Script'),
                '__SCRIPTHELP' => gT("This optional script field will be wrapped,"
                    . " so that the script is correctly executed after the question is on the screen."
                    . " If you do not have the correct permissions, this will be ignored"),
                "noCodeWarning" =>
                gT("Please put in a valid code. Only letters and numbers are allowed and it has to start with a letter. For example [Question1]"),
                "alreadyTaken" =>
                gT("This code is already used - duplicate codes are not allowed."),
                "codeTooLong" =>
                gT("A question code cannot be longer than 20 characters."),
                "Question cannot be stored. Please check the subquestion codes for duplicates or empty codes." =>
                gT("Question cannot be stored. Please check the subquestion codes for duplicates or empty codes."),
                "Question cannot be stored. Please check the answer option for duplicates or empty titles." =>
                gT("Question cannot be stored. Please check the answer option for duplicates or empty titles."),
            ],
        ];

        $aData['topBar']['type'] = 'question';

        $aData['topBar']['importquestion'] = true;
        $aData['topBar']['showSaveButton'] = true;
        $aData['topBar']['savebuttonform'] = 'frmeditgroup';
        $aData['topBar']['closebuttonurl'] = '/admin/survey/sa/listquestions/surveyid/' . $iSurveyID; // Close button

        if ($landOnSideMenuTab !== '') {
            $aData['sidemenu']['landOnSideMenuTab'] = $landOnSideMenuTab;
        }
        $this->_renderWrappedTemplate('survey/Question2', 'view', $aData);
    }

    /****
     * *** A lot of getter function regarding functionalities and views.
     * *** All called via ajax
     ****/

    /**
     * Returns all languages in a specific survey as a JSON document
     *
     * @param int $iSurveyId
     *
     * @return void
     */
    public function getPossibleLanguages($iSurveyId)
    {
        $iSurveyId = (int) $iSurveyId;
        $aLanguages = Survey::model()->findByPk($iSurveyId)->allLanguages;
        $this->renderJSON($aLanguages);
    }

    /**
     * Action called by the FE editor when a save is triggered.
     *
     * @param int $sid Survey id
     *
     * @return void
     * @throws CException
     */
    public function saveQuestionData($sid)
    {
        $sid           = (int) $sid;
        $survey        = Survey::model()->findByPk($sid);
        $questionSaver = new \LimeSurvey\Models\Services\Question\QuestionSaver(
            App()->request,
            $survey,
            Question::model(),
            QuestionL10n::model(),
            $this->getController()
        );
        $this->renderJSON($questionSaver->saveQuestionData($sid));
    }

    /**
     * Update the data set in the FE
     *
     * @param int $iQuestionId
     * @param string $type
     * @param int $gid Group id
     * @param string $question_template
     *
     * @return void
     * @throws CException
     */
    public function reloadQuestionData($iQuestionId = null, $type = null, $gid = null, $question_template = 'core')
    {
        $iQuestionId = (int) $iQuestionId;
        $oQuestion = $this->getQuestionObject($iQuestionId, $type, $gid);

        $aCompiledQuestionData = $this->getCompiledQuestionData($oQuestion);
        $aQuestionGeneralOptions = $this->getGeneralOptions(
            $oQuestion->qid,
            $type,
            $oQuestion->gid,
            true,
            $question_template
        );
        $aAdvancedOptions = $this->getAdvancedOptions($oQuestion->qid, $type, true, $question_template);

        $aLanguages = [];
        $aAllLanguages = getLanguageData(false, App()->session['adminlang']);
        $aSurveyLanguages = $oQuestion->survey->getAllLanguages();

        array_walk(
            $aSurveyLanguages,
            function ($lngString) use (&$aLanguages, $aAllLanguages) {
                $aLanguages[$lngString] = $aAllLanguages[$lngString]['description'];
            }
        );

        $this->renderJSON(
            array_merge(
                $aCompiledQuestionData,
                [
                    'languages' => $aLanguages,
                    'mainLanguage' => $oQuestion->survey->language,
                    'generalSettings' => $aQuestionGeneralOptions,
                    'advancedSettings' => $aAdvancedOptions,
                    'questiongroup' => $oQuestion->group->attributes,
                ]
            )
        );
    }

    /**
     * Collect initial question data
     * This either creates a temporary question object, or calls a question object from the database
     *
     * @param int $iQuestionId
     * @param int $gid
     * @param string $type
     *
     * @return void
     * @throws CException
     */
    public function getQuestionData($iQuestionId = null, $gid = null, $type = null)
    {
        $iQuestionId = (int) $iQuestionId;
        $oQuestion = $this->getQuestionObject($iQuestionId, $type, $gid);

        $aQuestionInformationObject = $this->getCompiledQuestionData($oQuestion);
        $surveyInfo = $this->getCompiledSurveyInfo($oQuestion);

        $aLanguages = [];
        $aAllLanguages = getLanguageData(false, App()->session['adminlang']);
        $aSurveyLanguages = $oQuestion->survey->getAllLanguages();
        array_walk(
            $aSurveyLanguages,
            function ($lngString) use (&$aLanguages, $aAllLanguages) {
                $aLanguages[$lngString] = $aAllLanguages[$lngString]['description'];
            }
        );

        $this->renderJSON(
            array_merge(
                $aQuestionInformationObject,
                [
                    'surveyInfo' => $surveyInfo,
                    'languages' => $aLanguages,
                    'mainLanguage' => $oQuestion->survey->language,
                ]
            )
        );
    }

    /**
     * Collect the permissions available for a specific question
     *
     * @param $iQuestionId
     *
     * @return void
     * @throws CException
     */
    public function getQuestionPermissions($iQuestionId = null)
    {
        $iQuestionId = (int) $iQuestionId;
        $oQuestion = $this->getQuestionObject($iQuestionId);

        $aPermissions = [
            "read" => Permission::model()->hasSurveyPermission($oQuestion->sid, 'survey', 'read'),
            "update" => Permission::model()->hasSurveyPermission($oQuestion->sid, 'survey', 'update'),
            "editorpreset" => App()->session['htmleditormode'],
            "script" =>
            Permission::model()->hasSurveyPermission($oQuestion->sid, 'survey', 'update')
            && SettingsUser::getUserSetting('showScriptEdit', App()->user->id),
        ];

        $this->renderJSON($aPermissions);
    }

    /**
     * Returns a json document containing the question types
     *
     * @return void
     */
    public function getQuestionTypeList()
    {
        $this->renderJSON(QuestionType::modelsAttributes());
    }

    /**
     * @todo document me.
     *
     * @param string $sQuestionType
     * @return void
     */
    public function getQuestionTypeInformation($sQuestionType)
    {
        $aTypeInformations = QuestionType::modelsAttributes();
        $aQuestionTypeInformation = $aTypeInformations[$sQuestionType];

        $this->renderJSON($aQuestionTypeInformation);
    }

    /**
     * @todo document me
     *
     * @param int $iQuestionId
     * @param string $sQuestionType
     * @param int $gid
     * @param boolean $returnArray
     * @param string $question_template
     *
     * @return void|array
     * @throws CException
     */
    public function getGeneralOptions(
        $iQuestionId = null,
        $sQuestionType = null,
        $gid = null,
        $returnArray = false,
        $question_template = 'core'
    ) {
        $oQuestion = $this->getQuestionObject($iQuestionId, $sQuestionType, $gid);
        $aGeneralOptionsArray = $oQuestion
            ->getDataSetObject()
            ->getGeneralSettingsArray($oQuestion->qid, $sQuestionType, null, $question_template);

        if ($returnArray === true) {
            return $aGeneralOptionsArray;
        }

        $this->renderJSON($aGeneralOptionsArray);
    }

    /**
     * @todo document me
     *
     * @param int $iQuestionId
     * @param string $sQuestionType
     * @param boolean $returnArray
     * @param string $question_template
     *
     * @return void|array
     * @throws CException
     */
    public function getAdvancedOptions(
        $iQuestionId = null,
        $sQuestionType = null,
        $returnArray = false,
        $question_template = 'core'
    ) {
        $oQuestion = $this->getQuestionObject($iQuestionId, $sQuestionType);
        $aAdvancedOptionsArray = $oQuestion->getDataSetObject()
            ->getAdvancedOptions($oQuestion->qid, $sQuestionType, null, $question_template);
        if ($returnArray === true) {
            return $aAdvancedOptionsArray;
        }

        $this->renderJSON(
            [
                'advancedSettings' => $aAdvancedOptionsArray,
                'questionTypeDefinition' => $oQuestion->questionType,
            ]
        );
    }

    /**
     * Live preview rendering
     *
     * @param int $iQuestionId
     * @param string $sLanguage
     * @param boolean $root
     *
     * @return void
     *
     * @throws CException
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Syntax
     * @throws WrongTemplateVersionException
     */
    public function getRenderedPreview($iQuestionId, $sLanguage, $root = false)
    {
        if ($iQuestionId == null) {
            echo "<h3>No Preview available</h3>";
            return;
        }
        $root = (bool) $root;

        $changedText = App()->request->getPost('changedText', []);
        $changedType = App()->request->getPost('changedType', null);
        $oQuestion = Question::model()->findByPk($iQuestionId);

        $changedType = $changedType == null ? $oQuestion->type : $changedType;

        if ($changedText !== []) {
            App()->session['edit_' . $iQuestionId . '_changedText'] = $changedText;
        } else {
            $changedText = isset(App()->session['edit_' . $iQuestionId . '_changedText'])
            ? App()->session['edit_' . $iQuestionId . '_changedText']
            : [];
        }

        $aFieldArray = [
            //  0 => string qid
            $oQuestion->qid,
            //  1 => string sgqa | This should be working because it is only about parent questions here!
            "{$oQuestion->sid}X{$oQuestion->gid}X{$oQuestion->qid}",
            //  2 => string questioncode
            $oQuestion->title,
            //  3 => string question | technically never used in the new renderers and totally unessecary therefor empty
            "",
            //  4 => string type
            $oQuestion->type,
            //  5 => string gid
            $oQuestion->gid,
            //  6 => string mandatory,
            ($oQuestion->mandatory == 'Y'),
        ];
        Yii::import('application.helpers.qanda_helper', true);
        setNoAnswerMode(['shownoanswer' => $oQuestion->survey->shownoanswer]);

        // Some session magic.
        // TODO: Factor out $_SESSION from question rendering.
        $sessionBackup = $_SESSION;
        $survey = $oQuestion->survey;
        $surveyid = $survey->sid;
        $_SESSION['survey_'.$surveyid] = [];
        $_SESSION['survey_'.$surveyid]['s_lang'] = 'en';
        $fieldmap = createFieldMap($survey, 'full', true, false, $_SESSION['survey_'.$surveyid]['s_lang']);
        foreach ($fieldmap as $info) {
            // Needed to set empty values.
            // TODO: Don't need to set all quesetions in survey, only ONE question.
            $_SESSION['survey_' . $surveyid][$info['fieldname']] = null;
        }
        // TODO: Language should be changed.
        $_SESSION['survey_'.$surveyid]['s_lang'] = $survey->language;
        $_SESSION['survey_'.$surveyid]['step'] = 0;
        $_SESSION['survey_'.$surveyid]['maxstep'] = 0;
        $_SESSION['survey_'.$surveyid]['prevstep'] = 2;

        $oQuestionRenderer = $oQuestion->getRenderererObject($aFieldArray, $changedType);
        $aRendered = $oQuestionRenderer->render();

        // Restore session.
        $_SESSION = $sessionBackup;

        $aSurveyInfo = $oQuestion->survey->attributes;
        $aQuestion = array_merge(
            $oQuestion->attributes,
            QuestionAttribute::model()->getQuestionAttributes($iQuestionId),
            ['answer' => $aRendered[0]],
            [
                'number' => $oQuestion->question_order,
                'code' => $oQuestion->title,
                'text' => isset($changedText['question'])
                ? $changedText['question']
                : $oQuestion->questionL10ns[$sLanguage]->question,
                'help' => [
                    'show' => true,
                    'text' => (isset($changedText['help'])
                        ? $changedText['help']
                        : $oQuestion->questionL10ns[$sLanguage]->help),
                ],
            ]
        );

//        unset($_SESSION['survey_' . $aSurveyInfo['sid']]);
        // If the template instance is not reset, it will load the last used one.
        // This may be correct, but oftentimes it is not and to not leave it for luck and chance => Reset
        Template::resetInstance();
        Template::getInstance($oQuestion->survey->template);
        App()->twigRenderer->renderTemplateForQuestionEditPreview(
            '/subviews/survey/question_container.twig',
            ['aSurveyInfo' => $aSurveyInfo, 'aQuestion' => $aQuestion, 'session' => $_SESSION],
            $root
        );
    }

    /**
     * Renders the top bar definition for questions as JSON document
     *
     * @param int $qid
     * @return void
     * @throws CException
     */
    public function getQuestionTopbar($qid = null)
    {
        $oQuestion = $this->getQuestionObject($qid);
        $sid = $oQuestion->sid;
        $gid = $oQuestion->gid;
        $qid = $oQuestion->qid;
        // TODO: Rename Variable for better readability.
        $qtypes = QuestionType::modelsAttributes();
        // TODO: Rename Variable for better readability.
        $qrrow = $oQuestion->attributes;
        $ownsSaveButton = true;
        $ownsImportButton = true;

        $hasCopyPermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'create');
        $hasUpdatePermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'update');
        $hasExportPermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'export');
        $hasDeletePermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'delete');
        $hasReadPermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'read');

        return App()->getController()->renderPartial(
            '/admin/survey/topbar/question_topbar',
            array(
                'oSurvey' => $oQuestion->survey,
                'sid' => $sid,
                'hasCopyPermission'   => $hasCopyPermission,
                'hasUpdatePermission' => $hasUpdatePermission,
                'hasExportPermission' => $hasExportPermission,
                'hasDeletePermission' => $hasDeletePermission,
                'hasReadPermission'   => $hasReadPermission,
                'gid' => $gid,
                'qid' => $qid,
                'qrrow' => $qrrow,
                'qtypes' => $qtypes,
                'ownsSaveButton' => $ownsSaveButton,
                'ownsImportButton' => $ownsImportButton,
            ),
            false,
            false
        );
    }

    /**
     * Creates a question object
     * This is either an instance of the placeholder model QuestionCreate for new questions,
     * or of Question for already existing ones
     *
     * @param int $iQuestionId
     * @param string $sQuestionType
     * @param int $gid
     * @return Question
     * @throws CException
     */
    private function getQuestionObject($iQuestionId = null, $sQuestionType = null, $gid = null)
    {
        $iSurveyId = App()->request->getParam('sid') ?? App()->request->getParam('surveyid');
        $oQuestion = Question::model()->findByPk($iQuestionId);

        if ($oQuestion == null) {
            $oQuestion = QuestionCreate::getInstance($iSurveyId, $sQuestionType);
        }

        if ($sQuestionType != null) {
            $oQuestion->type = $sQuestionType;
        }

        if ($gid != null) {
            $oQuestion->gid = $gid;
        }

        return $oQuestion;
    }


    /**
     * @todo document me
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return void
     */
    private function cleanAnsweroptions(&$oQuestion, &$dataSet)
    {
        $aAnsweroptions = $oQuestion->answers;
        array_walk(
            $aAnsweroptions,
            function ($oAnsweroption) use (&$dataSet) {
                $exists = false;
                foreach ($dataSet as $scaleId => $aAnsweroptions) {
                    foreach ($aAnsweroptions as $i => $aAnsweroptionDataSet) {
                        if (((is_numeric($aAnsweroptionDataSet['aid'])
                            && $oAnsweroption->aid == $aAnsweroptionDataSet['aid'])
                            || $oAnsweroption->code == $aAnsweroptionDataSet['code'])
                            && ($oAnsweroption->scale_id == $scaleId)
                        ) {
                            $exists = true;
                            $dataSet[$scaleId][$i]['aid'] = $oAnsweroption->aid;
                        }

                        if (!$exists) {
                            $oAnsweroption->delete();
                        }
                    }
                }
            }
        );
    }

    /**
     * @todo document me.
     *
     * @param Question $oQuestion
     * @return array
     */
    private function getCompiledQuestionData($oQuestion)
    {
        LimeExpressionManager::StartProcessingPage(false, true);
        $aQuestionDefinition = array_merge($oQuestion->attributes, ['typeInformation' => $oQuestion->questionType]);
        $oQuestionGroup = QuestionGroup::model()->findByPk($oQuestion->gid);
        $aQuestionGroupDefinition = array_merge($oQuestionGroup->attributes, $oQuestionGroup->questionGroupL10ns);

        $aScaledSubquestions = $oQuestion->getOrderedSubQuestions();
        foreach ($aScaledSubquestions as $scaleId => $aSubquestions) {
            $aScaledSubquestions[$scaleId] = array_map(function ($oSubQuestion) {
                return array_merge($oSubQuestion->attributes, $oSubQuestion->questionL10ns);
            }, $aSubquestions);
        }

        $aScaledAnswerOptions = $oQuestion->getOrderedAnswers();
        foreach ($aScaledAnswerOptions as $scaleId => $aAnswerOptions) {
            $aScaledAnswerOptions[$scaleId] = array_map(function ($oAnswerOption) {
                return array_merge($oAnswerOption->attributes, $oAnswerOption->answerL10ns);
            }, $aAnswerOptions);
        }
        $aReplacementData = [];
        $questioni10N = [];
        foreach ($oQuestion->questionL10ns as $lng => $oQuestionI10N) {
            $questioni10N[$lng] = $oQuestionI10N->attributes;

            templatereplace(
                $oQuestionI10N->question,
                array(),
                $aReplacementData,
                'Unspecified',
                false,
                $oQuestion->qid
            );

            $questioni10N[$lng]['question_expression'] = viewHelper::stripTagsEM(
                LimeExpressionManager::GetLastPrettyPrintExpression()
            );

            templatereplace($oQuestionI10N->help, array(), $aReplacementData, 'Unspecified', false, $oQuestion->qid);
            $questioni10N[$lng]['help_expression'] = viewHelper::stripTagsEM(
                LimeExpressionManager::GetLastPrettyPrintExpression()
            );
        }
        LimeExpressionManager::FinishProcessingPage();
        return [
            'question' => $aQuestionDefinition,
            'questiongroup' => $aQuestionGroupDefinition,
            'i10n' => $questioni10N,
            'subquestions' => $aScaledSubquestions,
            'answerOptions' => $aScaledAnswerOptions,
        ];
    }

    private function getCompiledSurveyInfo(&$oQuestion) {
        $oSurvey = $oQuestion->survey;
        $aQuestionTitles = $oCommand = Yii::app()->db->createCommand()
            ->select('title')
            ->from('{{questions}}')
            ->where('sid=:sid', [':sid'=>$oSurvey->sid])
            ->where('parent_qid=0')
            ->queryColumn();
        $isActive = $oSurvey->isActive;
        $questionCount = safecount($aQuestionTitles);
        $groupCount = safecount($oSurvey->groups);

        return [
            "aQuestionTitles" => $aQuestionTitles,
            "isActive" => $isActive,
            "questionCount" => $questionCount,
            "groupCount" => $groupCount,
        ];

    }

    /**
     * Renders template(s) wrapped in header and footer
     *
     * @param string $sAction Current action, the folder to fetch views from
     * @param string|array $aViewUrls View url(s)
     * @param array $aData Data to be passed on. Optional.
     * @param bool $sRenderFile
     * @throws CHttpException
     */
    protected function _renderWrappedTemplate(
        $sAction = 'survey/Question2',
        $aViewUrls = array(),
        $aData = array(),
        $sRenderFile = false
    ) {
        parent::_renderWrappedTemplate($sAction, $aViewUrls, $aData, $sRenderFile);
    }
}
