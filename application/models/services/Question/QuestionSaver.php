<?php

namespace LimeSurvey\Models\Services\Question;

class QuestionSaver
{
    /** @var \CHttpRequest */
    private $request;

    /** @var \Survey */
    private $survey;

    /** @var \Question */
    private $questionModel;

    /** @var \QuestionL10n */
    private $questionl10nModel;

    /** @var \CController */
    private $controller;

    /**
     * @param \CHttpRequest $request
     * @param \Survey $survey
     * @param \Question $questionModel
     * @param \QuestionL10n $questionl10nModel
     *
     * @todo QuestionAttribute, Answer, AnswerL10n, LimeExpressionManager, DefaultValue, DefaultValueL10n, QuestionCreate
     */
    public function __construct(
        \CHttpRequest $request,
        \Survey $survey,
        \Question $questionModel,
        \QuestionL10n $questionl10nModel,
        \CController $controller
    ) {
        $this->request = $request;
        $this->survey = $survey;
        $this->questionModel = $questionModel;
        $this->questionl10nModel = $questionl10nModel;
        $this->controller = $controller;
    }

    /**
     * Action called by the FE editor when a save is triggered.
     *
     * @param int $sid Survey id
     *
     * @return void
     * @throws CException
     */
    public function saveQuestionData(int $sid)
    {
        $questionData = $this->request->getPost('questionData', []);
        $isNewQuestion = false;
        $questionCopy = (boolean) $this->request->getPost('questionCopy');
        $questionCopySettings = $this->request->getPost('copySettings', []);
        $questionCopySettings = array_map(
            function ($value) {
                return !!$value;
            },
            $questionCopySettings
        );

        // Store changes to the actual question data, by either storing it, or updating an old one
        $oQuestion = $this->questionModel->findByPk($questionData['question']['qid']);
        if ($oQuestion == null || $questionCopy == true) {
            $oQuestion = $this->storeNewQuestionData($questionData['question']);
            // TODO: Unused variable
            $isNewQuestion = true;
        } else {
            $oQuestion = $this->updateQuestionData($oQuestion, $questionData['question']);
        }

        /*
         * Setting up a try/catch scenario to delete a copied/created question,
         * in case the storing of the peripherals breaks
         */
        try {
            // Apply the changes to general settings, advanced settings and translations
            $setApplied = [];

            $setApplied['questionI10N'] = $this->applyI10N($oQuestion, $questionData['questionI10N']);

            $setApplied['generalSettings'] = $this->unparseAndSetGeneralOptions(
                $oQuestion,
                $questionData['generalSettings']
            );

            if (!($questionCopy === true && $questionCopySettings['copyAdvancedOptions'] == false)) {
                $setApplied['advancedSettings'] = $this->unparseAndSetAdvancedOptions(
                    $oQuestion,
                    $questionData['advancedSettings']
                );
            }

            if (!($questionCopy === true && $questionCopySettings['copyDefaultAnswers'] == false)) {
                $setApplied['defaultAnswers'] = $this->copyDefaultAnswers($oQuestion, $questionData['question']['qid']);
            }


            // save advanced attributes default values for given question type
            if (array_key_exists('save_as_default', $questionData['generalSettings'])
                && $questionData['generalSettings']['save_as_default']['formElementValue'] == 'Y') {
                /*
                SettingsUser::setUserSetting(
                    'question_default_values_' . $questionData['question']['type'],
                    ls_json_encode($questionData['advancedSettings'])
                );
                 */
            } elseif (array_key_exists('clear_default', $questionData['generalSettings'])
                && $questionData['generalSettings']['clear_default']['formElementValue'] == 'Y') {
                /*
                SettingsUser::deleteUserSetting('question_default_values_' . $questionData['question']['type'], '');
                 */
            }

            // If set, store subquestions
            if (isset($questionData['scaledSubquestions'])) {
                if (!($questionCopy === true && $questionCopySettings['copySubquestions'] == false)) {
                    $setApplied['scaledSubquestions'] = $this->storeSubquestions(
                        $oQuestion,
                        $questionData['scaledSubquestions'],
                        $questionCopy
                    );
                }
            }

            // If set, store answer options
            if (isset($questionData['scaledAnswerOptions'])) {
                if (!($questionCopy === true && $questionCopySettings['copyAnswerOptions'] == false)) {
                    $setApplied['scaledAnswerOptions'] = $this->storeAnswerOptions(
                        $oQuestion,
                        $questionData['scaledAnswerOptions'],
                        $questionCopy
                    );
                }
            }
        } catch (\CException $ex) {
            throw new \LSJsonException(
                500,
                gT('Question has been stored, but an error happened: ')."\n".$ex->getMessage(),
                0,
                App()->createUrl(
                    'admin/questioneditor/sa/view/',
                    ["surveyid"=> $sid, 'gid' => $oQuestion->gid, 'qid'=> $oQuestion->qid]
                )
            );
        }

        // Compile the newly stored data to update the FE
        $oNewQuestion = $this->questionModel->findByPk($oQuestion->qid);
        $aCompiledQuestionData = $this->getCompiledQuestionData($oNewQuestion);
        $aQuestionAttributeData = $this->getQuestionAttributeData($oQuestion->qid);
        $aQuestionGeneralOptions = $this->getGeneralOptions(
            $oQuestion->qid,
            $oQuestion->gid,
            $aQuestionAttributeData['question_template']
        );
        $aAdvancedOptions = $this->getAdvancedOptions($oQuestion);

        // Return a JSON document with the newly stored question data
        return [
            'success' => array_reduce(
                $setApplied,
                function ($coll, $it) {
                    return $coll && $it;
                },
                true
            ),
            'message' => ($questionCopy === true
                ? gT('Question successfully copied')
                : gT('Question successfully stored')
            ),
            'successDetail' => $setApplied,
            'questionId' => $oQuestion->qid,
            'redirect' => $this->controller->createUrl(
                'admin/questioneditor/sa/view/',
                [
                    'surveyid' => $sid,
                    'gid' => $oQuestion->gid,
                    'qid' => $oQuestion->qid,
                ]
            ),
            'newQuestionDetails' => [
                "question"            => $aCompiledQuestionData['question'],
                "scaledSubquestions"  => $aCompiledQuestionData['subquestions'],
                "scaledAnswerOptions" => $aCompiledQuestionData['answerOptions'],
                "questionI10N"        => $aCompiledQuestionData['i10n'],
                "questionAttributes"  => $aQuestionAttributeData,
                "generalSettings"     => $aQuestionGeneralOptions,
                "advancedSettings"    => $aAdvancedOptions,
            ],
            'transfer' => $questionData,
        ];
    }

