<?php

namespace LimeSurvey\Models\Services;

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
        $sXMLDirectoryPath = str_replace('\\', '/', $sXMLDirectoryPath);

        $sQuestionConfigFilePath = $this->appConfig['rootdir']
            . DIRECTORY_SEPARATOR
            . $sXMLDirectoryPath
            . DIRECTORY_SEPARATOR
            . 'config.xml';
        $oThemeConfig = $this->xmlIO->load($sQuestionConfigFilePath);

        // replace custom_attributes with attributes
        $sThemeConfig = $oThemeConfig->asXML();
        if (preg_match('/<custom_attributes>/', $sThemeConfig)) {
            $sThemeConfig = preg_replace('/<custom_attributes>/', '<attributes>', $sThemeConfig);
            $sThemeConfig = preg_replace('/<\/custom_attributes>/', '</attributes>', $sThemeConfig);
        };
        $oThemeConfig = new \SimpleXMLElement($sThemeConfig);

        $sThemeDirectoryName = basename(dirname($sQuestionConfigFilePath, 1));
        $sPathToCoreConfigFile = str_replace(
            '\\',
            '/',
            $this->appConfig['rootdir']
            . '/application/views/survey/questions/answer/'
            . $sThemeDirectoryName
            . '/config.xml'
        );

        // get type from core theme
        if (isset($oThemeConfig->metadata->type)) {
            $oThemeConfig->metadata->type = 'question_theme';
        } else {
            $oThemeConfig->metadata->addChild('type', 'question_theme');
        };

        // set compatibility version
        if (isset($oThemeConfig->compatibility->version)) {
            $oThemeConfig->compatibility->version = '4.0';
        } else {
            $compatibility = $oThemeConfig->addChild('compatibility');
            $compatibility->addChild('version');
            $oThemeConfig->compatibility->version = '4.0';
        }

        $oThemeCoreConfig = $this->xmlIO->load($sPathToCoreConfigFile);
        if (empty($oThemeCoreConfig)) {
            return $aSuccess = [
                'message' => sprintf(
                    gT("Question theme could not be converted to LimeSurvey 4 standard. Reason: No matching core theme with the name %s could be found"),
                    $sThemeDirectoryName
                ),
                'success' => false
            ];
        }

        // get questiontype from core if it is missing
        if (!isset($oThemeConfig->metadata->questionType)) {
            $oThemeConfig->metadata->addChild('questionType', $oThemeCoreConfig->metadata->questionType);
        };

        // Search missing new tags and copy theme from the core theme
        $aNewMetadataTagsToRecoverFromCoreType = [
            'group',
            'subquestions',
            'answerscales',
            'hasdefaultvalues',
            'assessable',
            'class'
        ];
        foreach ($aNewMetadataTagsToRecoverFromCoreType as $sMetaTag) {
            if (!isset($oThemeConfig->metadata->$sMetaTag)) {
                $oThemeConfig->metadata->addChild($sMetaTag, $oThemeCoreConfig->metadata->$sMetaTag);
            }
        }

        // write everything back to to xml file
        $this->xmlIO->save($oThemeConfig, $sQuestionConfigFilePath);

        return $aSuccess = [
            'message' => gT('Question Theme has been sucessfully converted to LimeSurvey 4'),
            'success' => true
        ];
    }

    /**
     * @see https://stackoverflow.com/a/13882419/2138090
     */
    public function renameTag(\SimpleXMLElement $_oldTag, $newTagName)
    {
        $oldTag = new \DOMDocument();
        $oldTag->loadXML($_oldTag->asXML());
        $document = $oldTag->ownerDocument;

        $newTag = $document->createElement($newTagName);
        $oldTag->parentNode->replaceChild($newTag, $oldTag);

        foreach ($oldTag->attributes as $attribute) {
            $newTag->setAttribute($attribute->name, $attribute->value);
        }
        foreach (iterator_to_array($oldTag->childNodes) as $child) {
            $newTag->appendChild($oldTag->removeChild($child));
        }
        return $newTag;
    }
}
