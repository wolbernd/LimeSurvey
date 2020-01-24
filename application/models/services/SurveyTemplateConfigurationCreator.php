<?php

namespace LimeSurvey\Models\Services;

/**
 * For a given survey, it checks if its theme have a all the needed configuration entries (survey + survey group).
 * Else, it will create it.
 * @TODO: recursivity for survey group
 */
class SurveyTemplateConfigurationCreator
{
    /** @var Survey */
    private $surveyModel;

    /** @var TemplateConfiguration */
    private $templateConfigModel;

    /**
     * @param \Survey $surveyModel
     */
    public function __construct(\Survey $surveyModel, \TemplateConfiguration $templateConfigModel)
    {
        $this->surveyModel = $surveyModel;
        $this->templateConfigModel = $templateConfigModel;
    }

    /**
     * @param int $surveyId
     * @return TemplateConfiguration
     */
    public function checkAndcreateSurveyConfig(int $surveyId)
    {
        //if a template name is given also check against that
        $survey = $this->surveyModel->findByPk($surveyId);
        $templateName  = $survey->template;
        $surveyGroupId = $survey->gsid;

        $criteria = new \CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->addCondition('template_name=:template_name');
        $criteria->params = array('sid' => $surveyId, 'template_name' => $templateName);

        $templateConfig = $this->templateConfigModel->find($criteria);

        // TODO: Move to SurveyGroup creation, right now the 'lazy loading' approach is ok.
        if (!is_a($templateConfig, 'TemplateConfiguration') && $templateName != null) {
            $templateConfig = $this->templateConfigModel::getInstanceFromTemplateName($templateName);
            $templateConfig->bUseMagicInherit = false;
            $templateConfig->id = null;
            $templateConfig->isNewRecord = true;
            $templateConfig->gsid = null;
            $templateConfig->sid = $surveyId;
            $templateConfig->setToInherit();
            $templateConfig->save();
        }

        $criteria = new \CDbCriteria();
        $criteria->addCondition('gsid=:gsid');
        $criteria->addCondition('template_name=:template_name');
        $criteria->params = array('gsid' => $surveyGroupId, 'template_name' => $templateName);
        $templateConfig = $this->templateConfigModel->find($criteria);

        if (!is_a($templateConfig, 'TemplateConfiguration') && $templateName != null) {
            $templateConfig = $this->templateConfigModel::getInstanceFromTemplateName($templateName);
            $templateConfig->bUseMagicInherit = false;
            $templateConfig->id = null;
            $templateConfig->isNewRecord = true;
            $templateConfig->sid = null;
            $templateConfig->gsid = $surveyGroupId;
            $templateConfig->setToInherit();
            $templateConfig->save();
        }

        return $templateConfig;
    }
}