    /**
     * Method to store and filter questionData for a new question
     *
     * @param array $aQuestionData
     * @param boolean $subquestion
     * @return \Question
     * @throws \CHttpException
     */
    private function storeNewQuestionData($aQuestionData = null, $subquestion = false)
    {
        $iQuestionGroupId = $this->request->getParam('gid');
        /*
        $type = SettingsUser::getUserSettingValue(
            'preselectquestiontype',
            null,
            null,
            null,
            App()->getConfig('preselectquestiontype')
        );
         */

        if (isset($aQuestionData['same_default'])) {
            if ($aQuestionData['same_default'] == 1) {
                $aQuestionData['same_default'] =0;
            } else {
                $aQuestionData['same_default'] =1;
            }
        }

        $aQuestionData = array_merge([
            'sid' => $this->survey->sid,
            'gid' => $this->request->getParam('gid'),
            'type' => $type,
            'other' => 'N',
            'mandatory' => 'N',
            'relevance' => 1,
            'group_name' => '',
            'modulename' => '',
        ], $aQuestionData);
        unset($aQuestionData['qid']);

        if ($subquestion) {
            foreach ($this->survey->allLanguages as $sLanguage) {
                unset($aQuestionData[$sLanguage]);
            }
        } else {
            $aQuestionData['question_order'] = \getMaxQuestionOrder($iQuestionGroupId);
        }

        $oQuestion = new \Question();
        $oQuestion->setAttributes($aQuestionData, false);
        if ($oQuestion == null) {
            throw new \LSJsonException(
                500,
                gT("Question creation failed, input array malformed or invalid"),
                0,
                null,
                true
            );
        }

        $saved = $oQuestion->save();
        if ($saved == false) {
            throw new \LSJsonException(
                500,
                "Object creation failed, couldn't save.\n ERRORS:\n"
                . print_r($oQuestion->getErrors(), true),
                0,
                null,
                true
            );
        }

        $i10N = [];
        foreach ($this->survey->allLanguages as $sLanguage) {
            $i10N[$sLanguage] = new \QuestionL10n();
            $i10N[$sLanguage]->setAttributes([
                'qid' => $oQuestion->qid,
                'language' => $sLanguage,
                'question' => '',
                'help' => '',
            ], false);
            $i10N[$sLanguage]->save();
        }

        return $oQuestion;
    }

