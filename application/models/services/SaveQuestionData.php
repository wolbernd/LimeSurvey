<?php

namespace LimeSurvey\Models\Services;

use Question;
use QuestionL10n;
use QuestionAttribute;
use Answer;
use AnswerL10n;
use Survey;
use SettingsUser;
use CHttpException;
use Exception;
use LSJsonException;
use LSHttpRequest;

final class SaveQuestionData
{
    /**
     * @var array
     */
    private $questionData;

    /**
     * @var Survey
     */
    private $survey;

    /**
     * @var LSHttpRequest
     */
    private $request;

    /**
     * TODO: DVO for questionData
     *
     * @param Survey $survey
     * @param LSHttpRequest $request
     * @param Question|null $question
     * @param array $questionData
     */
    public function __construct(Survey $survey, LSHttpRequest $request, array $questionData)
    {
        $this->survey = $survey;
        $this->request = $request;
        $this->questionData = $questionData;
    }

    /**
     * Save or update all question data for this $question
     * If $question is null, create a new question. Otherwise, update the question.
     *
     * @param Question|null $question
     * @return void
     */
    public function save($question = null)
    {
        if (
            !isset($this->questionData['question']['qid'])
            || $this->questionData['question']['qid'] == 0
        ) {
            $this->questionData['question']['qid'] = null;
            $question = $this->storeNewQuestionData($this->questionData['question']);
        } else {
            if (empty($question)) {
                throw new CHttpException(
                    500,
                    "question cannot be null when updating question"
                );
            }
            // Store changes to the actual question data, by either storing it, or updating an old one
            $this->updateQuestionData($question, $this->questionData['question']);
        }

        // Apply the changes to general settings, advanced settings and translations
        $setApplied = [];

        $setApplied['questionI10N'] = $this->applyL10n($question, $this->questionData['questionI10N']);

        $setApplied['advancedSettings'] = $this->unparseAndSetAdvancedOptions(
            $question,
            $this->questionData['advancedSettings']
        );

        $setApplied['question'] = $this->unparseAndSetGeneralOptions(
            $question,
            $this->questionData['question']
        );

        // save advanced attributes default values for given question type
        if (array_key_exists('save_as_default', $this->questionData['question'])
            && $this->questionData['question']['save_as_default'] == 'Y') {
            SettingsUser::setUserSetting(
                'question_default_values_' . $this->questionData['question']['type'],
                ls_json_encode($this->questionData['advancedSettings'])
            );
        } elseif (array_key_exists('clear_default', $this->questionData['question'])
            && $this->questionData['question']['clear_default'] == 'Y') {
            SettingsUser::deleteUserSetting('question_default_values_' . $this->questionData['question']['type']);
        }

        // Clean subquestions and answer options before save.
        // NB: Still inside a database transaction.
        if ($question->survey->active == 'N') {
            $question->deleteAllAnswers();
            $question->deleteAllSubquestions();
            // If question type has subquestions, save them.
            if ($question->questionType->subquestions > 0) {
                $this->storeSubquestions(
                    $question,
                    $this->request->getPost('subquestions')
                );
            }
            // If question type has answeroptions, save them.
            if ($question->questionType->answerscales > 0) {
                $this->storeAnswerOptions(
                    $question,
                    $this->request->getPost('answeroptions')
                );
            }
        } else {
            // TODO: Update subquestions.
            // TODO: Update answer options.
        }
    }

