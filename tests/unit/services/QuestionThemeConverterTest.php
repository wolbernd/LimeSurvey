<?php

namespace ls\tests;

/**
 * Check the JSON saved in database.
 */
class QuestionThemeConverterTest extends TestBaseClass
{
    /**
     * 
     */
    public function testBasic()
    {
        $foo = new \LimeSurvey\Models\Services\QuestionThemeConverter(
            \Yii::app()->getConfig(),
            new \LimeSurvey\Models\Services\XmlIO()
        );
    }
}
