<?php

/**
 * Convert question theme from LS3 to LS4.
 */
final class QuestionThemeConverter
{
    private $rootdir;

    /**
     * @param ? $appConfig
     */
    public function __construct($rootdir)
    {
        $this->rootdir = $rootdir;
    }

    /**
     * @param string $sXMLDirectoryPath
     * @return array [string $message, boolean $success]
     * @throws \Exception
     */
    public function convert($sXMLDirectoryPath)
    {
        $sQuestionConfigFilePath = $this->getQuestionConfigPath($sXMLDirectoryPath);

        $oThemeConfig = $this->loadXml($sQuestionConfigFilePath);

        $this->replaceTags($oThemeConfig);
        $this->setType($oThemeConfig);
        $this->setCompatibility($oThemeConfig);

        // check if core question theme can be found to fill in missing information
        $sPathToCoreConfigFile = $this->getCorePath($sQuestionConfigFilePath);

        if (!is_file($sPathToCoreConfigFile)) {
            return $aSuccess = [
                'message' => sprintf(
                    gT("Question theme could not be converted to LimeSurvey 4 standard. Reason: No matching core theme with the name %s could be found"),
                    $sThemeDirectoryName
                ),
                'success' => false
            ];
        }

        $oThemeCoreConfig = $this->loadXml($sPathToCoreConfigFile);

        // get questiontype from core if it is missing
        if (!isset($oThemeConfig->metadata->questionType)) {
            $oThemeConfig->metadata->addChild('questionType', $oThemeCoreConfig->metadata->questionType);
        };

        // search missing new tags and copy theme from the core theme
        $aNewMetadataTagsToRecoverFromCoreType = ['group', 'subquestions', 'answerscales', 'hasdefaultvalues', 'assessable', 'class'];
        foreach ($aNewMetadataTagsToRecoverFromCoreType as $sMetaTag) {
            if (!isset($oThemeConfig->metadata->$sMetaTag)) {
                $oThemeConfig->metadata->addChild($sMetaTag, $oThemeCoreConfig->metadata->$sMetaTag);
            }
        }

        // write everything back to to xml file
        $oThemeConfig->saveXML($sQuestionConfigFilePath);

        return $aSuccess = [
            'message' => gT('Question Theme has been sucessfully converted to LimeSurvey 4'),
            'success' => true
        ];
    }

    /**
     * @param string $path
     * @return SimpleXMLElement
     * @throws \Exception
     */
    public function loadXml($path)
    {
        $oldState = libxml_disable_entity_loader(true);
        $file = file_get_contents($path);
        if (empty($file)) {
            throw new \Exception(
                sprintf(
                    gT('Found no file at path %s'),
                    $path
                )
            );
        }
        $xml = simplexml_load_string($file);
        libxml_disable_entity_loader($oldState);
        return $xml;
    }

    /**
     * @param SimpleXMLElement $oThemeConfig
     * @return void
     */
    public function replaceTags(SimpleXMLElement $oThemeConfig)
    {
        // replace custom_attributes with attributes
        //if (preg_match('/<custom_attributes>/', $sQuestionConfigFile)) {
            //$sQuestionConfigFile = preg_replace('/<custom_attributes>/', '<attributes>', $sQuestionConfigFile);
            //$sQuestionConfigFile = preg_replace('/<\/custom_attributes>/', '</attributes>', $sQuestionConfigFile);
        //};

        // Do things
    }

    /**
     * @param SimpleXMLElement $oThemeConfig
     * @return void
     */
    public function setCompatibility(SimpleXMLElement $oThemeConfig)
    {
        if (isset($oThemeConfig->compatibility->version)) {
            $oThemeConfig->compatibility->version = '4.0';
        } else {
            $compatibility = $oThemeConfig->addChild('compatibility');
            $compatibility->addChild('version');
            $oThemeConfig->compatibility->version = '4.0';
        }
    }

    /**
     * @param SimpleXMLElement $oThemeConfig
     * @return void
     */
    public function setType(SimpleXMLElement $oThemeConfig)
    {
        // get type from core theme
        if (isset($oThemeConfig->metadata->type)) {
            $oThemeConfig->metadata->type = 'question_theme';
        } else {
            $oThemeConfig->metadata->addChild('type', 'question_theme');
        };
    }

    /**
     * @param string $sXMLDirectoryPath
     * @return string
     */
    public function getQuestionConfigPath($sXMLDirectoryPath)
    {
        $sXMLDirectoryPath = str_replace('\\', '/', $sXMLDirectoryPath);
        return $this->rootdir . DIRECTORY_SEPARATOR . $sXMLDirectoryPath . DIRECTORY_SEPARATOR . 'config.xml';
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
            $this->rootdir . '/application/views/survey/questions/answer/' . $sThemeDirectoryName . '/config.xml'
        );
    }
}