    /**
     * Method to store and filter questionData for a new question
     *
     * todo: move to model or service class
     *
     * @param array $questionDataQuestion what is inside this array ??
     * @param boolean $subquestion ???
     * @return Question
     * @throws CHttpException
     */
    private function storeNewQuestionData(array $questionDataQuestion, $subquestion = false)
    {
        // TODO: Don't read request from private methods.
        $iQuestionGroupId = (int) $this->request->getParam('gid'); //the group id the question belongs to
        $type = SettingsUser::getUserSettingValue(
            'preselectquestiontype',
            null,
            null,
            null,
            App()->getConfig('preselectquestiontype')
        );

        if (isset($questionDataQuestion['same_default'])) {
            if ($questionDataQuestion['same_default'] == 1) {
                $questionDataQuestion['same_default'] = 0;
            } else {
                $questionDataQuestion['same_default'] = 1;
            }
        }

        $questionDataQuestion = array_merge(
            [
                'sid'        => $this->survey->sid,
                'gid'        => $iQuestionGroupId,
                'type'       => $type,
                'other'      => 'N',
                'mandatory'  => 'N',
                'relevance'  => 1,
                'group_name' => '',
                'modulename' => '',
                'encrypted'  => 'N'
            ],
            $questionDataQuestion
        );
        unset($questionDataQuestion['qid']);

        if ($subquestion) {
            foreach ($this->survey->allLanguages as $sLanguage) {
                unset($questionDataQuestion[$sLanguage]);
            }
        } else {
            $questionDataQuestion['question_order'] = getMaxQuestionOrder($iQuestionGroupId);
        }

        $question = new Question();
        $question->setAttributes($questionDataQuestion, false);

        //set the question_order the highest existing number +1, if no question exists for the group
        //set the question_order to 1
        $highestOrderNumber = Question::getHighestQuestionOrderNumberInGroup($iQuestionGroupId);
        if ($highestOrderNumber === null) { //this means there is no question inside this group ...
            $question->question_order = Question::START_SORTING_VALUE;
        } else {
            $question->question_order = $highestOrderNumber + 1;
        }

        $saved = $question->save();
        if ($saved == false) {
            throw new LSJsonException(
                500,
                gT('Could not save question') . " " . PHP_EOL
                . print_r($question->getErrors(), true),
                0,
                null,
                true
            );
        }

        $i10N = [];
        foreach ($this->survey->allLanguages as $sLanguage) {
            $i10N[$sLanguage] = new QuestionL10n();
            $i10N[$sLanguage]->setAttributes(
                [
                    'qid'      => $question->qid,
                    'language' => $sLanguage,
                    'question' => '',
                    'help'     => '',
                    'script'   => '',
                ],
                false
            );
            $i10N[$sLanguage]->save();
        }

        return $question;
    }

    /**
     * Method to store and filter questionData for editing a question
     *
     * @param Question $question
     * @param array $questionDataQuestion
     * @return void
     * @throws CHttpException
     */
    private function updateQuestionData($question, $questionDataQuestion)
    {
        if (isset($questionDataQuestion['same_default'])) {
            if ($questionDataQuestion['same_default'] == 1) {
                $questionDataQuestion['same_default'] = 0;
            } else {
                $questionDataQuestion['same_default'] = 1;
            }
        }

        $question->setAttributes($questionDataQuestion, false);
        if (!$question->save()) {
            throw new LSJsonException(
                500,
                "Update failed, could not save. ERRORS:<br/>"
                . implode(", ", $question->getErrors()['title']),
                0,
                null,
                true
            );
        }
    }

