<?php

namespace LimeSurvey\Models\Services;

/**
 * Fetch list of all admin themes.
 * Used in global settings.
 *
 * getAdminThemeList
 */
class AdminThemeListFetcher
{
    /** @var array */
    private $appConfig;

    /** @var XmlIO */
    private $xmlIO;

    /** @var AdminTheme */
    private $adminThemeModel;

    /**
     * @param array $appConfig
     * @param XmlIO $xmlIO
     */
    public function __construct(array $appConfig, XmlIO $xmlIO, \AdminTheme $adminThemeModel)
    {
        $this->appConfig = $appConfig;
        $this->xmlIO = $xmlIO;
        $this->adminThemeModel = $adminThemeModel;
    }

    /**
     * @return SimpleXMLElement[]
     */
    public function getList()
    {
        // The directory containing the default admin themes
        $standardDir     = $this->appConfig['styledir'];

        // The directory containing the user themes
        $uploadDir       = $this->appConfig['uploaddir'].DIRECTORY_SEPARATOR.'admintheme';

        // Array containing the configuration files of standard admin themes (styles/...)
        $standardThemes = $this->getListAux($standardDir);

        // Array containing the configuration files of user admin themes (upload/admintheme/...)
        $userThemes     = $this->getListAux($uploadDir);

        $list = array_merge($standardThemes, $userThemes);
        ksort($list);

        return $list;
    }

    /**
     * Return an array containing the configuration object of all templates in a given directory
     *
     * @param string $dir          The directory to scan
     * @return SimpleXMLElement[]  List of configs
     */
    private function getListAux($dir)
    {
        $list = array();
        if ($dir && $dirHandle = opendir($dir)) {
            while (false !== ($file = readdir($dirHandle))) {
                if (is_dir($dir.DIRECTORY_SEPARATOR.$file) && is_file($dir.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.'config.xml')) {
                    $templateConfig = $this->xmlIO->load($dir.DIRECTORY_SEPARATOR.$file.'/config.xml');
                    if ($this->adminThemeModel->isStandardAdminTheme($file)) {
                        $previewUrl = $this->appConfig['styleurl'].$file;
                    } else {
                        $previewUrl = $this->appConfig['uploadurl'].DIRECTORY_SEPARATOR.'admintheme'.DIRECTORY_SEPARATOR.$file;
                    }
                    $templateConfig->path    = $file;
                    $templateConfig->preview = '<img src="'.$previewUrl.'/preview.png" alt="admin theme preview" height="200" class="img-thumbnail" />';
                    $list[$file] = $templateConfig;
                }
            }
            closedir($dirHandle);
        }
        return $list;
    }
}
