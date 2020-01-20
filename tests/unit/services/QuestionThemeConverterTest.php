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
        \Yii::import('application.models.services.QuestionThemeConverter');
        $foo = new \QuestionThemeConverter(\Yii::app()->getConfig('rootdir'));
    }
}
