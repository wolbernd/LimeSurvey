<?php

/**
 * Convert question theme from LS3 to LS4.
 */
final class QuestionThemeConverter
{
    /** @var array */
    private $appConfig;

    /** @var XmlIO */
    private $xmlIO;

    /**
     * @param ? $appConfig
     */
    public function __construct(array $appConfig, XmlIO $xmlIO)
    {
        $this->appConfig = $appConfig;
        $this->xmlIO = $xmlIO;
    }

    /**
     * @param string $sXMLDirectoryPath
     * @return array [string $message, boolean $success]
     * @throws \Exception
     */
    public function convert($sXMLDirectoryPath)
    {
        $sQuestionConfigFilePath = $this->getQuestionConfigPath($sXMLDirectoryPath);

        $themeConfig = $this->xmlIO->load($sQuestionConfigFilePath);

        $this->replaceTags($themeConfig);
        $this->setType($themeConfig);
        $this->setCompatibility($themeConfig);

        // check if core question theme can be found to fill in missing information
        $sPathToCoreConfigFile = $this->getCorePath($sQuestionConfigFilePath);

        if (!is_file($sPathToCoreConfigFile)) {
            return $aSuccess = [
                'message' => sprintf(
                    gT("Question theme could not be converted to LimeSurvey 4 standard. Reason: No matching core theme with the name %s could be found"),
                    $sPathToCoreConfigFile
                ),
                'success' => false
            ];
        }

        $coreConfig = $this->xmlIO->load($sPathToCoreConfigFile);

        $this->recoverQuestionType($themeConfig, $coreConfig);
        $this->recoverNewTags($themeConfig, $coreConfig);

        // write everything back to to xml file
        $this->xmlIO->save($themeConfig, $sQuestionConfigFilePath);

        return $aSuccess = [
            'message' => gT('Question Theme has been sucessfully converted to LimeSurvey 4'),
            'success' => true
        ];
    }

    /**
     * @param SimpleXMLElement $themeConfig
     * @return void
     */
    public function replaceTags(SimpleXMLElement $themeConfig)
    {
        // replace custom_attributes with attributes
        //if (preg_match('/<custom_attributes>/', $sQuestionConfigFile)) {
            //$sQuestionConfigFile = preg_replace('/<custom_attributes>/', '<attributes>', $sQuestionConfigFile);
            //$sQuestionConfigFile = preg_replace('/<\/custom_attributes>/', '</attributes>', $sQuestionConfigFile);
        //};

        // Do things
    }

    /**
     * @param SimpleXMLElement $themeConfig
     * @return void
     */
    public function setCompatibility(SimpleXMLElement $themeConfig)
    {
        if (isset($themeConfig->compatibility->version)) {
            $themeConfig->compatibility->version = '4.0';
        } else {
            $compatibility = $themeConfig->addChild('compatibility');
            $compatibility->addChild('version');
            $themeConfig->compatibility->version = '4.0';
        }
    }

    /**
     * @param SimpleXMLElement $themeConfig
     * @return void
     */
    public function setType(SimpleXMLElement $themeConfig)
    {
        // get type from core theme
        if (isset($themeConfig->metadata->type)) {
            $themeConfig->metadata->type = 'question_theme';
        } else {
            $themeConfig->metadata->addChild('type', 'question_theme');
        };
    }

    /**
     * @param string $sXMLDirectoryPath
     * @return string
     */
    public function getQuestionConfigPath($sXMLDirectoryPath)
    {
        $sXMLDirectoryPath = str_replace('\\', '/', $sXMLDirectoryPath);
        return $this->appConfig['rootdir']
            . DIRECTORY_SEPARATOR
            . $sXMLDirectoryPath
            . DIRECTORY_SEPARATOR
            . 'config.xml';
    }

    /**
     * @param string
     * @return string
     */
    public function getCorePath($sQuestionConfigFilePath)
    {
        $sThemeDirectoryName = basename(dirname($sQuestionConfigFilePath, 1));
        return str_replace(
            '\\',
            '/',
            $this->appConfig['rootdir'] . '/application/views/survey/questions/answer/' . $sThemeDirectoryName . '/config.xml'
        );
    }

    /**
     * Search missing new tags and copy theme from the core theme
     *
     * @param SimpleXMLElement $themeConfig
     * @param SimpleXMLElement $coreConfig
     * @return void
     */
    public function recoverNewTags(SimpleXMLElement $themeConfig, SimpleXMLElement $coreConfig)
    {
        /** @var string New metadata tags to recover from core type */
        $newTags = ['group', 'subquestions', 'answerscales', 'hasdefaultvalues', 'assessable', 'class'];
        foreach ($newTags as $metaTag) {
            if (!isset($themeConfig->metadata->$metaTag)) {
                $themeConfig->metadata->addChild($metaTag, $coreConfig->metadata->$metaTag);
            }
        }
    }

    /**
     * Get questiontype from core if it is missing
     *
     * @param SimpleXMLElement $themeConfig
     * @param SimpleXMLElement $coreConfig
     * @return void
     */
    public function recoverQuestionType(SimpleXMLElement $themeConfig, SimpleXMLElement $coreConfig)
    {
        if (!isset($themeConfig->metadata->questionType)) {
            $themeConfig->metadata->addChild('questionType', $coreConfig->metadata->questionType);
        };
    }
}
