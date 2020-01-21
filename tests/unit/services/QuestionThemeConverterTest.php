<?php

namespace ls\tests;

use LimeSurvey\Models\Services\QuestionThemeConverter;
use LimeSurvey\Models\Services\XmlIO;
use Yii;

/**
 * Check the JSON saved in database.
 */
class QuestionThemeConverterTest extends TestBaseClass
{
    /**
     * @return void
     */
    public function testBasic()
    {
        $converter = new QuestionThemeConverter(
            ['rootdir' => __DIR__],
            (new class extends XmlIO {
                public function save($xml, $path)
                {
                    return true;
                }
                public function load($path)
                {
                    return new \SimpleXMLElement('<test></test>');
                }
            })
        );
        $converter->convert('blablabla');
    }
}