    /**
     * Method to store and filter questionData for editing a question
     *
     * @param Question $oQuestion
     * @param array $aQuestionData
     * @return Question
     * @throws \CHttpException
     */
    private function updateQuestionData(&$oQuestion, $aQuestionData)
    {
        //todo something wrong in frontend ...

        if (isset($aQuestionData['same_default'])) {
            if ($aQuestionData['same_default'] == 1) {
                $aQuestionData['same_default'] =0;
            } else {
                $aQuestionData['same_default'] =1;
            }
        }

        $oQuestion->setAttributes($aQuestionData, false);
        if ($oQuestion == null) {
            throw new \LSJsonException(
                500,
                gT("Question update failed, input array malformed or invalid"),
                0,
                null,
                true
            );
        }

        $saved = $oQuestion->save();
        if ($saved == false) {
            throw new \LSJsonException(
                500,
                "Update failed, could not save. ERRORS:<br/>"
                .implode(", ", $oQuestion->getErrors()['title']),
                0,
                null,
                true
            );
        }
        return $oQuestion;
    }

    /**
     * @todo document me.
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return boolean
     * @throws CException
     * @throws \CHttpException
     */
    private function storeSubquestions($oQuestion, $dataSet, $isCopyProcess = false)
    {
        $this->cleanSubquestions($oQuestion, $dataSet);
        foreach ($dataSet as $aSubquestions) {
            foreach ($aSubquestions as $aSubquestionDataSet) {
                $oSubQuestion = $this->questionModel->findByPk($aSubquestionDataSet['qid']);
                if ($oSubQuestion != null && !$isCopyProcess) {
                    $oSubQuestion = $this->updateQuestionData($oSubQuestion, $aSubquestionDataSet);
                } elseif (!$this->survey->isActive) {
                    $aSubquestionDataSet['parent_qid'] = $oQuestion->qid;
                    $oSubQuestion = $this->storeNewQuestionData($aSubquestionDataSet, true);
                }
                $this->applyI10NSubquestion($oSubQuestion, $aSubquestionDataSet);
            }
        }

        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return boolean
     * @throws \CHttpException
     */
    private function applyI10NSubquestion($oQuestion, $dataSet)
    {
        foreach ($oQuestion->survey->allLanguages as $sLanguage) {
            $aI10NBlock = $dataSet[$sLanguage];
            $i10N = $this->questionl10nModel->findByAttributes(['qid' => $oQuestion->qid, 'language' => $sLanguage]);
            $i10N->setAttributes([
                'question' => $aI10NBlock['question'],
                'help' => $aI10NBlock['help'],
            ], false);
            if (!$i10N->save()) {
                throw new \CHttpException(500, gT("Could not store translation for subquestion"));
            }
        }

        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return boolean
     * @throws \CHttpException
     */
    private function applyI10N($oQuestion, $dataSet)
    {
        foreach ($dataSet as $sLanguage => $aI10NBlock) {
            $i10N = $this->questionl10nModel->findByAttributes(['qid' => $oQuestion->qid, 'language' => $sLanguage]);
            $i10N->setAttributes([
                'question' => $aI10NBlock['question'],
                'help' => $aI10NBlock['help'],
                'script' => $aI10NBlock['script'],
            ], false);
            if (!$i10N->save()) {
                throw new \CHttpException(500, gT("Could not store translation"));
            }
        }

        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return boolean
     * @throws \CHttpException
     */
    private function unparseAndSetGeneralOptions($oQuestion, $dataSet)
    {
        $aQuestionBaseAttributes = $oQuestion->attributes;

        foreach ($dataSet as $sAttributeKey => $aAttributeValueArray) {
            if ($sAttributeKey === 'debug' || !isset($aAttributeValueArray['formElementValue'])) {
                continue;
            }
            if (array_key_exists($sAttributeKey, $aQuestionBaseAttributes)) {
                $oQuestion->$sAttributeKey = $aAttributeValueArray['formElementValue'];
            } else {
                if (!\QuestionAttribute::model()->setQuestionAttribute(
                    $oQuestion->qid,
                    $sAttributeKey,
                    $aAttributeValueArray['formElementValue']
                )) {
                    throw new \CHttpException(500, gT("Could not store general options"));
                }
            }
        }

        if (!$oQuestion->save()) {
            throw new \CHttpException(500, gT("Could not store general options"));
        }

        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return boolean
     * @throws \CHttpException
     */
    private function unparseAndSetAdvancedOptions($oQuestion, $dataSet)
    {
        $aQuestionBaseAttributes = $oQuestion->attributes;

        foreach ($dataSet as $sAttributeCategory => $aAttributeCategorySettings) {
            if ($sAttributeCategory === 'debug') {
                continue;
            }
            foreach ($aAttributeCategorySettings as $sAttributeKey => $aAttributeValueArray) {
                if (!isset($aAttributeValueArray['formElementValue'])) {
                    continue;
                }
                $newValue = $aAttributeValueArray['formElementValue'];

                // Set default value if empty.
                if ($newValue === ""
                    && isset($aAttributeValueArray['aFormElementOptions']['default'])) {
                    $newValue = $aAttributeValueArray['aFormElementOptions']['default'];
                }

                if (is_array($newValue)) {
                    foreach ($newValue as $lngKey => $content) {
                        if ($lngKey == 'expression') {
                            continue;
                        }
                        if (!\QuestionAttribute::model()->setQuestionAttributeWithLanguage(
                            $oQuestion->qid,
                            $sAttributeKey,
                            $content,
                            $lngKey
                        )) {
                            throw new \CHttpException(500, gT("Could not store advanced options"));
                        }
                    }
                } else {
                    if (array_key_exists($sAttributeKey, $aQuestionBaseAttributes)) {
                        $oQuestion->$sAttributeKey = $newValue;
                    } else {
                        if (!\QuestionAttribute::model()->setQuestionAttribute(
                            $oQuestion->qid,
                            $sAttributeKey,
                            $newValue
                        )) {
                            throw new \CHttpException(500, gT("Could not store advanced options"));
                        }
                    }
                }
            }
        }

        if (!$oQuestion->save()) {
            throw new \CHttpException(500, gT("Could not store advanced options"));
        }

        return true;
    }

    /**
     * Copies the default value(s) set for a question
     *
     * @param Question $oQuestion
     * @param integer $oldQid
     *
     * @return boolean
     * @throws CHttpException
     */
    private function copyDefaultAnswers($oQuestion, $oldQid)
    {
        if (empty($oldQid)) {
            return false;
        }

        $oOldDefaultValues = \DefaultValue::model()->with('defaultValueL10ns')->findAllByAttributes(['qid' => $oldQid]);

        $setApplied['defaultValues'] = array_reduce(
            $oOldDefaultValues,
            function ($collector, $oDefaultValue) use ($oQuestion) {
                $oNewDefaultValue = new \DefaultValue();
                $oNewDefaultValue->setAttributes($oDefaultValue->attributes, false);
                $oNewDefaultValue->dvid = null;
                $oNewDefaultValue->qid = $oQuestion->qid;

                if (!$oNewDefaultValue->save()) {
                    throw new \CHttpException(
                        500,
                        "Could not save default values. ERRORS:"
                        . print_r($oQuestion->getErrors(), true)
                    );
                }

                foreach ($oDefaultValue->defaultValueL10ns as $oDefaultValueL10n) {
                    $oNewDefaultValueL10n = new \DefaultValueL10n();
                    $oNewDefaultValueL10n->setAttributes($oDefaultValueL10n->attributes, false);
                    $oNewDefaultValueL10n->id = null;
                    $oNewDefaultValueL10n->dvid = $oNewDefaultValue->dvid;
                    if (!$oNewDefaultValueL10n->save()) {
                        throw new \CHttpException(
                            500,
                            "Could not save default value I10Ns. ERRORS:"
                            . print_r($oQuestion->getErrors(), true)
                        );
                    }
                }

                return true;
            },
            true
        );
        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return boolean
     * @throws CException
     * @throws CHttpException
     */
    private function storeAnswerOptions($oQuestion, $dataSet, $isCopyProcess = false)
    {
        $this->cleanAnsweroptions($oQuestion, $dataSet);
        foreach ($dataSet as $aAnswerOptions) {
            foreach ($aAnswerOptions as $iScaleId => $aAnswerOptionDataSet) {
                $aAnswerOptionDataSet['sortorder'] = (int) $aAnswerOptionDataSet['sortorder'];
                $oAnswer = \Answer::model()->findByPk($aAnswerOptionDataSet['aid']);
                if ($oAnswer == null || $isCopyProcess) {
                    $oAnswer = new \Answer();
                    $oAnswer->qid = $oQuestion->qid;
                    unset($aAnswerOptionDataSet['aid']);
                    unset($aAnswerOptionDataSet['qid']);
                }
        
                $codeIsEmpty = (!isset($aAnswerOptionDataSet['code']));
                if ($codeIsEmpty) {
                    throw new \CHttpException(
                        500,
                        "Answer option code cannot be empty"
                    );
                }
                $oAnswer->setAttributes($aAnswerOptionDataSet);
                $answerSaved = $oAnswer->save();
                if (!$answerSaved) {
                    throw new \CHttpException(
                        "Answer option couldn't be saved. Error: "
                        . print_r($oAnswer->getErrors(), true)
                    );
                }
                $this->applyAnswerI10N($oAnswer, $oQuestion, $aAnswerOptionDataSet);
            }
        }
        return true;
    }

    /**
     * @todo document me
     *
     * @param Answer $oAnswer
     * @param Question $oQuestion
     * @param array $dataSet
     *
     * @return boolean
     * @throws \CHttpException
     */
    private function applyAnswerI10N($oAnswer, $oQuestion, $dataSet)
    {
        foreach ($oQuestion->survey->allLanguages as $sLanguage) {
            $i10N = \AnswerL10n::model()->findByAttributes(['aid' => $oAnswer->aid, 'language' => $sLanguage]);
            if ($i10N == null) {
                $i10N = new \AnswerL10n();
                $i10N->setAttributes([
                    'aid' => $oAnswer->aid,
                    'language' => $sLanguage,
                ], false);
            }
            $i10N->setAttributes([
                'answer' => $dataSet[$sLanguage]['answer'],
            ], false);

            if (!$i10N->save()) {
                throw new \CHttpException(500, gT("Could not store translation for answer option"));
            }
        }

        return true;
    }

    /**
     * @todo document me.
     * @todo Duplication from controller
     *
     * @param Question $oQuestion
     * @return array
     */
    private function getCompiledQuestionData($oQuestion)
    {
        \LimeExpressionManager::StartProcessingPage(false, true);
        $aQuestionDefinition = array_merge($oQuestion->attributes, ['typeInformation' => $oQuestion->questionType]);
        $oQuestionGroup = \QuestionGroup::model()->findByPk($oQuestion->gid);
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

            \templatereplace(
                $oQuestionI10N->question,
                array(),
                $aReplacementData,
                'Unspecified',
                false,
                $oQuestion->qid
            );

            $questioni10N[$lng]['question_expression'] = \viewHelper::stripTagsEM(
                \LimeExpressionManager::GetLastPrettyPrintExpression()
            );

            \templatereplace($oQuestionI10N->help, array(), $aReplacementData, 'Unspecified', false, $oQuestion->qid);
            $questioni10N[$lng]['help_expression'] = \viewHelper::stripTagsEM(
                \LimeExpressionManager::GetLastPrettyPrintExpression()
            );
        }
        \LimeExpressionManager::FinishProcessingPage();
        return [
            'question' => $aQuestionDefinition,
            'questiongroup' => $aQuestionGroupDefinition,
            'i10n' => $questioni10N,
            'subquestions' => $aScaledSubquestions,
            'answerOptions' => $aScaledAnswerOptions,
        ];
    }

    /**
     * Either renders a JSON document of the question attribute array, or returns it
     *
     * @param int $iQuestionId
     *
     * @return void|array
     * @throws CException
     */
    private function getQuestionAttributeData($iQuestionId = null)
    {
        $iQuestionId = (int) $iQuestionId;
        $aQuestionAttributes = \QuestionAttribute::model()->getQuestionAttributes($iQuestionId);
        return $aQuestionAttributes;
    }

    /**
     * @todo document me
     *
     * @param int $iQuestionId
     * @param int $gid
     * @param string $question_template
     *
     * @return void|array
     * @throws CException
     */
    private function getGeneralOptions($iQuestionId = null, $gid = null, $question_template = 'core')
    {
        $oQuestion = $this->getQuestionObject($iQuestionId, null, $gid);
        $aGeneralOptionsArray = $oQuestion
            ->getDataSetObject()
            ->getGeneralSettingsArray($oQuestion->qid, null, null, $question_template);

        return $aGeneralOptionsArray;
    }

    /**
     * @todo document me
     *
     * @param \Question $oQuestion
     * @return void|array
     * @throws CException
     */
    public function getAdvancedOptions($oQuestion)
    {
        $question_template = 'core';
        $aAdvancedOptionsArray = $oQuestion->getDataSetObject()
            ->getAdvancedOptions($oQuestion->qid, null, null, $question_template);
        return $aAdvancedOptionsArray;
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
        $iSurveyId = $this->request->getParam('sid') ?? App()->request->getParam('surveyid');
        $oQuestion = $this->questionModel->findByPk($iQuestionId);

        if ($oQuestion == null) {
            $oQuestion = \QuestionCreate::getInstance($iSurveyId, $sQuestionType);
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
     * @todo document me.
     *
     * @param Question $oQuestion
     * @param array $dataSet
     * @return void
     * @todo PHPDoc description
     */
    private function cleanSubquestions($oQuestion, &$dataSet)
    {
        $aSubquestions = $oQuestion->subquestions;
        array_walk(
            $aSubquestions,
            function ($oSubquestion) use ($dataSet, $oQuestion) {
                $exists = false;
                foreach ($dataSet as $scaleId => $aSubquestions) {
                    foreach ($aSubquestions as $i => $aSubquestionDataSet) {
                        if ($oSubquestion->qid == $aSubquestionDataSet['qid']
                            || (($oSubquestion->title == $aSubquestionDataSet['title'])
                                && ($oSubquestion->scale_id == $scaleId))
                        ) {
                            $exists = true;
                            $dataSet[$scaleId][$i]['qid'] = $oSubquestion->qid;
                        }

                        if (!$exists && !$this->survey->isActive) {
                            $oSubquestion->delete();
                        }
                    }
                }
            }
        );
    }

}