    /**
     * @todo document me
     *
     * @param Question $question
     * @param array $dataSet
     * @return boolean
     * @throws CHttpException
     */
    private function applyL10n($question, $dataSet)
    {
        foreach ($dataSet as $sLanguage => $aI10NBlock) {
            $i10N = QuestionL10n::model()->findByAttributes(['qid' => $question->qid, 'language' => $sLanguage]);
            if (empty($i10N)) {
                throw new Exception('Found no L10n object');
            }
            $i10N->setAttributes(
                [
                    'question' => $aI10NBlock['question'],
                    'help'     => $aI10NBlock['help'],
                    'script'   => $aI10NBlock['script'],
                ],
                false
            );
            if (!$i10N->save()) {
                throw new CHttpException(500, gT("Could not store translation"));
            }
        }

        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $question
     * @param array $dataSet
     * @return boolean
     * @throws CHttpException
     */
    private function unparseAndSetGeneralOptions($question, $dataSet)
    {
        $aQuestionBaseAttributes = $question->attributes;

        foreach ($dataSet as $sAttributeKey => $attributeValue) {
            if ($sAttributeKey === 'debug' || !isset($attributeValue)) {
                continue;
            }
            if (array_key_exists($sAttributeKey, $aQuestionBaseAttributes)) {
                $question->$sAttributeKey = $attributeValue;
            } elseif (!QuestionAttribute::model()->setQuestionAttribute(
                $question->qid,
                $sAttributeKey,
                $attributeValue
            )) {
                throw new CHttpException(500, gT("Could not store general options"));
            }
        }

        if (!$question->save()) {
            throw new CHttpException(
                500,
                gT("Could not store question after general options") . PHP_EOL
                . print_r($question->getErrors(), true)
            );
        }

        return true;
    }

    /**
     * @todo document me
     *
     * @param Question $question
     * @param array $dataSet
     * @return boolean
     * @throws CHttpException
     */
    private function unparseAndSetAdvancedOptions($question, $dataSet)
    {
        $aQuestionBaseAttributes = $question->attributes;

        foreach ($dataSet as $sAttributeCategory => $aAttributeCategorySettings) {
            if ($sAttributeCategory === 'debug') {
                continue;
            }
            foreach ($aAttributeCategorySettings as $sAttributeKey => $attributeValue) {
                $newValue = $attributeValue;

                // Set default value if empty.
                // TODO: Default value
                if ($newValue === ""
                    && isset($attributeValue['aFormElementOptions']['default'])) {
                    $newValue = $attributeValue['aFormElementOptions']['default'];
                }

                if (is_array($newValue)) {
                    foreach ($newValue as $lngKey => $content) {
                        if ($lngKey === 'expression') {
                            continue;
                        }
                        if (!QuestionAttribute::model()->setQuestionAttributeWithLanguage(
                            $question->qid,
                            $sAttributeKey,
                            $content,
                            $lngKey
                        )) {
                            throw new CHttpException(500, gT("Could not store advanced options"));
                        }
                    }
                } elseif (array_key_exists($sAttributeKey, $aQuestionBaseAttributes)) {
                    $question->$sAttributeKey = $newValue;
                } elseif (!QuestionAttribute::model()->setQuestionAttribute(
                    $question->qid,
                    $sAttributeKey,
                    $newValue
                )) {
                    throw new CHttpException(500, gT("Could not store advanced options"));
                }
            }
        }

        if (!$question->save()) {
            throw new CHttpException(500, gT("Could not store advanced options"));
        }

        return true;
    }

    /**
     * Save subquestion.
     * Used when survey is *not* activated.
     *
     * @param Question $question
     * @param array $subquestionsArray Data from request.
     * @return void
     * @throws CHttpException
     */
    private function storeSubquestions($question, $subquestionsArray)
    {
        $questionOrder = 0;
        foreach ($subquestionsArray as $subquestionId => $subquestionArray) {
            if ($subquestionId == 0 or strpos($subquestionId, 'new') === 0) {
                // New subquestion
            } else {
                // Updating subquestion
            }
            foreach ($subquestionArray as $scaleId => $data) {
                $subquestion = new Question();
                $subquestion->sid        = $question->sid;
                $subquestion->gid        = $question->gid;
                $subquestion->parent_qid = $question->qid;
                $subquestion->question_order = $questionOrder;
                $subquestion->title      = $data['code'];
                if ($scaleId === 0) {
                    $subquestion->relevance  = $data['relevance'];
                }
                $subquestion->scale_id   = $scaleId;
                if (!$subquestion->save()) {
                    throw new CHttpException(
                        500,
                        gT("Could not save subquestion") . PHP_EOL
                        . print_r($subquestion->getErrors(), true)
                    );
                }
                $subquestion->refresh();
                foreach ($data['subquestionl10n'] as $lang => $questionText) {
                    $l10n = new QuestionL10n();
                    $l10n->qid = $subquestion->qid;
                    $l10n->language = $lang;
                    $l10n->question = $questionText;
                    if (!$l10n->save()) {
                        throw new CHttpException(
                            500,
                            gT("Could not save subquestion") . PHP_EOL
                            . print_r($l10n->getErrors(), true)
                        );
                    }
                }
            }
        }
    }

    /**
     * Store new answer options.
     * Different from update during active survey?
     *
     * @param Question $question
     * @param array $answerOptionsArray
     * @return void
     * @throws CHttpException
     */
    private function storeAnswerOptions($question, $answerOptionsArray)
    {
        $i = 0;
        foreach ($answerOptionsArray as $answerOptionId => $answerOptionArray) {
            foreach ($answerOptionArray as $scaleId => $data) {
                if (!isset($data['code'])) {
                    throw new Exception(
                        'code is not set in data: ' . json_encode($data)
                    );
                }
                $answer = $this->getNewAnswer();
                $answer->qid = $question->qid;
                $answer->code = $data['code'];
                $answer->sortorder = $i;
                $i++;
                if (isset($data['assessment'])) {
                    $answer->assessment_value = $data['assessment'];
                } else {
                    $answer->assessment_value = 0;
                }
                $answer->scale_id = $scaleId;
                if (!$answer->save()) {
                    throw new CHttpException(
                        500,
                        gT("Could not save answer option") . PHP_EOL
                        . print_r($answer->getErrors(), true)
                    );
                }
                $answer->refresh();
                foreach ($data['answeroptionl10n'] as $lang => $answerOptionText) {
                    $l10n = $this->getNewAnswerL10n();
                    $l10n->aid = $answer->aid;
                    $l10n->language = $lang;
                    $l10n->answer = $answerOptionText;
                    if (!$l10n->save()) {
                        throw new CHttpException(
                            500,
                            gT("Could not save answer option") . PHP_EOL
                            . print_r($l10n->getErrors(), true)
                        );
                    }
                }
            }
        }
    }

    /**
     * @return Answer
     */
    private function getNewAnswer()
    {
        return new Answer();
    }

    /**
     * @return AnswerL10n
     */
    private function getNewAnswerL10n()
    {
        return new AnswerL10n();
    }
}
